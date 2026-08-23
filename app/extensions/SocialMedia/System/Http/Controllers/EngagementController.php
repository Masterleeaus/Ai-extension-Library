<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\EngagementGovernanceService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class EngagementController extends Controller
{
    private const SUPPORTED_PLATFORMS = [
        'facebook',
        'instagram',
        'youtube',
        'youtube-shorts',
        'linkedin',
        'x',
        'tiktok',
    ];

    public function __construct(private readonly EngagementGovernanceService $service) {}

    public function capabilities(int $account): JsonResponse
    {
        $platform = $this->account($account);

        return $this->respond(fn () => $this->service->capabilities(Auth::user(), $platform));
    }

    public function inbox(Request $request, int $account): JsonResponse
    {
        $platform = $this->account($account);
        $filters = $request->validate([
            'resource_id' => 'nullable|string|max:500',
            'video_id' => 'nullable|string|max:255',
            'target_urn' => 'nullable|string|max:1000',
            'limit' => 'nullable|integer|min:1|max:100',
            'after' => 'nullable|string|max:2048',
            'page_token' => 'nullable|string|max:2048',
            'pagination_token' => 'nullable|string|max:2048',
            'start' => 'nullable|integer|min:0|max:1000000',
        ]);

        return $this->respond(fn () => $this->service->inbox(Auth::user(), $platform, $filters));
    }

    public function proposal(Request $request, int $account): JsonResponse
    {
        $platform = $this->account($account);
        $validated = $request->validate([
            'engagement' => 'required|array',
            'engagement.engagement_id' => 'required|string|max:1000',
            'engagement.resource_id' => 'nullable|string|max:1000',
            'engagement.parent_id' => 'nullable|string|max:1000',
            'engagement.actor_id' => 'nullable|string|max:1000',
            'engagement.actor_alias' => 'nullable|string|max:255',
            'engagement.message' => 'nullable|string|max:10000',
            'engagement.received_at' => 'nullable|date',
            'engagement.provider_context' => 'nullable|array|max:20',
            'engagement.vertical' => 'nullable|string|max:100',
            'engagement.subtype' => 'nullable|string|max:100',
            'suggested_text' => 'nullable|string|max:10000',
        ]);

        return $this->respond(fn () => $this->locked(
            $platform,
            (string) data_get($validated, 'engagement.engagement_id'),
            'proposal',
            fn () => $this->service->proposeReply(
                Auth::user(),
                $platform,
                (array) $validated['engagement'],
                $validated['suggested_text'] ?? null
            )
        ));
    }

    public function reply(Request $request, int $account): JsonResponse
    {
        return $this->mutation($request, $account, 'reply', fn (SocialMediaPlatform $platform, array $validated) =>
            $this->service->sendReply(Auth::user(), $platform, $validated)
        );
    }

    public function privateReply(Request $request, int $account): JsonResponse
    {
        return $this->mutation($request, $account, 'private-reply', fn (SocialMediaPlatform $platform, array $validated) =>
            $this->service->privateReply(Auth::user(), $platform, $validated)
        );
    }

    public function edit(Request $request, int $account): JsonResponse
    {
        $platform = $this->account($account);
        $validated = $request->validate([
            'approved' => 'required|accepted',
            'reply_id' => 'required|string|max:1000',
            'reply_text' => 'required|string|max:10000',
            'object_urn' => 'nullable|string|max:1000',
            'actor_urn' => 'nullable|string|max:1000',
        ]);

        return $this->respond(fn () => $this->locked(
            $platform,
            (string) $validated['reply_id'],
            'edit',
            fn () => $this->service->editReply(Auth::user(), $platform, $validated)
        ));
    }

    public function delete(Request $request, int $account): JsonResponse
    {
        $platform = $this->account($account);
        $validated = $request->validate([
            'approved' => 'required|accepted',
            'reply_id' => 'required|string|max:1000',
            'object_urn' => 'nullable|string|max:1000',
            'actor_urn' => 'nullable|string|max:1000',
        ]);

        return $this->respond(fn () => $this->locked(
            $platform,
            (string) $validated['reply_id'],
            'delete',
            fn () => $this->service->deleteReply(Auth::user(), $platform, $validated)
        ));
    }

    public function handoff(Request $request, int $account): JsonResponse
    {
        $platform = $this->account($account);
        $validated = $request->validate([
            'engagement' => 'required|array',
            'engagement.engagement_id' => 'required|string|max:1000',
            'engagement.resource_id' => 'nullable|string|max:1000',
            'engagement.parent_id' => 'nullable|string|max:1000',
            'engagement.actor_id' => 'nullable|string|max:1000',
            'engagement.actor_alias' => 'nullable|string|max:255',
            'engagement.message' => 'nullable|string|max:10000',
            'engagement.received_at' => 'nullable|date',
            'engagement.provider_context' => 'nullable|array|max:20',
            'engagement.vertical' => 'nullable|string|max:100',
            'engagement.subtype' => 'nullable|string|max:100',
        ]);

        return $this->respond(fn () => $this->locked(
            $platform,
            (string) data_get($validated, 'engagement.engagement_id'),
            'handoff',
            fn () => $this->service->handoff(Auth::user(), $platform, (array) $validated['engagement'])
        ));
    }

    private function mutation(Request $request, int $account, string $operation, callable $callback): JsonResponse
    {
        $platform = $this->account($account);
        $validated = $request->validate([
            'approved' => 'required|accepted',
            'proposal_id' => 'required|string|max:100',
            'reply_text' => 'required|string|max:10000',
            'human_handoff_acknowledged' => 'nullable|boolean',
            'quiet_period_override' => 'nullable|boolean',
            'engagement' => 'required|array',
            'engagement.engagement_id' => 'required|string|max:1000',
            'engagement.resource_id' => 'nullable|string|max:1000',
            'engagement.parent_id' => 'nullable|string|max:1000',
            'engagement.actor_id' => 'nullable|string|max:1000',
            'engagement.actor_alias' => 'nullable|string|max:255',
            'engagement.message' => 'nullable|string|max:10000',
            'engagement.received_at' => 'nullable|date',
            'engagement.provider_context' => 'nullable|array|max:20',
            'engagement.vertical' => 'nullable|string|max:100',
            'engagement.subtype' => 'nullable|string|max:100',
            'target_urn' => 'nullable|string|max:1000',
            'object_urn' => 'nullable|string|max:1000',
            'actor_urn' => 'nullable|string|max:1000',
        ]);

        return $this->respond(fn () => $this->locked(
            $platform,
            (string) data_get($validated, 'engagement.engagement_id'),
            $operation,
            fn () => $callback($platform, $validated)
        ));
    }

    private function account(int $accountId): SocialMediaPlatform
    {
        return SocialMediaPlatform::query()
            ->where('id', $accountId)
            ->where('user_id', Auth::id())
            ->whereIn('platform', self::SUPPORTED_PLATFORMS)
            ->firstOrFail();
    }

    private function locked(
        SocialMediaPlatform $account,
        string $engagementId,
        string $operation,
        callable $callback
    ): mixed {
        $key = hash('sha256', implode('|', [
            (string) Auth::id(),
            (string) $account->getKey(),
            $operation,
            $engagementId,
        ]));

        try {
            return Cache::lock('titan-reach:engagement:http:' . $key, 120)
                ->block(5, $callback);
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException(
                'Another engagement operation is already processing this item.',
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
                'message' => trans('The engagement operation could not be completed.'),
            ], 500);
        }
    }
}
