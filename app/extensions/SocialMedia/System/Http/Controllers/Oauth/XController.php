<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers\Oauth;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\X;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\Traits\HasBackRoute;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class XController extends Controller
{
    use HasBackRoute;

    public function __construct(public X $x) {}

    private function cacheKey(): string
    {
        return 'platforms.' . Auth::id() . '.x';
    }

    public function redirect(Request $request)
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type' => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $this->setBackCacheRoute();

        if (setting('X_CLIENT_ID') && setting('X_CLIENT_SECRET')) {
            if ($request->filled('platform_id')) {
                Cache::remember($this->cacheKey(), 60, fn () => $request->get('platform_id'));
            }

            return $this->x->authRedirect();
        }

        return back()->with([
            'type' => 'error',
            'message' => 'X app id and secret not set. Please contact the administrator.',
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->get('code');

        if (! $code) {
            return $this->failure();
        }

        $response = $this->x->getAccessToken($code)->throw();
        $this->setPlatformInfo((array) $response->json());

        return to_route($this->getBackCacheRoute())->with([
            'type' => 'success',
            'message' => 'X account connected successfully.',
        ]);
    }

    protected function setPlatformInfo(array $tokenData): void
    {
        $accessToken = (string) ($tokenData['access_token'] ?? '');

        if ($accessToken === '') {
            return;
        }

        $this->x->setToken($accessToken);
        $userData = (array) $this->x->getUserInfo()->throw()->json('data', []);
        $followersCount = (int) data_get($userData, 'public_metrics.followers_count', 0);
        $expiresAt = now()->addSeconds((int) ($tokenData['expires_in'] ?? 7200));
        $credentials = [
            'platform_id' => $userData['id'] ?? null,
            'name' => $userData['name'] ?? '',
            'picture' => $userData['profile_image_url'] ?? '',
            'username' => $userData['username'] ?? '',
            'access_token' => $accessToken,
            'access_token_expire_at' => $expiresAt,
            'authorized_scopes' => $this->normaliseScopes($tokenData['scope'] ?? []),
            'type' => 'user',
        ];

        if (! empty($tokenData['refresh_token'])) {
            $credentials['refresh_token'] = $tokenData['refresh_token'];
            $credentials['refresh_token_expire_at'] = now()->addMonths(6);
        }

        $platformId = Cache::pull($this->cacheKey());
        $platform = $platformId && is_numeric($platformId)
            ? SocialMediaPlatform::query()
                ->where('id', $platformId)
                ->where('user_id', Auth::id())
                ->where('platform', PlatformEnum::x->value)
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
                'platform' => PlatformEnum::x->value,
                'credentials' => $credentials,
                'connected_at' => now(),
                'expires_at' => $expiresAt,
                'followers_count' => $followersCount,
            ]);
        }
    }

    private function normaliseScopes(array|string $scopes): array
    {
        if (is_string($scopes)) {
            $scopes = preg_split('/[\s,]+/', trim($scopes)) ?: [];
        }

        return array_values(array_unique(array_filter(array_map('trim', $scopes))));
    }

    private function failure(): RedirectResponse
    {
        return to_route($this->getBackCacheRoute())->with([
            'type' => 'error',
            'message' => 'Something went wrong, please try again.',
        ]);
    }
}
