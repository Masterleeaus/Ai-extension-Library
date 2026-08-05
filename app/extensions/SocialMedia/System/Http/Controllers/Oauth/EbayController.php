<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers\Oauth;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Ebay;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\Traits\HasBackRoute;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class EbayController extends Controller
{
    use HasBackRoute;

    public function __construct(public Ebay $ebay) {}

    public function redirect(Request $request): RedirectResponse
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type'    => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        if (! setting('EBAY_CLIENT_ID') || ! setting('EBAY_CLIENT_SECRET') || ! setting('EBAY_REDIRECT_URI')) {
            return back()->with([
                'type'    => 'error',
                'message' => trans('eBay client ID, client secret and redirect URI must be configured.'),
            ]);
        }

        $this->setBackCacheRoute();
        $state = Str::random(64);

        Cache::put($this->oauthCacheKey(), [
            'state'       => $state,
            'platform_id' => $request->integer('platform_id') ?: null,
        ], now()->addMinutes(10));

        return $this->ebay->authRedirect($state);
    }

    public function callback(Request $request): RedirectResponse
    {
        $cached = Cache::pull($this->oauthCacheKey());
        $returnedState = (string) $request->get('state', '');
        $expectedState = (string) ($cached['state'] ?? '');

        if ($returnedState === '' || $expectedState === '' || ! hash_equals($expectedState, $returnedState)) {
            return $this->redirectToPlatforms('error', 'The eBay authorization state was invalid or expired.');
        }

        $code = (string) $request->get('code', '');

        if ($code === '') {
            return $this->redirectToPlatforms('error', 'eBay did not return an authorization code.');
        }

        try {
            $tokenResponse = $this->ebay->getAccessToken($code);
            $accessToken = (string) $tokenResponse->json('access_token', '');
            $refreshToken = (string) $tokenResponse->json('refresh_token', '');

            if ($tokenResponse->failed() || $accessToken === '' || $refreshToken === '') {
                return $this->redirectToPlatforms('error', 'Failed to obtain eBay seller authorization.');
            }

            $credentials = [
                'platform_id'                  => 'ebay-' . substr(hash('sha256', $refreshToken), 0, 24),
                'name'                         => 'eBay Seller',
                'username'                     => (string) config('social-media.ebay.marketplace_id', 'EBAY_AU'),
                'environment'                  => $this->ebay->environment(),
                'marketplace_id'               => (string) config('social-media.ebay.marketplace_id', 'EBAY_AU'),
                'access_token_encrypted'       => Crypt::encryptString($accessToken),
                'access_token_expires_at'      => now()
                    ->addSeconds((int) $tokenResponse->json('expires_in', 7200))
                    ->toIso8601String(),
                'refresh_token_encrypted'      => Crypt::encryptString($refreshToken),
                'refresh_token_expires_at'     => now()
                    ->addSeconds((int) $tokenResponse->json('refresh_token_expires_in', 47304000))
                    ->toIso8601String(),
                'authorized_scopes'            => config('social-media.ebay.scopes', []),
            ];

            $platform = $this->resolvePlatform($cached['platform_id'] ?? null);
            $refreshExpiry = now()->addSeconds((int) $tokenResponse->json('refresh_token_expires_in', 47304000));

            if ($platform) {
                $platform->update([
                    'credentials'     => $credentials,
                    'connected_at'    => now(),
                    'expires_at'      => $refreshExpiry,
                    'followers_count' => 0,
                ]);
            } else {
                $platform = SocialMediaPlatform::query()->create([
                    'user_id'         => Auth::id(),
                    'platform'        => PlatformEnum::ebay->value,
                    'credentials'     => $credentials,
                    'connected_at'    => now(),
                    'expires_at'      => $refreshExpiry,
                    'followers_count' => 0,
                ]);
            }

            $privileges = $this->ebay->getPrivileges($platform);
            $credentials = (array) $platform->credentials;
            $credentials['readiness_checked_at'] = now()->toIso8601String();
            $credentials['privileges_available'] = $privileges->successful();
            $platform->update(['credentials' => $credentials]);

            return $this->redirectToPlatforms('success', 'eBay seller account connected successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToPlatforms('error', 'The eBay seller account could not be connected.');
        }
    }

    private function resolvePlatform(?int $platformId): ?SocialMediaPlatform
    {
        if (! $platformId) {
            return null;
        }

        return SocialMediaPlatform::query()
            ->where('id', $platformId)
            ->where('user_id', Auth::id())
            ->where('platform', PlatformEnum::ebay->value)
            ->first();
    }

    private function oauthCacheKey(): string
    {
        return 'platforms.' . Auth::id() . '.ebay.oauth';
    }

    private function redirectToPlatforms(string $type, string $message): RedirectResponse
    {
        return to_route($this->getBackCacheRoute())->with([
            'type'    => $type,
            'message' => trans($message),
        ]);
    }
}
