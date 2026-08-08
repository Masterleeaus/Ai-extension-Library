<?php

namespace App\Extensions\SocialMedia\System\Helpers;

use App\Extensions\SocialMedia\System\Helpers\Contracts\BaseMetaHelper;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;

class Facebook extends BaseMetaHelper
{
    protected array $config = [];

    public function __construct(?array $config = null, protected ?string $accessToken = null)
    {
        $this->config = $config ?? config('social-media.facebook');

        $this->config = array_merge($this->config, [
            'app_id'       => setting('FACEBOOK_APP_ID'),
            'app_secret'   => setting('FACEBOOK_APP_SECRET'),
            'redirect_uri' => secure_url(config('social-media.facebook.redirect_uri')),
        ]);
    }

    public static function authRedirect(array $scopes = []): RedirectResponse
    {
        $fb = new self;

        if ($scopes) {
            $fb->config['scopes'] = $scopes;
        }

        $authUri = $fb->apiUrl('dialog/oauth', [
            'response_type' => 'code',
            'client_id'     => $fb->config['app_id'],
            'redirect_uri'  => $fb->config['redirect_uri'],
            'scope'         => collect($fb->config['scopes'])->join(','),
        ], true);

        return redirect($authUri);
    }

    public function refreshAccessToken(): Response
    {
        $apiUrl = $this->apiUrl('/oauth/access_token', [
            'client_id'         => $this->config['app_id'],
            'client_secret'     => $this->config['app_secret'],
            'grant_type'        => 'fb_exchange_token',
            'fb_exchange_token' => $this->accessToken,
        ]);

        return Http::post($apiUrl);
    }

    public function getAccountInfo(array $fields = []): Response
    {
        $apiUrl = $this->apiUrl('/me', [
            'access_token' => $this->accessToken,
            'fields'       => collect($fields)->join(','),
        ]);

        return Http::get($apiUrl);
    }

    public function getPagesInfo(array $fields = []): Response
    {
        $apiUrl = $this->apiUrl('/me/accounts', [
            'access_token' => $this->accessToken,
            'fields'       => collect($fields)->join(','),
        ]);

        return Http::get($apiUrl);
    }

    public function getPageProfile(string $pageId, array $fields = []): Response
    {
        $apiUrl = $this->apiUrl($pageId, [
            'access_token' => $this->accessToken,
            'fields'       => collect($fields)->join(','),
        ]);

        return Http::get($apiUrl);
    }

    public function publishTextOnPage(int $pageId, string $text): Response
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->post($this->apiUrl($pageId . '/feed'), ['message' => $text]);
    }

    public function publishPhotoOnPage(int $pageId, string $text, array $photos): Response
    {
        $attached_media = [];
        foreach ($photos as $url) {
            $res = Http::retry(3, 3000)
                ->withToken($this->accessToken)
                ->post($this->apiUrl($pageId . '/photos'), [
                    'url'       => url($url),
                    'published' => false,
                ]);
            $attached_media[] = ['media_fbid' => $res->json('id')];
        }

        return Http::retry(3, 3000)
            ->withToken($this->accessToken)
            ->post($this->apiUrl($pageId . '/feed'), [
                'message'        => $text,
                'attached_media' => $attached_media,
            ]);
    }

    public function publishPhotoStory(int $pageId, string $photoUrl): Response
    {
        $uploadResponse = Http::retry(3, 3000)
            ->withToken($this->accessToken)
            ->post($this->apiUrl($pageId . '/photos'), [
                'url'       => $photoUrl,
                'published' => false,
            ]);

        if ($uploadResponse->failed()) {
            return $uploadResponse;
        }

        return Http::retry(3, 3000)
            ->withToken($this->accessToken)
            ->post($this->apiUrl($pageId . '/photo_stories'), [
                'photo_id' => $uploadResponse->json('id'),
            ]);
    }

    public function publishVideoOnPage(string $pageId, string $fileUrl): Response
    {
        return Http::post($this->apiUrl("$pageId/videos"), [
            'file_url'     => $fileUrl,
            'description'  => 'example caption',
            'access_token' => $this->accessToken,
        ]);
    }

    public function getPageFeed(string $pageId, int $limit = 50, ?array $fields = null): Response
    {
        $defaultFields = ['id', 'message', 'created_time', 'full_picture'];

        return Http::withToken($this->accessToken)
            ->get($this->apiUrl("$pageId/feed", [
                'fields' => collect($fields ?? $defaultFields)->join(','),
                'limit'  => $limit,
            ]));
    }

    public function comments(string $postId, int $limit = 50, ?string $after = null): Response
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->get($this->apiUrl(rawurlencode(trim($postId)) . '/comments'), array_filter([
                'fields' => 'id,message,from,created_time,parent,can_reply_privately',
                'limit' => max(1, min(100, $limit)),
                'after' => $after,
            ], static fn ($value) => $value !== null && $value !== ''));
    }

    public function replyToComment(string $commentId, string $message): Response
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->post($this->apiUrl(rawurlencode(trim($commentId)) . '/comments'), [
                'message' => $message,
            ]);
    }

    public function privateReply(string $pageId, string $commentId, string $message): Response
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->post($this->apiUrl(rawurlencode(trim($pageId)) . '/messages'), [
                'recipient' => ['comment_id' => trim($commentId)],
                'message' => ['text' => $message],
            ]);
    }

    public function getPostAnalytics(string $postId, array $fields = []): Response
    {
        return Http::withToken($this->accessToken)
            ->get($this->apiUrl($postId, [
                'fields' => collect($fields)->join(','),
            ]));
    }

    public function subscribePageToWebhook(string $pageId, array $subscribedFields = ['feed']): Response
    {
        return Http::withToken($this->accessToken)
            ->post($this->apiUrl("{$pageId}/subscribed_apps"), [
                'subscribed_fields' => implode(',', $subscribedFields),
            ]);
    }
}
