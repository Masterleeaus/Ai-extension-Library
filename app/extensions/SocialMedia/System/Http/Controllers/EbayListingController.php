<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\EbayListingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class EbayListingController extends Controller
{
    public function __construct(private readonly EbayListingService $service) {}

    public function readiness(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
        ]);

        return $this->respond(fn () => $this->service->readiness(
            $request->user(),
            $this->account((int) $validated['account_id'])
        ));
    }

    public function draft(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $this->validateListingAction($request);

        return $this->respond(fn () => $this->service->syncDraft(
            $request->user(),
            $item,
            $this->account((int) $validated['account_id']),
            (array) $validated['listing'],
            (string) $validated['idempotency_key']
        ));
    }

    public function publish(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $this->validateListingAction($request);

        return $this->respond(fn () => $this->service->publish(
            $request->user(),
            $item,
            $this->account((int) $validated['account_id']),
            (array) $validated['listing'],
            (string) $validated['idempotency_key']
        ));
    }

    public function revise(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $this->validateListingAction($request);

        return $this->respond(fn () => $this->service->revise(
            $request->user(),
            $item,
            $this->account((int) $validated['account_id']),
            (array) $validated['listing'],
            (string) $validated['idempotency_key']
        ));
    }

    public function withdraw(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id'      => 'required|integer',
            'idempotency_key' => 'required|string|max:128',
        ]);

        return $this->respond(fn () => $this->service->withdraw(
            $request->user(),
            $item,
            $this->account((int) $validated['account_id']),
            (string) $validated['idempotency_key']
        ));
    }

    public function reconcile(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
        ]);

        return $this->respond(fn () => $this->service->reconcile(
            $request->user(),
            $item,
            $this->account((int) $validated['account_id'])
        ));
    }

    public function buyerQuestionHandoff(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id'          => 'required|integer',
            'question.question_id' => 'required|string|max:255',
            'question.buyer_alias' => 'nullable|string|max:255',
            'question.subject'     => 'nullable|string|max:500',
            'question.message'     => 'required|string|max:10000',
            'question.received_at' => 'nullable|date',
        ]);

        return $this->respond(fn () => $this->service->buyerQuestionHandoff(
            $request->user(),
            $item,
            $this->account((int) $validated['account_id']),
            (array) $validated['question']
        ));
    }

    private function validateListingAction(Request $request): array
    {
        return $request->validate([
            'account_id'      => 'required|integer',
            'idempotency_key' => 'required|string|max:128',
            'listing'         => 'required|array',
        ]);
    }

    private function account(int $accountId): SocialMediaPlatform
    {
        return SocialMediaPlatform::query()
            ->where('id', $accountId)
            ->where('user_id', Auth::id())
            ->where('platform', PlatformEnum::ebay->value)
            ->firstOrFail();
    }

    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data'   => $callback(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status'  => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
