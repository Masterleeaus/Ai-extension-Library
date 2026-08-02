<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMediaAutomation\System\Http\Requests\StoreAutomationRequest;
use App\Extensions\SocialMediaAutomation\System\Models\Automation;
use App\Extensions\SocialMediaAutomation\System\Services\PlatformPermissionService;
use App\Helpers\Classes\Helper;
use App\Helpers\Classes\PlanHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AutomationController extends Controller
{
    private const SUPPORTED_PLATFORMS = ['instagram', 'facebook', 'x', 'tiktok', 'linkedin'];

    public function index(Request $request): View
    {
        $automations = collect();
        $planAllows = $this->planAllowsAutomation();

        if (Helper::appIsDemo()) {
            $automations = collect($this->demoAutomations());
        } elseif ($planAllows) {
            $automations = Automation::query()
                ->where('user_id', Auth::id())
                ->with('platform')
                ->orderByDesc('created_at')
                ->get();
        }

        return view('social-media-automation::index', [
            'automations' => $automations,
            'planAllows'  => $planAllows,
        ]);
    }

    public function create(): View
    {
        if (! $this->planAllowsAutomation()) {
            return view('social-media-automation::index', [
                'automations' => collect(),
                'planAllows'  => false,
            ]);
        }

        $accounts = SocialMediaPlatform::query()
            ->where('user_id', Auth::id())
            ->whereIn('platform', self::SUPPORTED_PLATFORMS)
            ->connected()
            ->get();

        $permissionInfo = PlatformPermissionService::getAccountPermissions($accounts);

        return view('social-media-automation::builder.index', [
            'automation'     => null,
            'accounts'       => $accounts,
            'permissionInfo' => $permissionInfo,
        ]);
    }

    public function edit(Automation $automation): View
    {
        abort_if($automation->user_id !== Auth::id(), 404);

        $automation->load(['actions', 'replies', 'platform']);

        $accounts = SocialMediaPlatform::query()
            ->where('user_id', Auth::id())
            ->whereIn('platform', self::SUPPORTED_PLATFORMS)
            ->connected()
            ->get();

        $permissionInfo = PlatformPermissionService::getAccountPermissions($accounts);

        return view('social-media-automation::builder.index', [
            'automation'     => $automation,
            'accounts'       => $accounts,
            'permissionInfo' => $permissionInfo,
        ]);
    }

    public function store(StoreAutomationRequest $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        if ($response = $this->ensureAutomationCreationAllowed()) {
            return $response;
        }

        $automation = Automation::query()->create([
            'user_id'                  => Auth::id(),
            'social_media_platform_id' => $request->social_media_platform_id,
            'name'                     => $request->name,
            'status'                   => 'draft',
            'trigger_target'           => $request->trigger_target,
            'trigger_post_id'          => $request->trigger_post_id,
            'trigger_post_data'        => $request->trigger_post_data,
            'keyword_mode'             => $request->keyword_mode,
            'include_keywords'         => $request->include_keywords,
            'exclude_keywords'         => $request->exclude_keywords,
            'enable_public_replies'    => $request->boolean('enable_public_replies'),
            'delay_seconds'            => $request->integer('delay_seconds', 0),
            'workflow_graph'           => $request->workflow_graph,
        ]);

        $this->syncActions($automation, $request->input('actions', []));
        $this->syncReplies($automation, $request->input('replies', []));

        return response()->json([
            'status'  => 'success',
            'message' => trans('Automation created successfully.'),
            'data'    => $automation->load(['actions', 'replies']),
        ]);
    }

    public function update(StoreAutomationRequest $request, Automation $automation): JsonResponse
    {
        abort_if($automation->user_id !== Auth::id(), 404);

        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $automation->update([
            'social_media_platform_id' => $request->social_media_platform_id,
            'name'                     => $request->name,
            'trigger_target'           => $request->trigger_target,
            'trigger_post_id'          => $request->trigger_post_id,
            'trigger_post_data'        => $request->trigger_post_data,
            'keyword_mode'             => $request->keyword_mode,
            'include_keywords'         => $request->include_keywords,
            'exclude_keywords'         => $request->exclude_keywords,
            'enable_public_replies'    => $request->boolean('enable_public_replies'),
            'delay_seconds'            => $request->integer('delay_seconds', 0),
            'workflow_graph'           => $request->workflow_graph,
        ]);

        $this->syncActions($automation, $request->input('actions', []));
        $this->syncReplies($automation, $request->input('replies', []));

        return response()->json([
            'status'  => 'success',
            'message' => trans('Automation updated successfully.'),
            'data'    => $automation->load(['actions', 'replies']),
        ]);
    }

    public function toggleStatus(Automation $automation): JsonResponse
    {
        abort_if($automation->user_id !== Auth::id(), 404);

        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $newStatus = match ($automation->status) {
            'draft', 'paused' => 'live',
            'live'            => 'paused',
            default           => $automation->status,
        };

        $automation->update(['status' => $newStatus]);

        return response()->json([
            'status'  => 'success',
            'message' => trans('Automation status updated.'),
            'data'    => ['status' => $newStatus],
        ]);
    }

    public function destroy(Automation $automation): JsonResponse
    {
        abort_if($automation->user_id !== Auth::id(), 404);

        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $automation->delete();

        return response()->json([
            'status'  => 'success',
            'message' => trans('Automation deleted successfully.'),
        ]);
    }

    private function planAllowsAutomation(): bool
    {
        $plan = PlanHelper::userPlan();

        return PlanHelper::planMenuCheck($plan, 'ext_social_media_automation');
    }

    private function getAutomationLimitValue(string $key): int
    {
        $plan = Auth::user()?->relationPlan;
        $limits = (array) ($plan?->social_media_automation_limits ?? []);
        $value = $limits[$key] ?? -1;

        return is_numeric($value) ? (int) $value : -1;
    }

    private function ensureAutomationCreationAllowed(): ?JsonResponse
    {
        if (! $this->planAllowsAutomation()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('Your current plan does not include automation. Please upgrade your plan.'),
            ], 422);
        }

        $limit = $this->getAutomationLimitValue('automations');

        if ($limit === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('Your current plan does not allow creating automations.'),
            ], 422);
        }

        if ($limit > 0) {
            $count = Automation::query()
                ->where('user_id', Auth::id())
                ->count();

            if ($count >= $limit) {
                return response()->json([
                    'status'  => 'error',
                    'message' => trans('You have reached the maximum number of automations included in your plan.'),
                ], 422);
            }
        }

        return null;
    }

    private function syncActions(Automation $automation, array $actions): void
    {
        $automation->actions()->delete();

        foreach ($actions as $index => $action) {
            $automation->actions()->create([
                'type'    => $action['type'],
                'content' => $action['content'],
                'order'   => $index,
            ]);
        }
    }

    private function syncReplies(Automation $automation, array $replies): void
    {
        $automation->replies()->delete();

        foreach ($replies ?? [] as $reply) {
            if (! empty($reply['content'])) {
                $automation->replies()->create([
                    'content' => $reply['content'],
                ]);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function demoAutomations(): array
    {
        return [
            [
                'id'            => 1,
                'name'          => '50% off First Month for Pro Plans',
                'status'        => 'live',
                'created_at'    => 'May 23, 2022, 12:23',
                'platform_name' => 'instagram',
                'account_name'  => 'Jane Doe',
                'platform'      => (object) ['username' => 'Jane Doe', 'platform' => 'instagram'],
            ],
            [
                'id'            => 2,
                'name'          => 'Comment to DM — Product Launch',
                'status'        => 'live',
                'created_at'    => 'May 23, 2022, 12:23',
                'platform_name' => 'instagram',
                'account_name'  => 'John Smith',
                'platform'      => (object) ['username' => 'John Smith', 'platform' => 'instagram'],
            ],
            [
                'id'            => 3,
                'name'          => 'Use "Diet" Code to get my free eBook',
                'status'        => 'live',
                'created_at'    => 'May 23, 2022, 12:23',
                'platform_name' => 'facebook',
                'account_name'  => 'Emily Johnson',
                'platform'      => (object) ['username' => 'Emily Johnson', 'platform' => 'facebook'],
            ],
            [
                'id'            => 4,
                'name'          => 'SEND DM 50% Off First Year',
                'status'        => 'live',
                'created_at'    => 'May 23, 2022, 12:23',
                'platform_name' => 'x',
                'account_name'  => 'Michael Smith',
                'platform'      => (object) ['username' => 'Michael Smith', 'platform' => 'x'],
            ],
            [
                'id'            => 5,
                'name'          => 'Summer Sale',
                'status'        => 'expired',
                'created_at'    => 'May 23, 2022, 12:23',
                'platform_name' => 'tiktok',
                'account_name'  => 'Ava Brown',
                'platform'      => (object) ['username' => 'Ava Brown', 'platform' => 'tiktok'],
            ],
            [
                'id'            => 6,
                'name'          => 'Cyber Monday',
                'status'        => 'expired',
                'created_at'    => 'May 23, 2022, 12:23',
                'platform_name' => 'linkedin',
                'account_name'  => 'Sophia Davis',
                'platform'      => (object) ['username' => 'Sophia Davis', 'platform' => 'linkedin'],
            ],
        ];
    }
}
