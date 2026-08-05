<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\GoogleBusinessProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GoogleBusinessProfileController extends Controller
{
    public function __construct(private readonly GoogleBusinessProfileService $service) {}

    public function readiness(Request $request): JsonResponse
    {
        $validated = $request->validate(['account_id' => 'required|integer']);

        return $this->respond(fn () => $this->service->readiness(
            $request->user(),
            $this->account((int) $validated['account_id'])
        ));
    }

    public function publish(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $this->validateMutation($request);

        return $this->respond(fn () => $this->locked(
            $item,
            fn () => $this->service->publish(
                $request->user(),
                $item,
                $this->account((int) $validated['account_id']),
                (array) $validated['payload'],
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function uploadPhoto(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $this->validateMutation($request);

        return $this->respond(fn () => $this->locked(
            $item,
            fn () => $this->service->uploadPhoto(
                $request->user(),
                $item,
                $this->account((int) $validated['account_id']),
                (array) $validated['payload'],
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function reconcile(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate(['account_id' => 'required|integer']);

        return $this->respond(fn () => $this->locked(
            $item,
            fn () => $this->service->reconcile(
                $request->user(),
                $item,
                $this->account((int) $validated['account_id'])
            )
        ));
    }

    public function reviews(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'location_name' => 'required|string|max:255',
            'page_token' => 'nullable|string|max:2048',
        ]);

        return $this->respond(fn () => $this->service->reviews(
            $request->user(),
            $this->account((int) $validated['account_id']),
            (string) $validated['location_name'],
            $validated['page_token'] ?? null
        ));
    }

    public function replyToReview(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'idempotency_key' => 'required|string|max:128',
            'review_name' => 'required|string|max:500',
            'comment' => 'required|string|max:4096',
            'reply_approved' => 'required|boolean',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            fn () => $this->service->replyToReview(
                $request->user(),
                $item,
                $this->account((int) $validated['account_id']),
                (string) $validated['review_name'],
                (string) $validated['comment'],
                (bool) $validated['reply_approved'],
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function reviewHandoff(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'review.review_name' => 'required|string|max:500',
            'review.reviewer_name' => 'nullable|string|max:255',
            'review.star_rating' => 'nullable|string|max:50',
            'review.comment' => 'required|string|max:10000',
            'review.received_at' => 'nullable|date',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            fn () => $this->service->reviewHandoff(
                $request->user(),
                $item,
                $this->account((int) $validated['account_id']),
                (array) $validated['review']
            )
        ));
    }

    public function performance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'location_name' => 'required|string|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'metrics' => 'required|array|min:1|max:20',
            'metrics.*' => 'required|string|max:100',
        ]);

        return $this->respond(fn () => $this->service->performance(
            $request->user(),
            $this->account((int) $validated['account_id']),
            (string) $validated['location_name'],
            (string) $validated['start_date'],
            (string) $validated['end_date'],
            (array) $validated['metrics']
        ));
    }

    private function validateMutation(Request $request): array
    {
        return $request->validate([
            'account_id' => 'required|integer',
            'idempotency_key' => 'required|string|max:128',
            'payload' => 'required|array',
        ]);
    }

    private function account(int $accountId): SocialMediaPlatform
    {
        return SocialMediaPlatform::query()
            ->where('id', $accountId)
            ->where('user_id', Auth::id())
            ->where('platform', PlatformEnum::google_business_profile->value)
            ->firstOrFail();
    }

    private function locked(DistributionItem $item, callable $callback): mixed
    {
        $lockName = implode(':', [
            'titan-reach',
            'google-business-profile',
            Auth::id(),
            $item->getKey(),
        ]);

        try {
            return Cache::lock($lockName, 120)->block(5, function () use ($item, $callback) {
                $item->refresh();

                return $callback();
            });
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException(
                'Another request is already processing this Google Business Profile item.',
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
                'message' => trans('The Google Business Profile operation could not be completed.'),
            ], 500);
        }
    }
}
