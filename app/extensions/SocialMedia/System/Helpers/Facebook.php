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

    public static function authRedirect(array $scopes = [], ?string $state = null): RedirectResponse
    {
        $fb = new self;

        if ($scopes) {
            $fb->config['scopes'] = $scopes;
        }

        $params = [
            'response_type' => 'code',
            'client_id'     => $fb->config['app_id'],
            'redirect_uri'  => $fb->config['redirect_uri'],
            'scope'         => collect($fb->config['scopes'])->join(','),
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return redirect($fb->apiUrl('dialog/oauth', $params, true));
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
        return $this->get('/me', [
            'fields' => collect($fields)->join(','),
        ]);
    }

    public function getPagesInfo(array $fields = []): Response
    {
        return $this->get('/me/accounts', [
            'fields' => collect($fields)->join(','),
        ]);
    }

    public function getPageProfile(string $pageId, array $fields = []): Response
    {
        return $this->get('/' . $pageId, [
            'fields' => collect($fields)->join(','),
        ]);
    }

    public function getAdAccounts(array $fields = []): Response
    {
        $fields = $fields ?: [
            'id',
            'account_id',
            'name',
            'account_status',
            'currency',
            'timezone_name',
            'amount_spent',
            'spend_cap',
        ];

        return $this->get('/me/adaccounts', [
            'fields' => implode(',', $fields),
            'limit'  => 200,
        ]);
    }

    public function getAdAccount(string $adAccountId, array $fields = []): Response
    {
        $fields = $fields ?: [
            'id',
            'account_id',
            'name',
            'account_status',
            'currency',
            'timezone_name',
            'amount_spent',
            'spend_cap',
        ];

        return $this->get('/' . $this->normalizeAdAccountId($adAccountId), [
            'fields' => implode(',', $fields),
        ]);
    }

    public function createCampaign(string $adAccountId, array $payload): Response
    {
        return $this->post('/' . $this->normalizeAdAccountId($adAccountId) . '/campaigns', $payload);
    }

    public function createAdSet(string $adAccountId, array $payload): Response
    {
        return $this->post('/' . $this->normalizeAdAccountId($adAccountId) . '/adsets', $payload);
    }

    public function createAdCreative(string $adAccountId, array $payload): Response
    {
        return $this->post('/' . $this->normalizeAdAccountId($adAccountId) . '/adcreatives', $payload);
    }

    public function createAd(string $adAccountId, array $payload): Response
    {
        return $this->post('/' . $this->normalizeAdAccountId($adAccountId) . '/ads', $payload);
    }

    public function updateMarketingObject(string $objectId, array $payload): Response
    {
        return $this->post('/' . $objectId, $payload);
    }

    public function getInsights(string $objectId, array $fields, array $params = []): Response
    {
        return $this->get('/' . $objectId . '/insights', [
            ...$params,
            'fields' => implode(',', $fields),
        ]);
    }

    public function getAdPreviews(string $creativeId, string $adFormat): Response
    {
        return $this->get('/' . $creativeId . '/previews', [
            'ad_format' => $adFormat,
        ]);
    }

    public function publishTextOnPage(int $pageId, string $text): Response
    {
        return $this->post('/' . $pageId . '/feed', [
            'message' => $text,
        ]);
    }

    public function publishPhotoOnPage(int $pageId, string $text, array $photos): Response
    {
        $attachedMedia = [];

        foreach ($photos as $url) {
            $response = $this->post('/' . $pageId . '/photos', [
                'url'       => url($url),
                'published' => false,
            ]);

            if ($response->failed()) {
                return $response;
            }

            $attachedMedia[] = ['media_fbid' => $response->json('id')];
        }

        return $this->post('/' . $pageId . '/feed', [
            'message'        => $text,
            'attached_media' => $attachedMedia,
        ]);
    }

    public function publishPhotoStory(int $pageId, string $photoUrl): Response
    {
        $uploadResponse = $this->post('/' . $pageId . '/photos', [
            'url'       => $photoUrl,
            'published' => false,
        ]);

        if ($uploadResponse->failed()) {
            return $uploadResponse;
        }

        return $this->post('/' . $pageId . '/photo_stories', [
            'photo_id' => $uploadResponse->json('id'),
        ]);
    }

    public function publishVideoOnPage(string $pageId, string $fileUrl): Response
    {
        return $this->post('/' . $pageId . '/videos', [
            'file_url'    => $fileUrl,
            'description' => 'example caption',
        ]);
    }

    public function getPageFeed(string $pageId, int $limit = 50, ?array $fields = null): Response
    {
        $defaultFields = ['id', 'message', 'created_time', 'full_picture'];

        return $this->get('/' . $pageId . '/feed', [
            'fields' => collect($fields ?? $defaultFields)->join(','),
            'limit'  => $limit,
        ]);
    }

    public function getPostAnalytics(string $postId, array $fields = []): Response
    {
        return $this->get('/' . $postId, [
            'fields' => collect($fields)->join(','),
        ]);
    }

    public function subscribePageToWebhook(string $pageId, array $subscribedFields = ['feed']): Response
    {
        return $this->post('/' . $pageId . '/subscribed_apps', [
            'subscribed_fields' => implode(',', $subscribedFields),
        ]);
    }

    private function get(string $endpoint, array $query = []): Response
    {
        return Http::retry(2, 500, throw: false)
            ->withToken((string) $this->accessToken)
            ->acceptJson()
            ->get($this->apiUrl($endpoint), $query);
    }

    private function post(string $endpoint, array $payload = []): Response
    {
        return Http::asForm()
            ->withToken((string) $this->accessToken)
            ->acceptJson()
            ->post($this->apiUrl($endpoint), $payload);
    }

    private function normalizeAdAccountId(string $adAccountId): string
    {
        return str_starts_with($adAccountId, 'act_')
            ? $adAccountId
            : 'act_' . $adAccountId;
    }
}
