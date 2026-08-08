<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers\Oauth;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Linkedin;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\Traits\HasBackRoute;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class LinkedinController extends Controller
{
    use HasBackRoute;

    public function __construct(public Linkedin $linkedin) {}

    private function cacheKey(): string
    {
        return 'platforms.' . Auth::id() . '.linkedin';
    }

    public function redirect(Request $request): Application|Redirector|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type' => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $this->setBackCacheRoute();

        if (setting('LINKEDIN_APP_ID') && setting('LINKEDIN_APP_SECRET')) {
            if ($request->filled('platform_id')) {
                Cache::remember($this->cacheKey(), 60, fn () => $request->get('platform_id'));
            }

            $scopes = (array) config('social-media.linkedin.scopes', []);

            if ((bool) setting('LINKEDIN_COMMUNITY_MANAGEMENT_ENABLED', false)) {
                $scopes = array_values(array_unique([
                    ...$scopes,
                    ...(array) config('social-media.linkedin.engagement_scopes', []),
                ]));
            }

            return $this->linkedin::authRedirect($scopes);
        }

        return back()->with([
            'type' => 'error',
            'message' => 'Linkedin app id and secret not set. Please contact the administrator.',
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->get('code');

        if (! $code) {
            return $this->redirectToPlatforms('error', 'Failed to get access token');
        }

        $tokenResponse = $this->linkedin->getAccessToken($code);
        $tokenData = (array) $tokenResponse->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $expiresIn = (int) ($tokenData['expires_in'] ?? 0);

        if ($tokenResponse->failed() || ! $accessToken || $expiresIn <= 0) {
            return $this->redirectToPlatforms('error', 'Failed to get access token');
        }

        $this->linkedin->setToken($accessToken);
        $accountInfo = $this->linkedin->getAccountInfo();

        if ($accountInfo->failed()) {
            return $this->redirectToPlatforms('error', 'Failed to get account info');
        }

        $userData = (array) $accountInfo->json();
        $followersCount = $this->fetchFollowersCount($userData['sub'] ?? null);
        $authorizedScopes = $this->normaliseScopes($tokenData['scope'] ?? []);
        $expiresAt = now()->addSeconds($expiresIn);
        $credentials = [
            'platform_id' => $userData['sub'] ?? null,
            'name' => $userData['name'] ?? '',
            'username' => $userData['email'] ?? '',
            'picture' => $userData['picture'] ?? '',
            'access_token' => $accessToken,
            'access_token_expire_at' => $expiresAt,
            'authorized_scopes' => $authorizedScopes,
        ];

        if (! empty($tokenData['refresh_token'])) {
            $credentials['refresh_token'] = $tokenData['refresh_token'];
            $credentials['refresh_token_expire_at'] = now()->addSeconds(
                (int) ($tokenData['refresh_token_expires_in'] ?? $expiresIn)
            );
        }

        $platformId = Cache::pull($this->cacheKey());
        $platform = $platformId && is_numeric($platformId)
            ? SocialMediaPlatform::query()
                ->where('id', $platformId)
                ->where('user_id', Auth::id())
                ->where('platform', PlatformEnum::linkedin->value)
                ->first()
            : null;

        if ($platform) {
            $platform->update([
                'credentials' => array_merge((array) $platform->credentials, $credentials),
                'connected_at' => now(),
                'expires_at' => $expiresAt,
                'followers_count' => $followersCount,
            ]);
        } else {
            SocialMediaPlatform::query()->create([
                'user_id' => Auth::id(),
                'platform' => PlatformEnum::linkedin->value,
                'credentials' => $credentials,
                'connected_at' => now(),
                'expires_at' => $expiresAt,
                'followers_count' => $followersCount,
            ]);
        }

        return $this->redirectToPlatforms('success', 'Linkedin account connected successfully.');
    }

    public function redirectToPlatforms(string $type = 'success', string $message = 'Linkedin account connected successfully.'): RedirectResponse
    {
        return to_route($this->getBackCacheRoute())->with([
            'type' => $type,
            'message' => trans($message),
        ]);
    }

    private function fetchFollowersCount(?string $memberId): int
    {
        if (! $memberId) {
            return 0;
        }

        try {
            $response = $this->linkedin->getNetworkSize($memberId);
        } catch (Throwable) {
            return 0;
        }

        return $response->failed()
            ? 0
            : (int) ($response->json('firstDegreeSize') ?? $response->json('value') ?? 0);
    }

    private function normaliseScopes(array|string $scopes): array
    {
        if (is_string($scopes)) {
            $scopes = preg_split('/[\s,]+/', trim($scopes)) ?: [];
        }

        return array_values(array_unique(array_filter(array_map('trim', $scopes))));
    }
}
