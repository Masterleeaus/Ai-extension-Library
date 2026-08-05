<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Http;

use App\Domains\WorkCore\System\Actions\{ActionRequest,BusinessActionDispatcher};
use App\Domains\WorkCore\System\ReadModels\ReadModelExecutor;
use Illuminate\Http\{JsonResponse,Request};
use Illuminate\Validation\ValidationException;

final class PricingController
{
    public function preview(Request $request, ReadModelExecutor $reads): JsonResponse
    {
        $validated = $this->validatedPriceInput($request);
        return response()->json(['data' => $this->read('workcore.pricing.preview', $validated, $reads)]);
    }

    public function apply(Request $request, BusinessActionDispatcher $actions): JsonResponse
    {
        $validated = $this->validatedPriceInput($request);
        return response()->json(['data' => $this->dispatch('workcore.pricing.apply', $validated, $request, $actions)], 201);
    }

    public function upsertRule(Request $request, BusinessActionDispatcher $actions): JsonResponse
    {
        $validated = $request->validate([
            'public_id' => ['nullable','string','max:26'], 'name' => ['required','string','max:160'],
            'target_type' => ['nullable','string','max:80'], 'target_reference' => ['nullable','string','max:160'],
            'priority' => ['nullable','integer','min:0','max:10000'],
            'adjustment_type' => ['required','in:fixed_minor,percentage,multiplier'],
            'adjustment_value' => ['required','numeric'], 'conditions' => ['nullable','array'],
            'starts_at' => ['nullable','date'], 'ends_at' => ['nullable','date','after_or_equal:starts_at'],
            'is_active' => ['nullable','boolean'],
        ]);
        $this->validateAdjustmentValue($validated);

        return response()->json(['data' => $this->dispatch('workcore.pricing.rule.upsert', $validated, $request, $actions)], 201);
    }

    public function upsertSeasonalRate(Request $request, BusinessActionDispatcher $actions): JsonResponse
    {
        $validated = $request->validate([
            'public_id' => ['nullable','string','max:26'],
            'name' => ['required','string','max:160'],
            'target_type' => ['nullable','string','max:80'],
            'target_reference' => ['nullable','string','max:160'],
            'starts_on' => ['required','date'],
            'ends_on' => ['required','date','after_or_equal:starts_on'],
            'multiplier' => ['required','numeric','min:0.10','max:10'],
            'priority' => ['nullable','integer','min:0','max:10000'],
            'is_active' => ['nullable','boolean'],
        ]);

        return response()->json(['data' => $this->dispatch('workcore.pricing.seasonal.upsert', $validated, $request, $actions)], 201);
    }

    public function recordSignal(Request $request, BusinessActionDispatcher $actions): JsonResponse
    {
        $validated = $request->validate([
            'signal_type' => ['required','in:demand,occupancy,competitor'],
            'target_type' => ['required','string','max:80'], 'target_reference' => ['required','string','max:160'],
            'source' => ['nullable','string','max:80'], 'recorded_at' => ['nullable','date'], 'metadata' => ['nullable','array'],
            'indicator_type' => ['nullable','string','max:80'],
            'score' => ['required_if:signal_type,demand','nullable','numeric','min:0','max:100'],
            'quantity' => ['nullable','integer','min:0'],
            'capacity' => ['required_if:signal_type,occupancy','nullable','integer','min:1'],
            'occupied' => ['required_if:signal_type,occupancy','nullable','integer','min:0','lte:capacity'],
            'competitor_name' => ['required_if:signal_type,competitor','nullable','string','max:160'],
            'observed_price_minor' => ['required_if:signal_type,competitor','nullable','integer','min:0'],
            'currency' => ['required_if:signal_type,competitor','nullable','string','alpha','size:3'],
            'source_url' => ['nullable','url','max:2048'],
        ]);
        $this->normalizeCurrency($validated);

        return response()->json(['data' => $this->dispatch('workcore.pricing.signal.record', $validated, $request, $actions)], 201);
    }

    public function analytics(Request $request, ReadModelExecutor $reads): JsonResponse
    {
        $validated = $request->validate(['days' => ['nullable','integer','min:1','max:365'], 'target_type' => ['nullable','string','max:80'], 'target_reference' => ['nullable','string','max:160']]);
        return response()->json(['data' => $this->read('workcore.pricing.analytics', $validated, $reads)]);
    }

    /** @return array<string,mixed> */
    private function validatedPriceInput(Request $request): array
    {
        $validated = $request->validate($this->priceRules());
        $this->normalizeCurrency($validated);

        return $validated;
    }

    /** @return array<string,array<int,string>> */
    private function priceRules(): array
    {
        return [
            'base_price_minor' => ['required','integer','min:0'], 'currency' => ['nullable','string','alpha','size:3'],
            'target_type' => ['required','string','max:80'], 'target_reference' => ['required','string','max:160'],
            'minimum_price_minor' => ['nullable','integer','min:0'],
            'maximum_price_minor' => ['nullable','integer','min:0','gte:minimum_price_minor'],
            'channel' => ['nullable','string','max:80'], 'context' => ['nullable','array'], 'demand_signals' => ['nullable','array'],
        ];
    }

    /** @param array<string,mixed> $validated */
    private function validateAdjustmentValue(array $validated): void
    {
        $type = (string) $validated['adjustment_type'];
        $raw = $validated['adjustment_value'];
        $value = (float) $raw;
        $valid = match ($type) {
            'fixed_minor' => is_int($raw) || (is_string($raw) && preg_match('/^-?\d+$/', $raw) === 1),
            'percentage' => is_finite($value) && $value >= -100 && $value <= 1000,
            'multiplier' => is_finite($value) && $value >= 0.10 && $value <= 10.0,
            default => false,
        };
        if (! $valid) {
            throw ValidationException::withMessages([
                'adjustment_value' => 'The adjustment value is invalid for the selected adjustment type.',
            ]);
        }
    }

    /** @param array<string,mixed> $validated */
    private function normalizeCurrency(array &$validated): void
    {
        if (isset($validated['currency'])) {
            $validated['currency'] = strtoupper((string) $validated['currency']);
        }
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function dispatch(string $key, array $payload, Request $request, BusinessActionDispatcher $actions): array
    {
        $tenant = workcore_tenant();
        $idempotency = trim((string) $request->header('Idempotency-Key'));
        abort_if($idempotency === '', 422, 'Idempotency-Key header is required.');
        return $actions->dispatch(new ActionRequest($key, $payload, (int) $tenant->companyId(), (int) $tenant->userId(), $idempotency, $request->header('X-WorkCore-Confirmation'), 'api'))->data;
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    private function read(string $key, array $filters, ReadModelExecutor $reads): array
    {
        $tenant = workcore_tenant();
        return $reads->execute($key, $filters, (int) $tenant->companyId(), (int) $tenant->userId());
    }
}
