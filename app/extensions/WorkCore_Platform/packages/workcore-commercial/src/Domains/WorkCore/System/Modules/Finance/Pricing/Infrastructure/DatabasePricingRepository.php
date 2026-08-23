<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Infrastructure;

use App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts\PricingRepositoryContract;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\Domain\PricingInputValidator;
use App\Domains\WorkCore\System\Modules\Finance\Pricing\DTO\PricingDecision;
use DateTimeInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DatabasePricingRepository implements PricingRepositoryContract
{
    public function __construct(
        private ConnectionInterface $db,
        private PricingInputValidator $validator,
    ) {}

    public function context(int $companyId, array $input, DateTimeInterface $at): array
    {
        $this->assertCompanyId($companyId);
        $targetType = trim((string) ($input['target_type'] ?? 'generic'));
        $targetReference = trim((string) ($input['target_reference'] ?? 'default'));
        $atValue = $at->format('Y-m-d H:i:s');

        $rules = $this->db->table('tz_pricing_rules')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($targetType): void {
                $query->whereNull('target_type')->orWhere('target_type', $targetType);
            })
            ->where(function ($query) use ($targetReference): void {
                $query->whereNull('target_reference')->orWhere('target_reference', $targetReference);
            })
            ->where(function ($query) use ($atValue): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $atValue);
            })
            ->where(function ($query) use ($atValue): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $atValue);
            })
            ->orderBy('priority')->orderBy('public_id')
            ->get()
            ->map(static fn (object $row): array => [
                'id' => (string) $row->public_id,
                'priority' => (int) $row->priority,
                'type' => (string) $row->adjustment_type,
                'value' => (float) $row->adjustment_value,
                'conditions' => json_decode((string) ($row->conditions ?? '{}'), true) ?: [],
            ])->all();

        $seasonal = $this->db->table('tz_seasonal_rates')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('starts_on', '<=', $at->format('Y-m-d'))
            ->where('ends_on', '>=', $at->format('Y-m-d'))
            ->where(function ($query) use ($targetType): void {
                $query->whereNull('target_type')->orWhere('target_type', $targetType);
            })
            ->where(function ($query) use ($targetReference): void {
                $query->whereNull('target_reference')->orWhere('target_reference', $targetReference);
            })
            ->orderBy('priority')->get();
        $seasonalMultiplier = 1.0;
        foreach ($seasonal as $rate) {
            $seasonalMultiplier *= (float) $rate->multiplier;
        }

        $demandScore = $this->db->table('tz_demand_indicators')
            ->where('company_id', $companyId)
            ->where('target_type', $targetType)
            ->where('target_reference', $targetReference)
            ->where('recorded_at', '>=', $at->modify('-24 hours')->format('Y-m-d H:i:s'))
            ->avg('score');

        $occupancy = $this->db->table('tz_occupancy_snapshots')
            ->where('company_id', $companyId)
            ->where('target_type', $targetType)
            ->where('target_reference', $targetReference)
            ->orderByDesc('recorded_at')->first();

        return [
            'rules' => $rules,
            'seasonal_multiplier' => round($seasonalMultiplier, 4),
            'demand_score' => $demandScore === null ? 50.0 : round((float) $demandScore, 2),
            'occupancy_percentage' => $occupancy === null ? null : (float) $occupancy->occupancy_percentage,
        ];
    }

    public function upsertRule(int $companyId, int $actorId, array $payload): array
    {
        $this->assertCompanyId($companyId);
        $this->assertActorId($actorId);
        $this->validator->validateRule($payload);
        $publicId = trim((string) ($payload['public_id'] ?? '')) ?: (string) Str::ulid();
        $type = trim((string) $payload['adjustment_type']);
        if (! in_array($type, ['fixed_minor', 'percentage', 'multiplier'], true)) {
            throw new InvalidArgumentException('Unsupported pricing adjustment type.');
        }
        $record = [
            'company_id' => $companyId,
            'public_id' => $publicId,
            'name' => trim((string) $payload['name']),
            'target_type' => $this->nullableString($payload['target_type'] ?? null),
            'target_reference' => $this->nullableString($payload['target_reference'] ?? null),
            'priority' => max(0, min(10000, (int) ($payload['priority'] ?? 100))),
            'adjustment_type' => $type,
            'adjustment_value' => (float) $payload['adjustment_value'],
            'conditions' => json_encode(is_array($payload['conditions'] ?? null) ? $payload['conditions'] : [], JSON_THROW_ON_ERROR),
            'starts_at' => $payload['starts_at'] ?? null,
            'ends_at' => $payload['ends_at'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'updated_by' => $actorId,
            'updated_at' => now(),
        ];
        $exists = $this->db->table('tz_pricing_rules')->where('company_id', $companyId)->where('public_id', $publicId)->exists();
        if ($exists) {
            $this->db->table('tz_pricing_rules')->where('company_id', $companyId)->where('public_id', $publicId)->update($record);
        } else {
            $record['created_by'] = $actorId;
            $record['created_at'] = now();
            $this->db->table('tz_pricing_rules')->insert($record);
        }
        return $this->ruleByPublicId($companyId, $publicId);
    }

    public function upsertSeasonalRate(int $companyId, int $actorId, array $payload): array
    {
        $this->assertCompanyId($companyId);
        $this->assertActorId($actorId);
        $this->validator->validateSeasonalRate($payload);
        $publicId = trim((string) ($payload['public_id'] ?? '')) ?: (string) Str::ulid();
        $record = [
            'company_id' => $companyId,
            'public_id' => $publicId,
            'name' => trim((string) $payload['name']),
            'target_type' => $this->nullableString($payload['target_type'] ?? null),
            'target_reference' => $this->nullableString($payload['target_reference'] ?? null),
            'starts_on' => (string) $payload['starts_on'],
            'ends_on' => (string) $payload['ends_on'],
            'multiplier' => round((float) $payload['multiplier'], 4),
            'priority' => max(0, min(10000, (int) ($payload['priority'] ?? 100))),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'updated_by' => $actorId,
            'updated_at' => now(),
        ];
        $query = $this->db->table('tz_seasonal_rates')
            ->where('company_id', $companyId)
            ->where('public_id', $publicId);
        if ((clone $query)->exists()) {
            $query->update($record);
        } else {
            $record['created_by'] = $actorId;
            $record['created_at'] = now();
            $this->db->table('tz_seasonal_rates')->insert($record);
        }

        return $this->seasonalRateByPublicId($companyId, $publicId);
    }

    public function recordSignal(int $companyId, int $actorId, array $payload): array
    {
        $this->assertCompanyId($companyId);
        $this->assertActorId($actorId);
        $this->validator->validateSignal($payload);
        $type = trim((string) $payload['signal_type']);
        $publicId = (string) Str::ulid();
        $common = [
            'company_id' => $companyId,
            'public_id' => $publicId,
            'target_type' => trim((string) $payload['target_type']),
            'target_reference' => trim((string) $payload['target_reference']),
            'source' => trim((string) ($payload['source'] ?? 'manual')),
            'recorded_by' => $actorId,
            'recorded_at' => $payload['recorded_at'] ?? now(),
            'metadata' => json_encode(is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $table = match ($type) {
            'demand' => 'tz_demand_indicators',
            'occupancy' => 'tz_occupancy_snapshots',
            'competitor' => 'tz_competitor_price_snapshots',
            default => throw new InvalidArgumentException('signal_type must be demand, occupancy or competitor.'),
        };
        $specific = match ($type) {
            'demand' => [
                'indicator_type' => trim((string) ($payload['indicator_type'] ?? 'composite')),
                'score' => round((float) $payload['score'], 2),
                'quantity' => max(0, (int) ($payload['quantity'] ?? 0)),
            ],
            'occupancy' => $this->occupancyPayload($payload),
            'competitor' => [
                'competitor_name' => trim((string) $payload['competitor_name']),
                'observed_price_minor' => (int) $payload['observed_price_minor'],
                'currency' => strtoupper(trim((string) $payload['currency'])),
                'source_url' => $this->nullableString($payload['source_url'] ?? null),
            ],
        };
        $this->db->table($table)->insert($common + $specific);
        return ['public_id' => $publicId, 'signal_type' => $type, 'target_type' => $common['target_type'], 'target_reference' => $common['target_reference']];
    }

    public function recordDecision(int $companyId, int $actorId, array $input, PricingDecision $decision): array
    {
        $this->assertCompanyId($companyId);
        $this->assertActorId($actorId);
        $this->validator->validatePriceInput($input);
        $publicId = (string) Str::ulid();
        $record = [
            'company_id' => $companyId,
            'public_id' => $publicId,
            'target_type' => trim((string) $input['target_type']),
            'target_reference' => trim((string) $input['target_reference']),
            'base_price_minor' => $decision->basePriceMinor,
            'final_price_minor' => $decision->finalPriceMinor,
            'currency' => $decision->currency,
            'factors' => json_encode($decision->toArray(), JSON_THROW_ON_ERROR),
            'decision_hash' => $decision->decisionHash,
            'calculated_by' => $actorId,
            'calculated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $this->db->table('tz_price_history')->insert($record);
        return ['public_id' => $publicId] + $decision->toArray();
    }

    public function analytics(int $companyId, array $filters): array
    {
        $this->assertCompanyId($companyId);
        $days = max(1, min(365, (int) ($filters['days'] ?? 30)));
        $history = $this->db->table('tz_price_history')->where('company_id', $companyId)
            ->where('calculated_at', '>=', now()->subDays($days));
        if (! empty($filters['target_type'])) {
            $history->where('target_type', (string) $filters['target_type']);
        }
        if (! empty($filters['target_reference'])) {
            $history->where('target_reference', (string) $filters['target_reference']);
        }
        $count = (int) (clone $history)->count();
        $baseTotal = (int) ((clone $history)->sum('base_price_minor') ?? 0);
        $finalTotal = (int) ((clone $history)->sum('final_price_minor') ?? 0);
        $averageChange = $baseTotal > 0 ? (($finalTotal - $baseTotal) / $baseTotal) * 100 : 0.0;

        $competitors = $this->db->table('tz_competitor_price_snapshots')->where('company_id', $companyId)
            ->where('recorded_at', '>=', now()->subDays($days));
        if (! empty($filters['target_type'])) {
            $competitors->where('target_type', (string) $filters['target_type']);
        }
        if (! empty($filters['target_reference'])) {
            $competitors->where('target_reference', (string) $filters['target_reference']);
        }
        $competitorAverage = (float) ((clone $competitors)->avg('observed_price_minor') ?? 0);
        $ownAverage = $count > 0 ? $finalTotal / $count : 0.0;
        $marketIndex = $ownAverage > 0 && $competitorAverage > 0 ? $competitorAverage / $ownAverage : 1.0;

        return [
            'days' => $days,
            'decision_count' => $count,
            'base_revenue_minor' => $baseTotal,
            'projected_revenue_minor' => $finalTotal,
            'projected_revenue_impact_minor' => $finalTotal - $baseTotal,
            'average_price_change_percent' => round($averageChange, 4),
            'competitor_average_price_minor' => (int) round($competitorAverage),
            'market_price_index' => round($marketIndex, 4),
            'history' => (clone $history)->orderByDesc('calculated_at')->limit(100)->get()->map(static fn (object $row): array => [
                'public_id' => (string) $row->public_id,
                'target_type' => (string) $row->target_type,
                'target_reference' => (string) $row->target_reference,
                'base_price_minor' => (int) $row->base_price_minor,
                'final_price_minor' => (int) $row->final_price_minor,
                'currency' => (string) $row->currency,
                'calculated_at' => (string) $row->calculated_at,
            ])->all(),
        ];
    }

    private function assertCompanyId(int $companyId): void
    {
        if ($companyId < 1) {
            throw new InvalidArgumentException('A valid WorkCore company is required.');
        }
    }

    private function assertActorId(int $actorId): void
    {
        if ($actorId < 1) {
            throw new InvalidArgumentException('A valid WorkCore actor is required.');
        }
    }

    /** @return array<string,mixed> */
    private function ruleByPublicId(int $companyId, string $publicId): array
    {
        $row = $this->db->table('tz_pricing_rules')->where('company_id', $companyId)->where('public_id', $publicId)->first();
        if (! $row) {
            throw new InvalidArgumentException('Pricing rule was not persisted.');
        }
        return [
            'public_id' => (string) $row->public_id,
            'name' => (string) $row->name,
            'priority' => (int) $row->priority,
            'adjustment_type' => (string) $row->adjustment_type,
            'adjustment_value' => (float) $row->adjustment_value,
            'is_active' => (bool) $row->is_active,
        ];
    }

    /** @return array<string,mixed> */
    private function seasonalRateByPublicId(int $companyId, string $publicId): array
    {
        $row = $this->db->table('tz_seasonal_rates')
            ->where('company_id', $companyId)
            ->where('public_id', $publicId)
            ->first();
        if (! $row) {
            throw new InvalidArgumentException('Seasonal rate was not persisted.');
        }

        return [
            'public_id' => (string) $row->public_id,
            'name' => (string) $row->name,
            'target_type' => $row->target_type === null ? null : (string) $row->target_type,
            'target_reference' => $row->target_reference === null ? null : (string) $row->target_reference,
            'starts_on' => (string) $row->starts_on,
            'ends_on' => (string) $row->ends_on,
            'multiplier' => (float) $row->multiplier,
            'priority' => (int) $row->priority,
            'is_active' => (bool) $row->is_active,
        ];
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function occupancyPayload(array $payload): array
    {
        $capacity = max(0, (int) ($payload['capacity'] ?? 0));
        $occupied = max(0, (int) ($payload['occupied'] ?? 0));
        if ($capacity < 1 || $occupied > $capacity) {
            throw new InvalidArgumentException('Occupancy requires capacity >= 1 and occupied <= capacity.');
        }
        return [
            'capacity' => $capacity,
            'occupied' => $occupied,
            'occupancy_percentage' => round(($occupied / $capacity) * 100, 2),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
