<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\SocialMediaChannelEntitlementService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SocialMediaPlatformController extends Controller
{
    public function __construct(
        private readonly SocialMediaChannelEntitlementService $entitlements,
    ) {}

    public function __invoke()
    {
        $user = Auth::user();

        return view('social-media::platforms', [
            'platforms'     => PlatformEnum::channels(),
            'userPlatforms' => SocialMediaPlatform::query()
                ->when(request('active') === 'on', function ($query) {
                    return $query->where('expires_at', '>', now());
                })
                ->when(request('inactive') === 'on', function ($query) {
                    return $query->where('expires_at', '<', now());
                })
                ->when(request('search'), function ($query, $search) {
                    return $query->where('credentials', 'like', "%{$search}%");
                })
                ->where('user_id', Auth::id())->get(),
            'channelUsage' => $this->entitlements->usage($user),
        ]);
    }

    public function disconnect(SocialMediaPlatform $platform): ?RedirectResponse
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type'    => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        try {
            $platform->delete();

            return back()->with([
                'type'    => 'success',
                'message' => trans('Channel has been disconnected.'),
            ]);
        } catch (Exception $exception) {
            return back()->with([
                'type'    => 'error',
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
