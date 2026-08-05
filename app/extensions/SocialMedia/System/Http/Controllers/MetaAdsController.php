<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\PaidMediaCampaign;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\MetaAdsService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class MetaAdsController extends Controller
{
    public function __construct(private readonly MetaAdsService $service) {}

    public function createDraft(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $this->validateDraft($request);

        return $this->respond(fn () => $this->locked(
            'item',
            $item->getKey(),
            fn () => $this->service->saveDraft(
                $request->user(),
                $item,
                $this->account((int) $validated['account_id']),
                (array) $validated['campaign']
            )
        ));
    }

    public function updateDraft(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        $validated = $this->validateDraft($request);

        return $this->respond(fn () => $this->locked(
            'campaign',
            $campaign->getKey(),
            fn () => $this->service->saveDraft(
                $request->user(),
                $campaign->distributionItem,
                $this->account((int) $validated['account_id']),
                (array) $validated['campaign'],
                $campaign->fresh()
            )
        ));
    }

    public function recommendations(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        return $this->respond(fn () => $this->service->recommendAudience(
            $request->user(),
            $campaign
        ));
    }

    public function approveBudget(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        $validated = $request->validate([
            'human_confirmed' => 'required|accepted',
            'confirmation' => 'required|string|size:64',
        ]);

        return $this->respond(fn () => $this->locked(
            'campaign',
            $campaign->getKey(),
            fn () => $this->service->approveBudget(
                $request->user(),
                $campaign->fresh(),
                (bool) $validated['human_confirmed'],
                (string) $validated['confirmation']
            )
        ));
    }

    public function syncPaused(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:128',
        ]);

        return $this->respond(fn () => $this->locked(
            'campaign',
            $campaign->getKey(),
            fn () => $this->service->syncPaused(
                $request->user(),
                $campaign->fresh(),
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function preview(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        $validated = $request->validate([
            'ad_format' => 'required|string|max:100',
        ]);

        return $this->respond(fn () => $this->service->preview(
            $request->user(),
            $campaign,
            (string) $validated['ad_format']
        ));
    }

    public function activate(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        $validated = $request->validate([
            'activation_confirmation' => 'required|string|size:64',
            'idempotency_key' => 'required|string|max:128',
        ]);

        return $this->respond(fn () => $this->locked(
            'campaign',
            $campaign->getKey(),
            fn () => $this->service->activate(
                $request->user(),
                $campaign->fresh(),
                (string) $validated['activation_confirmation'],
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function pause(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:128',
        ]);

        return $this->respond(fn () => $this->locked(
            'campaign',
            $campaign->getKey(),
            fn () => $this->service->pause(
                $request->user(),
                $campaign->fresh(),
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function insights(
        Request $request,
        PaidMediaCampaign $campaign
    ): JsonResponse {
        $validated = $request->validate([
            'date_preset' => 'nullable|string|max:100',
            'time_range' => 'nullable|array',
            'level' => 'nullable|string|max:50',
            'breakdowns' => 'nullable|string|max:500',
        ]);

        return $this->respond(fn () => $this->service->insights(
            $request->user(),
            $campaign,
            $validated
        ));
    }

    private function validateDraft(Request $request): array
    {
        return $request->validate([
            'account_id' => 'required|integer',
            'campaign' => 'required|array',
            'campaign.ad_account_id' => 'required|string|max:100',
            'campaign.name' => 'required|string|max:255',
            'campaign.objective' => 'required|string|max:100',
            'campaign.budget' => 'required|array',
            'campaign.budget.type' => 'required|string|in:daily,lifetime',
            'campaign.budget.amount_minor' => 'required|integer|min:1',
            'campaign.budget.spend_cap_minor' => 'nullable|integer|min:1',
            'campaign.currency' => 'required|string|size:3',
            'campaign.schedule' => 'nullable|array',
            'campaign.schedule.starts_at' => 'nullable|date',
            'campaign.schedule.ends_at' => 'nullable|date',
            'campaign.campaign' => 'required|array',
            'campaign.adset' => 'required|array',
            'campaign.creative' => 'required|array',
            'campaign.ad' => 'required|array',
        ]);
    }

    private function account(int $accountId): SocialMediaPlatform
    {
        return SocialMediaPlatform::query()
            ->where('id', $accountId)
            ->where('user_id', Auth::id())
            ->where('platform', PlatformEnum::facebook->value)
            ->firstOrFail();
    }

    private function locked(string $type, int|string $id, callable $callback): mixed
    {
        $lockName = implode(':', [
            'titan-reach',
            'meta-ads',
            Auth::id(),
            $type,
            $id,
        ]);

        try {
            return Cache::lock($lockName, 180)->block(5, $callback);
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException(
                'Another request is already processing this paid-media campaign.',
                previous: $exception
            );
        }
    }

    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $callback(),
            ]);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => trans('The Meta Ads operation could not be completed.'),
            ], 500);
        }
    }
}
