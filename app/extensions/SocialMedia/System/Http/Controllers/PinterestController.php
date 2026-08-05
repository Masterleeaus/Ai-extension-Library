<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\PinterestService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PinterestController extends Controller
{
    public function __construct(private readonly PinterestService $service) {}

    public function readiness(Request $request): JsonResponse
    {
        $validated = $request->validate(['account_id' => 'required|integer']);

        return $this->respond(fn () => $this->service->readiness(
            $request->user(),
            $this->account((int) $validated['account_id'])
        ));
    }

    public function boards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'bookmark' => 'nullable|string|max:2048',
        ]);

        return $this->respond(fn () => $this->service->boards(
            $request->user(),
            $this->account((int) $validated['account_id']),
            $validated['bookmark'] ?? null
        ));
    }

    public function publish(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'idempotency_key' => 'required|string|max:128',
            'payload' => 'required|array',
        ]);

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

    public function analytics(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'metrics' => 'required|array|min:1|max:20',
            'metrics.*' => 'required|string|max:100',
        ]);

        return $this->respond(fn () => $this->service->analytics(
            $request->user(),
            $item,
            $this->account((int) $validated['account_id']),
            (string) $validated['start_date'],
            (string) $validated['end_date'],
            (array) $validated['metrics']
        ));
    }

    public function engagementHandoff(Request $request, DistributionItem $item): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|integer',
            'engagement.engagement_id' => 'required|string|max:255',
            'engagement.actor_alias' => 'nullable|string|max:255',
            'engagement.message' => 'required|string|max:10000',
            'engagement.received_at' => 'nullable|date',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            fn () => $this->service->engagementHandoff(
                $request->user(),
                $item,
                $this->account((int) $validated['account_id']),
                (array) $validated['engagement']
            )
        ));
    }

    private function account(int $accountId): SocialMediaPlatform
    {
        return SocialMediaPlatform::query()
            ->where('id', $accountId)
            ->where('user_id', Auth::id())
            ->where('platform', PlatformEnum::pinterest->value)
            ->firstOrFail();
    }

    private function locked(DistributionItem $item, callable $callback): mixed
    {
        $lockName = implode(':', [
            'titan-reach',
            'pinterest',
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
                'Another request is already processing this Pinterest item.',
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
                'message' => trans('The Pinterest operation could not be completed.'),
            ], 500);
        }
    }
}
