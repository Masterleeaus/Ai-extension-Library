<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers\Oauth;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Facebook;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\Traits\HasBackRoute;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\Traits\HasSaveImage;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMediaAutomation\System\Services\WebhookProcessor;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FacebookController extends Controller
{
    use HasBackRoute;
    use HasSaveImage;

    private function cacheKey(): string
    {
        return 'platforms.' . Auth::id() . '.facebook';
    }

    public function redirect(Request $request): RedirectResponse
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type'    => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $this->setBackCacheRoute();

        if (! setting('FACEBOOK_APP_ID') || ! setting('FACEBOOK_APP_SECRET')) {
            return back()->with([
                'type'    => 'error',
                'message' => trans('Facebook app ID and secret are not configured.'),
            ]);
        }

        $state = Str::random(64);
        Cache::put($this->cacheKey(), [
            'state'       => $state,
            'platform_id' => $request->integer('platform_id') ?: null,
        ], now()->addMinutes(10));

        return Facebook::authRedirect([
            'pages_manage_posts',
            'pages_show_list',
            'pages_read_user_content',
            'pages_read_engagement',
            'pages_messaging',
            'pages_manage_metadata',
            'read_insights',
            'ads_read',
            'ads_management',
        ], $state);
    }

    public function callback(Request $request): RedirectResponse
    {
        $cached = Cache::pull($this->cacheKey());
        $returnedState = (string) $request->get('state', '');
        $expectedState = (string) ($cached['state'] ?? '');

        if ($returnedState === '' || $expectedState === '' || ! hash_equals($expectedState, $returnedState)) {
            return $this->redirectToPlatforms('error', 'The Facebook authorization state was invalid or expired.');
        }

        $code = (string) $request->get('code', '');

        if ($code === '') {
            return $this->redirectToPlatforms('error', 'Facebook did not return an authorization code.');
        }

        try {
            $facebook = new Facebook;
            $shortToken = (string) $facebook->getAccessToken($code)->throw()->json('access_token', '');

            if ($shortToken === '') {
                throw new Exception('Facebook did not return an access token.');
            }

            $facebook->setToken($shortToken);
            $longLivedResponse = $facebook->refreshAccessToken();
            $marketingToken = $longLivedResponse->successful()
                ? (string) $longLivedResponse->json('access_token', $shortToken)
                : $shortToken;
            $marketingExpiresIn = (int) $longLivedResponse->json('expires_in', 5184000);

            $facebook->setToken($marketingToken);
            $page = Arr::first((array) $facebook
                ->getPagesInfo(['name,username,picture,access_token,followers_count,fan_count'])
                ->throw()
                ->json('data', []));

            if (! $page) {
                return $this->redirectToPlatforms('error', 'No manageable Facebook Page was returned.');
            }

            $adAccountsResponse = $facebook->getAdAccounts();
            $adAccounts = $adAccountsResponse->successful()
                ? collect((array) $adAccountsResponse->json('data', []))
                    ->map(fn (array $account) => Arr::only($account, [
                        'id',
                        'account_id',
                        'name',
                        'account_status',
                        'currency',
                        'timezone_name',
                        'amount_spent',
                        'spend_cap',
                    ]))
                    ->values()
                    ->all()
                : [];

            $pageAccessToken = (string) data_get($page, 'access_token', '');
            $pageId = (string) data_get($page, 'id', '');
            $followersCount = (int) (
                data_get($page, 'followers_count')
                ?? data_get($page, 'fan_count')
                ?? 0
            );

            if ($pageAccessToken === '' || $pageId === '') {
                throw new Exception('Facebook Page credentials were incomplete.');
            }

            $credentials = [
                'type'                         => 'user',
                'platform_id'                  => $pageId,
                'name'                         => data_get($page, 'name'),
                'username'                     => data_get($page, 'username'),
                'picture'                      => self::downloadImageToStorage(data_get($page, 'picture.data.url')),
                'access_token'                 => $pageAccessToken,
                'access_token_expire_at'       => config('social-media.facebook.access_token_expire_at', now()->addDay()),
                'refresh_token'                => $pageAccessToken,
                'refresh_token_expire_at'      => config('social-media.facebook.access_token_expire_at', now()->addDay()),
                'marketing_access_token_encrypted' => Crypt::encryptString($marketingToken),
                'marketing_access_token_expires_at' => now()->addSeconds($marketingExpiresIn)->toIso8601String(),
                'marketing_authorized_scopes'  => ['ads_read', 'ads_management'],
                'ad_accounts'                  => $adAccounts,
                'ad_accounts_discovered_at'    => now()->toIso8601String(),
            ];

            $platform = $this->resolvePlatform($cached['platform_id'] ?? null);

            if ($platform) {
                $platform->update([
                    'credentials'     => $credentials,
                    'connected_at'    => now(),
                    'expires_at'      => config('social-media.facebook.access_token_expire_at', now()->addDay()),
                    'followers_count' => $followersCount,
                ]);
            } else {
                $platform = SocialMediaPlatform::query()->create([
                    'user_id'         => Auth::id(),
                    'platform'        => PlatformEnum::facebook->value,
                    'credentials'     => $credentials,
                    'connected_at'    => now(),
                    'expires_at'      => config('social-media.facebook.access_token_expire_at', now()->addDay()),
                    'followers_count' => $followersCount,
                ]);
            }

            $this->subscribePageToWebhook($pageId, $pageAccessToken);

            return $this->redirectToPlatforms('success', 'Facebook account connected successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToPlatforms('error', 'The Facebook account could not be connected.');
        }
    }

    public function webhook(Request $request)
    {
        if ($request->isMethod('GET')) {
            $verifyToken = setting('FACEBOOK_WEBHOOK_SECRET', 'default-password');

            if ($request->get('hub_mode') === 'subscribe'
                && hash_equals((string) $verifyToken, (string) $request->get('hub_verify_token'))) {
                return response($request->get('hub_challenge'), 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response('Token invalid', 403);
        }

        if ($request->isMethod('POST')) {
            try {
                if (class_exists(WebhookProcessor::class)) {
                    $processor = app(WebhookProcessor::class);
                    $appSecret = setting('FACEBOOK_APP_SECRET');

                    if ($appSecret && ! $processor->verifySignature($request, $appSecret)) {
                        Log::warning('Facebook webhook signature verification failed');

                        return response('Invalid signature', 403);
                    }

                    $processor->processFacebookPayload($request->json()->all());
                }
            } catch (Throwable $exception) {
                report($exception);
            }

            return response('EVENT_RECEIVED', 200);
        }

        return response('Method not allowed', 405);
    }

    private function resolvePlatform(?int $platformId): ?SocialMediaPlatform
    {
        if (! $platformId) {
            return null;
        }

        return SocialMediaPlatform::query()
            ->where('id', $platformId)
            ->where('user_id', Auth::id())
            ->where('platform', PlatformEnum::facebook->value)
            ->first();
    }

    private function subscribePageToWebhook(string $pageId, string $pageAccessToken): void
    {
        try {
            $response = (new Facebook(null, $pageAccessToken))->subscribePageToWebhook($pageId);

            if ($response->failed()) {
                Log::warning('Facebook page webhook subscription failed', [
                    'page_id' => $pageId,
                    'status'  => $response->status(),
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function redirectToPlatforms(string $type, string $message): RedirectResponse
    {
        return redirect()->route($this->getBackCacheRoute())->with([
            'type'    => $type,
            'message' => trans($message),
        ]);
    }
}
