<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\GoogleBusinessProfileResourceGuard;
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
    public function __construct(
        private readonly GoogleBusinessProfileService $service,
        private readonly GoogleBusinessProfileResourceGuard $resources
    ) {}

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
        $this->assertOwnedItem($item);
        $validated = $this->validateMutation($request);
        $account = $this->account((int) $validated['account_id']);

        return $this->respond(function () use ($request, $item, $account, $validated) {
            $payload = (array) $validated['payload'];
            $resources = $this->resources->resolveLocation(
                $account,
                (string) $payload['account_name'],
                (string) $payload['location_name'],
                true
            );
            $payload['location_name'] = $resources['account_location_name'];

            return $this->locked(
                $item,
                fn () => $this->service->publish(
                    $request->user(),
                    $item,
                    $account,
                    $payload,
                    (string) $validated['idempotency_key']
                )
            );
        });
    }

    public function uploadPhoto(Request $request, DistributionItem $item): JsonResponse
    {
        $this->assertOwnedItem($item);
        $validated = $this->validateMutation($request);
        $account = $this->account((int) $validated['account_id']);

        return $this->respond(function () use ($request, $item, $account, $validated) {
            $payload = (array) $validated['payload'];
            $resources = $this->resources->resolveLocation(
                $account,
                (string) $payload['account_name'],
                (string) $payload['location_name']
            );
            $payload['location_name'] = $resources['account_location_name'];

            return $this->locked(
                $item,
                fn () => $this->service->uploadPhoto(
                    $request->user(),
                    $item,
                    $account,
                    $payload,
                    (string) $validated['idempotency_key']
                )
            );
        });
    }

    public function reconcile(Request $request, DistributionItem $item): JsonResponse
    {
        $this->assertOwnedItem($item);
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'account_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:500',
        ]);
        $account = $this->account((int) $validated['account_id']);

        return $this->respond(function () use ($request, $item, $account, $validated) {
            $resources = $this->resources->resolveLocation(
                $account,
                (string) $validated['account_name'],
                (string) $validated['location_name']
            );
            $this->resources->assertLocalPostName(
                $resources,
                (string) data_get($item->payload, 'google_business_profile.local_post_name', '')
            );

            return $this->locked(
                $item,
                fn () => $this->service->reconcile(
                    $request->user(),
                    $item,
                    $account
                )
            );
        });
    }

    public function reviews(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'account_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:500',
            'page_token' => 'nullable|string|max:2048',
        ]);
        $account = $this->account((int) $validated['account_id']);

        return $this->respond(function () use ($request, $account, $validated) {
            $resources = $this->resources->resolveLocation(
                $account,
                (string) $validated['account_name'],
                (string) $validated['location_name']
            );

            return $this->service->reviews(
                $request->user(),
                $account,
                (string) $resources['account_location_name'],
                $validated['page_token'] ?? null
            );
        });
    }

    public function replyToReview(Request $request, DistributionItem $item): JsonResponse
    {
        $this->assertOwnedItem($item);
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'account_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:500',
            'idempotency_key' => 'required|string|max:128',
            'review_name' => 'required|string|max:500',
            'comment' => 'required|string|max:4096',
            'reply_approved' => 'required|boolean',
        ]);
        $account = $this->account((int) $validated['account_id']);

        return $this->respond(function () use ($request, $item, $account, $validated) {
            $resources = $this->resources->resolveLocation(
                $account,
                (string) $validated['account_name'],
                (string) $validated['location_name']
            );
            $reviewName = $this->resources->assertReviewName(
                $resources,
                (string) $validated['review_name']
            );

            return $this->locked(
                $item,
                fn () => $this->service->replyToReview(
                    $request->user(),
                    $item,
                    $account,
                    $reviewName,
                    (string) $validated['comment'],
                    (bool) $validated['reply_approved'],
                    (string) $validated['idempotency_key']
                )
            );
        });
    }

    public function reviewHandoff(Request $request, DistributionItem $item): JsonResponse
    {
        $this->assertOwnedItem($item);
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'account_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:500',
            'review.review_name' => 'required|string|max:500',
            'review.reviewer_name' => 'nullable|string|max:255',
            'review.star_rating' => 'nullable|string|max:50',
            'review.comment' => 'required|string|max:10000',
            'review.received_at' => 'nullable|date',
        ]);
        $account = $this->account((int) $validated['account_id']);

        return $this->respond(function () use ($request, $item, $account, $validated) {
            $resources = $this->resources->resolveLocation(
                $account,
                (string) $validated['account_name'],
                (string) $validated['location_name']
            );
            $review = (array) $validated['review'];
            $review['review_name'] = $this->resources->assertReviewName(
                $resources,
                (string) $review['review_name']
            );

            return $this->locked(
                $item,
                fn () => $this->service->reviewHandoff(
                    $request->user(),
                    $item,
                    $account,
                    $review
                )
            );
        });
    }

    public function performance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'account_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:500',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'metrics' => 'required|array|min:1|max:20',
            'metrics.*' => 'required|string|max:100',
        ]);
        $account = $this->account((int) $validated['account_id']);

        return $this->respond(function () use ($request, $account, $validated) {
            $resources = $this->resources->resolveLocation(
                $account,
                (string) $validated['account_name'],
                (string) $validated['location_name']
            );

            return $this->service->performance(
                $request->user(),
                $account,
                (string) $resources['performance_location_name'],
                (string) $validated['start_date'],
                (string) $validated['end_date'],
                (array) $validated['metrics']
            );
        });
    }

    private function validateMutation(Request $request): array
    {
        return $request->validate([
            'account_id' => 'required|integer',
            'idempotency_key' => 'required|string|max:128',
            'payload' => 'required|array',
            'payload.account_name' => 'required|string|max:255',
            'payload.location_name' => 'required|string|max:500',
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

    private function assertOwnedItem(DistributionItem $item): void
    {
        abort_if((int) $item->user_id !== (int) Auth::id(), 404);
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
                $this->assertOwnedItem($item);

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
