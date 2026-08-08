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
use Illuminate\Support\Facades\Log;
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
                'type' => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $this->setBackCacheRoute();

        if (setting('FACEBOOK_APP_ID') && setting('FACEBOOK_APP_SECRET')) {
            if ($request->filled('platform_id')) {
                Cache::remember($this->cacheKey(), 60, fn () => $request->get('platform_id'));
            }

            return Facebook::authRedirect([
                'pages_manage_posts',
                'pages_show_list',
                'pages_read_user_content',
                'pages_read_engagement',
                'pages_manage_engagement',
                'pages_messaging',
                'pages_manage_metadata',
                'read_insights',
            ]);
        }

        return back()->with([
            'type' => 'error',
            'message' => 'Facebook app id and secret not set. Please contact the administrator.',
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $fb = new Facebook;
        $code = $request->get('code');

        if (! $code) {
            return $this->failure();
        }

        try {
            $token = $fb->getAccessToken($code)->throw()->json('access_token');
            $fb->setToken($token);
            $page = Arr::first($fb->getPagesInfo([
                'name,username,picture,access_token,followers_count,fan_count',
            ])->throw()->json('data'));

            if (! $page) {
                return $this->failure();
            }
        } catch (Exception) {
            return $this->failure();
        }

        $pageAccessToken = (string) data_get($page, 'access_token', '');
        $pageId = (string) data_get($page, 'id', '');
        $followersCount = (int) (data_get($page, 'followers_count') ?? data_get($page, 'fan_count') ?? 0);
        $credentials = [
            'type' => 'user',
            'platform_id' => $pageId,
            'name' => data_get($page, 'name'),
            'username' => data_get($page, 'username'),
            'picture' => self::downloadImageToStorage(data_get($page, 'picture.data.url')),
            'access_token' => $pageAccessToken,
            'access_token_expire_at' => config('social-media.facebook.access_token_expire_at', now()->addDay()),
            'refresh_token' => $pageAccessToken,
            'refresh_token_expire_at' => config('social-media.facebook.access_token_expire_at', now()->addDay()),
        ];

        $platformId = Cache::get($this->cacheKey());
        $platform = $platformId && is_numeric($platformId)
            ? SocialMediaPlatform::query()
                ->where('id', $platformId)
                ->where('user_id', Auth::id())
                ->where('platform', PlatformEnum::facebook->value)
                ->first()
            : null;

        $values = [
            'credentials' => $credentials,
            'connected_at' => now(),
            'expires_at' => config('social-media.facebook.access_token_expire_at', now()->addDay()),
            'followers_count' => $followersCount,
        ];

        if ($platform) {
            $platform->update($values);
            Cache::forget($this->cacheKey());
        } else {
            $platform = SocialMediaPlatform::query()->create([
                'user_id' => Auth::id(),
                'platform' => PlatformEnum::facebook->value,
                ...$values,
            ]);
        }

        if ($pageId !== '' && $pageAccessToken !== '') {
            $this->subscribePageToWebhook($pageId, $pageAccessToken);
        }

        return redirect()->route($this->getBackCacheRoute())->with([
            'type' => 'success',
            'message' => trans('Facebook account connected successfully.'),
        ]);
    }

    public function webhook(Request $request)
    {
        if ($request->isMethod('GET')) {
            $verifyToken = setting('FACEBOOK_WEBHOOK_SECRET', 'default-password');

            Log::info('Facebook webhook verification attempt', [
                'mode' => $request->get('hub_mode'),
                'matched' => hash_equals((string) $verifyToken, (string) $request->get('hub_verify_token')),
            ]);

            if ($request->get('hub_mode') === 'subscribe'
                && hash_equals((string) $verifyToken, (string) $request->get('hub_verify_token'))) {
                return response($request->get('hub_challenge'), 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response('Token invalid', 403);
        }

        if ($request->isMethod('POST')) {
            Log::info('Facebook webhook POST received', [
                'entry_count' => count((array) $request->json('entry', [])),
            ]);

            try {
                if (class_exists(WebhookProcessor::class)) {
                    $processor = app(WebhookProcessor::class);
                    $appSecret = setting('FACEBOOK_APP_SECRET');

                    if ($appSecret && ! $processor->verifySignature($request, $appSecret)) {
                        Log::warning('Facebook webhook signature verification failed');

                        return response('Invalid signature', 403);
                    }

                    $processor->processFacebookPayload($request->json()->all());
                } else {
                    Log::warning('Facebook webhook automation processor is unavailable');
                }
            } catch (Throwable $exception) {
                Log::error('Facebook webhook processing failed', [
                    'exception' => $exception::class,
                ]);
            }

            return response('EVENT_RECEIVED', 200);
        }

        return response('Method not allowed', 405);
    }

    private function subscribePageToWebhook(string $pageId, string $pageAccessToken): void
    {
        try {
            $response = (new Facebook(null, $pageAccessToken))->subscribePageToWebhook($pageId);
            Log::log($response->successful() ? 'info' : 'warning', 'Facebook page webhook subscription completed', [
                'page_id_hash' => hash('sha256', $pageId),
                'status' => $response->status(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Facebook page webhook subscription exception', [
                'page_id_hash' => hash('sha256', $pageId),
                'exception' => $exception::class,
            ]);
        }
    }

    private function failure(): RedirectResponse
    {
        return redirect()->route($this->getBackCacheRoute())->with([
            'type' => 'error',
            'message' => 'Something went wrong, please try again.',
        ]);
    }
}
