<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers\Oauth;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Pinterest;
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

class PinterestController extends Controller
{
    use HasBackRoute;

    public function __construct(private readonly Pinterest $pinterest) {}

    public function redirect(Request $request): RedirectResponse
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type' => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        if (! (setting('PINTEREST_CLIENT_ID') ?: config('social-media.pinterest.client_id'))
            || ! (setting('PINTEREST_CLIENT_SECRET') ?: config('social-media.pinterest.client_secret'))
            || ! (setting('PINTEREST_REDIRECT_URI') ?: config('social-media.pinterest.redirect_uri'))) {
            return back()->with([
                'type' => 'error',
                'message' => trans('Pinterest OAuth settings must be configured.'),
            ]);
        }

        $this->setBackCacheRoute();
        $state = Str::random(64);

        Cache::put($this->oauthCacheKey(), [
            'state' => $state,
            'user_id' => Auth::id(),
            'platform_id' => $request->integer('platform_id') ?: null,
        ], now()->addMinutes(10));

        return redirect()->away($this->pinterest->authorizationUrl($state));
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
                'The Pinterest authorization state was invalid or expired.'
            );
        }

        $code = trim((string) $request->get('code', ''));

        if ($code === '') {
            return $this->redirectToPlatforms(
                'error',
                'Pinterest did not return an authorization code.'
            );
        }

        try {
            $tokenResponse = $this->pinterest->exchangeCode($code);
            $accessToken = trim((string) $tokenResponse->json('access_token', ''));

            if ($tokenResponse->failed() || $accessToken === '') {
                return $this->redirectToPlatforms(
                    'error',
                    'Failed to obtain Pinterest authorization.'
                );
            }

            $existing = $this->resolvePlatform($cached['platform_id'] ?? null);
            $existingCredentials = (array) ($existing?->credentials ?? []);
            $refreshToken = trim((string) $tokenResponse->json('refresh_token', ''));
            $accessExpiresAt = now()->addSeconds((int) $tokenResponse->json('expires_in', 2592000));
            $authorizedScopes = $this->normaliseScopes(
                (string) $tokenResponse->json('scope', ''),
                (array) ($existingCredentials['authorized_scopes'] ?? [])
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
                'platform' => PlatformEnum::pinterest->value,
                'credentials' => $temporaryCredentials,
                'connected_at' => now(),
                'expires_at' => $accessExpiresAt,
            ]);

            $userResponse = $this->pinterest->userAccount($temporary);
            $boardsResponse = $this->pinterest->boards($temporary);

            if ($userResponse->failed()) {
                return $this->redirectToPlatforms(
                    'error',
                    'The Pinterest user account could not be discovered.'
                );
            }

            $userData = (array) $userResponse->json();
            $boards = $boardsResponse->successful()
                ? array_slice(array_values(array_filter(
                    (array) $boardsResponse->json('items', []),
                    static fn ($board): bool => is_array($board)
                        && strtoupper((string) ($board['privacy'] ?? 'PUBLIC')) !== 'SECRET'
                )), 0, 100)
                : [];
            $scope = static fn (string $required): bool => in_array(
                $required,
                $authorizedScopes,
                true
            );
            $capabilities = [
                'account_discovery' => $scope('user_accounts:read'),
                'boards' => $scope('boards:read') && $boardsResponse->successful(),
                'board_write' => $scope('boards:write'),
                'publish' => $scope('pins:write') && $boards !== [],
                'analytics' => $scope('pins:read'),
                'status_reconciliation' => $scope('pins:read'),
                'product_links' => $scope('pins:write'),
            ];
            $refreshExpiry = $tokenResponse->json('refresh_token_expires_in')
                ? now()->addSeconds((int) $tokenResponse->json('refresh_token_expires_in'))
                : (isset($existingCredentials['refresh_token_expires_at'])
                    ? Carbon::parse((string) $existingCredentials['refresh_token_expires_at'])
                    : now()->addDays(60));
            $userId = trim((string) ($userData['id'] ?? ''));
            $username = (string) ($userData['username'] ?? 'Pinterest');

            $credentials = [
                ...$existingCredentials,
                ...$temporaryCredentials,
                'platform_id' => $userId !== ''
                    ? $userId
                    : 'pinterest-' . substr(hash('sha256', Auth::id() . ':' . $username), 0, 24),
                'name' => (string) ($userData['business_name'] ?? $username),
                'username' => $username,
                'account_type' => $userData['account_type'] ?? null,
                'website_url' => $userData['website_url'] ?? null,
                'profile_image' => $userData['profile_image'] ?? null,
                'boards' => array_map(
                    static fn (array $board): array => [
                        'id' => $board['id'] ?? null,
                        'name' => $board['name'] ?? null,
                        'privacy' => $board['privacy'] ?? null,
                        'pin_count' => $board['pin_count'] ?? null,
                    ],
                    $boards
                ),
                'authorized_scopes' => $authorizedScopes,
                'provider_capabilities' => $capabilities,
                'token_type' => (string) $tokenResponse->json('token_type', 'Bearer'),
                'refresh_token_expires_at' => $refreshExpiry->toIso8601String(),
                'health' => [
                    'status' => $capabilities['publish'] ? 'healthy' : 'limited',
                    'checked_at' => now()->toIso8601String(),
                    'boards_count' => count($boards),
                    'rate_limit' => $this->pinterest->rateLimit($boardsResponse),
                ],
            ];

            if ($tokenResponse->json('refresh_token')) {
                $credentials['refresh_token_encrypted'] = Crypt::encryptString(
                    (string) $tokenResponse->json('refresh_token')
                );
            }

            if ($existing) {
                $existing->update([
                    'credentials' => $credentials,
                    'connected_at' => now(),
                    'expires_at' => ! empty($credentials['refresh_token_encrypted'])
                        ? $refreshExpiry
                        : $accessExpiresAt,
                    'followers_count' => (int) ($userData['follower_count'] ?? 0),
                ]);
            } else {
                SocialMediaPlatform::query()->create([
                    'user_id' => Auth::id(),
                    'platform' => PlatformEnum::pinterest->value,
                    'credentials' => $credentials,
                    'connected_at' => now(),
                    'expires_at' => ! empty($credentials['refresh_token_encrypted'])
                        ? $refreshExpiry
                        : $accessExpiresAt,
                    'followers_count' => (int) ($userData['follower_count'] ?? 0),
                ]);
            }

            return $this->redirectToPlatforms(
                'success',
                'Pinterest account connected successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToPlatforms(
                'error',
                'The Pinterest account could not be connected.'
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
            ->where('platform', PlatformEnum::pinterest->value)
            ->first();
    }

    private function normaliseScopes(string $returned, array $verifiedFallback): array
    {
        $scopes = preg_split('/[\s,]+/', trim($returned)) ?: [];

        return array_values(array_unique(array_filter(
            $scopes !== [] ? $scopes : $verifiedFallback,
            static fn ($scope): bool => is_string($scope) && trim($scope) !== ''
        )));
    }

    private function oauthCacheKey(): string
    {
        return 'platforms.' . Auth::id() . '.pinterest.oauth';
    }

    private function redirectToPlatforms(string $type, string $message): RedirectResponse
    {
        return to_route($this->getBackCacheRoute())->with([
            'type' => $type,
            'message' => trans($message),
        ]);
    }
}
