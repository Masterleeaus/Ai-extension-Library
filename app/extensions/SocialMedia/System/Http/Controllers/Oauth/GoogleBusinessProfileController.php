<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers\Oauth;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\GoogleBusinessProfile;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\Traits\HasBackRoute;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class GoogleBusinessProfileController extends Controller
{
    use HasBackRoute;

    public function __construct(private readonly GoogleBusinessProfile $google) {}

    public function redirect(Request $request): RedirectResponse
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type' => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        if (! setting('GOOGLE_BUSINESS_PROFILE_CLIENT_ID')
            || ! setting('GOOGLE_BUSINESS_PROFILE_CLIENT_SECRET')
            || ! setting('GOOGLE_BUSINESS_PROFILE_REDIRECT_URI')) {
            return back()->with([
                'type' => 'error',
                'message' => trans('Google Business Profile OAuth settings must be configured.'),
            ]);
        }

        $this->setBackCacheRoute();
        $state = Str::random(64);

        Cache::put($this->oauthCacheKey(), [
            'state' => $state,
            'user_id' => Auth::id(),
            'platform_id' => $request->integer('platform_id') ?: null,
        ], now()->addMinutes(10));

        return redirect()->away($this->google->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $cached = Cache::pull($this->oauthCacheKey());
        $returnedState = (string) $request->get('state', '');
        $expectedState = (string) ($cached['state'] ?? '');

        if ($returnedState === ''
            || $expectedState === ''
            || ! hash_equals($expectedState, $returnedState)
            || (int) ($cached['user_id'] ?? 0) !== (int) Auth::id()) {
            return $this->redirectToPlatforms(
                'error',
                'The Google Business Profile authorization state was invalid or expired.'
            );
        }

        $code = trim((string) $request->get('code', ''));

        if ($code === '') {
            return $this->redirectToPlatforms(
                'error',
                'Google did not return an authorization code.'
            );
        }

        try {
            $tokenResponse = $this->google->exchangeCode($code);
            $accessToken = trim((string) $tokenResponse->json('access_token', ''));

            if ($tokenResponse->failed() || $accessToken === '') {
                return $this->redirectToPlatforms(
                    'error',
                    'Failed to obtain Google Business Profile authorization.'
                );
            }

            $existing = $this->resolvePlatform($cached['platform_id'] ?? null);
            $existingCredentials = (array) ($existing?->credentials ?? []);
            $refreshToken = trim((string) $tokenResponse->json('refresh_token', ''));
            $accessExpiresAt = now()->addSeconds((int) $tokenResponse->json('expires_in', 3600));
            $authorizedScopes = $this->normaliseScopes(
                (string) $tokenResponse->json('scope', ''),
                (array) config('social-media.google_business_profile.scopes', [])
            );

            $temporaryCredentials = [
                'access_token_encrypted' => Crypt::encryptString($accessToken),
                'access_token_expires_at' => $accessExpiresAt->toIso8601String(),
            ];

            if ($refreshToken !== '') {
                $temporaryCredentials['refresh_token_encrypted'] = Crypt::encryptString($refreshToken);
            } elseif (! empty($existingCredentials['refresh_token_encrypted'])) {
                $temporaryCredentials['refresh_token_encrypted'] =
                    $existingCredentials['refresh_token_encrypted'];
            }

            $temporary = new SocialMediaPlatform([
                'user_id' => Auth::id(),
                'platform' => PlatformEnum::google_business_profile->value,
                'credentials' => $temporaryCredentials,
                'connected_at' => now(),
                'expires_at' => $accessExpiresAt,
            ]);

            $accountsResponse = $this->google->accounts($temporary);

            if ($accountsResponse->failed()) {
                return $this->redirectToPlatforms(
                    'error',
                    'Google Business Profile accounts could not be discovered.'
                );
            }

            $accounts = array_slice(
                array_values((array) $accountsResponse->json('accounts', [])),
                0,
                20
            );
            $locations = [];

            foreach ($accounts as $account) {
                $accountName = trim((string) ($account['name'] ?? ''));

                if ($accountName === '' || count($locations) >= 100) {
                    continue;
                }

                $locationsResponse = $this->google->locations($temporary, $accountName);

                if ($locationsResponse->successful()) {
                    foreach ((array) $locationsResponse->json('locations', []) as $location) {
                        $locations[] = $this->locationSummary($accountName, (array) $location);

                        if (count($locations) >= 100) {
                            break;
                        }
                    }
                }
            }

            $manageScope = 'https://www.googleapis.com/auth/business.manage';
            $manageGranted = in_array($manageScope, $authorizedScopes, true);
            $capabilities = [
                'account_discovery' => $accounts !== [],
                'location_discovery' => $locations !== [],
                'publish' => $manageGranted && $locations !== [],
                'photos' => $manageGranted && $locations !== [],
                'reviews' => $manageGranted && $locations !== [],
                'review_reply' => $manageGranted && $locations !== [],
                'analytics' => $manageGranted && $locations !== [],
                'status_reconciliation' => $manageGranted && $locations !== [],
            ];
            $firstAccount = (array) ($accounts[0] ?? []);
            $firstLocation = (array) ($locations[0] ?? []);
            $refreshExpiry = isset($existingCredentials['refresh_token_expires_at'])
                ? Carbon::parse((string) $existingCredentials['refresh_token_expires_at'])
                : now()->addYear();

            $credentials = [
                ...$existingCredentials,
                ...$temporaryCredentials,
                'platform_id' => 'gbp-' . substr(hash(
                    'sha256',
                    Auth::id() . ':' . json_encode(array_column($accounts, 'name'))
                ), 0, 24),
                'name' => (string) ($firstAccount['accountName'] ?? 'Google Business Profile'),
                'username' => (string) ($firstLocation['title'] ?? $firstAccount['name'] ?? 'Google Business Profile'),
                'account_names' => array_values(array_filter(array_map(
                    static fn (array $account): ?string => isset($account['name'])
                        ? (string) $account['name']
                        : null,
                    $accounts
                ))),
                'locations' => $locations,
                'selected_location_name' => $firstLocation['v4_name'] ?? null,
                'authorized_scopes' => $authorizedScopes,
                'provider_capabilities' => $capabilities,
                'token_type' => (string) $tokenResponse->json('token_type', 'Bearer'),
                'refresh_token_expires_at' => $refreshExpiry->toIso8601String(),
                'health' => [
                    'status' => $locations !== [] ? 'healthy' : 'limited',
                    'checked_at' => now()->toIso8601String(),
                    'accounts_count' => count($accounts),
                    'locations_count' => count($locations),
                    'rate_limit' => $this->google->rateLimit($accountsResponse),
                ],
            ];

            if ($existing) {
                $existing->update([
                    'credentials' => $credentials,
                    'connected_at' => now(),
                    'expires_at' => ! empty($credentials['refresh_token_encrypted'])
                        ? $refreshExpiry
                        : $accessExpiresAt,
                    'followers_count' => 0,
                ]);
            } else {
                SocialMediaPlatform::query()->create([
                    'user_id' => Auth::id(),
                    'platform' => PlatformEnum::google_business_profile->value,
                    'credentials' => $credentials,
                    'connected_at' => now(),
                    'expires_at' => ! empty($credentials['refresh_token_encrypted'])
                        ? $refreshExpiry
                        : $accessExpiresAt,
                    'followers_count' => 0,
                ]);
            }

            return $this->redirectToPlatforms(
                'success',
                'Google Business Profile account connected successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToPlatforms(
                'error',
                'The Google Business Profile account could not be connected.'
            );
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
            ->where('platform', PlatformEnum::google_business_profile->value)
            ->first();
    }

    private function normaliseScopes(string $returned, array $fallback): array
    {
        $scopes = preg_split('/[\s,]+/', trim($returned)) ?: [];

        return array_values(array_unique(array_filter(
            $scopes !== [] ? $scopes : $fallback,
            static fn ($scope): bool => is_string($scope) && trim($scope) !== ''
        )));
    }

    private function locationSummary(string $accountName, array $location): array
    {
        $locationName = trim((string) ($location['name'] ?? ''), '/');
        $locationId = str_starts_with($locationName, 'locations/')
            ? substr($locationName, strlen('locations/'))
            : '';

        return [
            'name' => $locationName !== '' ? $locationName : null,
            'account_name' => $accountName,
            'v4_name' => $locationId !== ''
                ? trim($accountName, '/') . '/locations/' . $locationId
                : null,
            'title' => $location['title'] ?? null,
            'store_code' => $location['storeCode'] ?? null,
            'website_uri' => $location['websiteUri'] ?? null,
            'primary_category' => data_get($location, 'categories.primaryCategory.displayName'),
            'place_id' => data_get($location, 'metadata.placeId'),
            'maps_uri' => data_get($location, 'metadata.mapsUri'),
            'can_operate_local_post' => data_get($location, 'metadata.canOperateLocalPost'),
            'can_have_food_menus' => data_get($location, 'metadata.canHaveFoodMenus'),
        ];
    }

    private function oauthCacheKey(): string
    {
        return 'platforms.' . Auth::id() . '.google-business-profile.oauth';
    }

    private function redirectToPlatforms(string $type, string $message): RedirectResponse
    {
        return to_route($this->getBackCacheRoute())->with([
            'type' => $type,
            'message' => trans($message),
        ]);
    }
}
