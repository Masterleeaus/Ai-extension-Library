<?php

namespace App\Extensions\SocialMedia\System\Helpers;

use App\Extensions\SocialMedia\System\Services\FileSplitService;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Linkedin
{
    protected $config = [];

    public function __construct(?array $config = null, protected ?string $accessToken = null)
    {
        $this->config = $config ?? config('social-media.linkedin');
        $this->config = array_merge($this->config, [
            'app_id'     => setting('LINKEDIN_APP_ID'),
            'app_secret' => setting('LINKEDIN_APP_SECRET'),
        ]);
        $this->config['redirect_uri'] = url($this->config['redirect_uri']);
    }

    private function apiUrl(string $endpoint, array $params = [], bool $isBaseUrl = false)
    {
        $apiUrl = $isBaseUrl ? $this->config['base_url'] : $this->config['api_url'];

        if (str_starts_with($endpoint, '/')) {
            $endpoint = substr($endpoint, 1);
        }

        $v = $this->config['api_version'] ?? null;
        $versionedUrlWithEndpoint = $apiUrl . '/' . (! $isBaseUrl && $v ? ($v . '/') : '') . $endpoint;

        if (count($params)) {
            $versionedUrlWithEndpoint .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $versionedUrlWithEndpoint;
    }

    public function setToken(string $bearerToken)
    {
        $this->accessToken = $bearerToken;

        return $this;
    }

    public static function authRedirect(array $scopes = []): RedirectResponse
    {
        $linkedin = new static;
        $linkedin->config['scopes'] = $scopes ?: config('social-media.linkedin.scopes', []);

        return redirect($linkedin->apiUrl('oauth/v2/authorization', [
            'response_type' => 'code',
            'client_id'     => $linkedin->config['app_id'],
            'redirect_uri'  => $linkedin->config['redirect_uri'],
            'scope'         => collect($linkedin->config['scopes'])->join(' '),
        ], true));
    }

    public function getAccessToken(string $code): Response
    {
        return Http::post($this->apiUrl('oauth/v2/accessToken', [
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->config['app_id'],
            'client_secret' => $this->config['app_secret'],
            'redirect_uri'  => $this->config['redirect_uri'],
        ], true));
    }

    public function refreshAccessToken($refreshToken = null): Response
    {
        return Http::post($this->apiUrl('oauth/v2/accessToken', [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->config['app_id'],
            'client_secret' => $this->config['app_secret'],
        ], true));
    }

    public function getAccountInfo()
    {
        return Http::withToken($this->accessToken)->get($this->apiUrl('v2/userinfo'));
    }

    public function publishText(string $userId, string $text): Response
    {
        $post = [
            'author'       => "urn:li:person:{$userId}",
            'commentary'   => $text,
            'visibility'   => config('platform.linkedin.options.visibility', 'PUBLIC'),
            'distribution' => [
                'feedDistribution'               => config('platform.linkedin.options.feedDistribution', 'MAIN_FEED'),
                'targetEntities'                 => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState'            => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];

        return $this->apiClient()->post($this->apiUrl('rest/posts'), $post);
    }

    public function publishImage(string $userId, array $images, string $text): Response
    {
        $uploadedMedia = collect([]);
        foreach ($images as $imagePath) {
            $imageContainer = $this->apiClient()
                ->post($this->apiUrl('rest/images', ['action' => 'initializeUpload']), [
                    'initializeUploadRequest' => ['owner' => "urn:li:person:{$userId}"],
                ])
                ->json('value');

            $response = $this->apiClient()
                ->attach('file', fopen($imagePath, 'rb'))
                ->post($imageContainer['uploadUrl']);

            if ($response->created()) {
                $uploadedMedia->push($imageContainer);
            }
        }

        $postImages = $uploadedMedia->map(fn ($item) => ['id' => $item['image']]);
        $attachMediaObj = ($postImages->count() > 1) ? [
            'content' => ['multiImage' => ['images' => $postImages->toArray()]],
        ] : [
            'content' => ['media' => ['id' => $postImages->value('id')]],
        ];

        $post = [
            'author'       => "urn:li:person:{$userId}",
            'commentary'   => $text,
            'visibility'   => config('platform.linkedin.options.visibility', 'PUBLIC'),
            'distribution' => [
                'feedDistribution'               => config('platform.linkedin.options.feedDistribution', 'MAIN_FEED'),
                'targetEntities'                 => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState'            => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
            ...$attachMediaObj,
        ];

        return $this->apiClient()->post($this->apiUrl('rest/posts'), $post);
    }

    public function publishVideo(string $userId, string $videoPath, string $text): Response
    {
        throw_unless(Storage::exists($videoPath), "File not found: $videoPath");
        $mediaFileSize = Storage::size($videoPath);
        $mediaName = basename($videoPath);
        $mediaContainerData = [
            'mediaLibraryMetadata' => [
                'owner'     => "urn:li:person:{$userId}",
                'assetName' => $mediaName,
            ],
            'initializeUploadRequest' => [
                'owner'           => "urn:li:person:{$userId}",
                'fileSizeBytes'   => $mediaFileSize,
                'uploadCaptions'  => false,
                'uploadThumbnail' => false,
            ],
        ];

        $mediaContainerRes = $this->apiClient()
            ->asJson()
            ->post($this->apiUrl('rest/videos', ['action' => 'initializeUpload']), $mediaContainerData);

        if ($mediaContainerRes->failed()) {
            return $mediaContainerRes;
        }

        $mediaContainer = $mediaContainerRes->json('value');
        $videoSplitService = new FileSplitService($videoPath, 4);
        $videoChunks = $videoSplitService->splitAndUpload();
        $uploadedParts = [];

        foreach ($videoChunks as $key => $chunk) {
            $uploadUrl = $mediaContainer['uploadInstructions'][$key]['uploadUrl'];
            $fileContent = file_get_contents($chunk);
            $uploadMediaRes = Http::withHeaders(['Content-Type' => 'application/octet-stream'])
                ->withBody($fileContent, 'application/octet-stream')
                ->put($uploadUrl);
            $videoId = $mediaContainer['video'];
            $eTag = $uploadMediaRes->headers()['ETag'] ?? [];
            array_push($uploadedParts, ...$eTag);
        }

        $this->apiClient()->asJson()
            ->post($this->apiUrl('rest/videos', ['action' => 'finalizeUpload']), [
                'finalizeUploadRequest' => [
                    'video'           => $videoId,
                    'uploadToken'     => '',
                    'uploadedPartIds' => $uploadedParts,
                ],
            ])
            ->throw();

        $videoStatus = $this->apiClient()->get($this->apiUrl('rest/videos/' . urlencode($videoId)))->throw();
        throw_if(
            $videoStatus->json('status') == 'PROCESSING_FAILED ',
            new Exception('Video processing failed. Reason: ' . $videoStatus->json('processingFailureReason' ?? 'unknown'))
        );

        $isVideoAllowed = false;
        $attempt = 0;
        while (! $isVideoAllowed && $attempt < 10) {
            $videoStatus = $this->apiClient()->get($this->apiUrl('rest/videos/' . urlencode($videoId)))->throw();

            if ($videoStatus->json('status') == 'AVAILABLE') {
                $isVideoAllowed = true;
                break;
            }

            $attempt++;
            sleep(2);
        }

        $post = [
            'author'       => "urn:li:person:{$userId}",
            'commentary'   => $text,
            'visibility'   => config('platform.linkedin.options.visibility', 'PUBLIC'),
            'distribution' => [
                'feedDistribution'               => config('platform.linkedin.options.feedDistribution', 'MAIN_FEED'),
                'targetEntities'                 => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState'            => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
            'content'                   => ['media' => ['id' => $videoId]],
        ];

        $videoSplitService->cleanup();

        return $this->apiClient()->post($this->apiUrl('rest/posts'), $post)->throw();
    }

    public function organizationAcls(?string $role = null): Response
    {
        return $this->apiClient()->get($this->apiUrl('rest/organizationAcls', array_filter([
            'q' => 'roleAssignee',
            'role' => $role,
        ], static fn ($value) => $value !== null && $value !== '')));
    }

    public function comments(string $targetUrn, int $start = 0, int $count = 50): Response
    {
        return $this->apiClient()->get($this->apiUrl(
            'rest/socialActions/' . rawurlencode(trim($targetUrn)) . '/comments',
            ['start' => max(0, $start), 'count' => max(1, min(100, $count))]
        ));
    }

    public function replyToComment(
        string $targetUrn,
        string $actorUrn,
        string $objectUrn,
        string $text,
        ?string $parentCommentUrn = null
    ): Response {
        $payload = [
            'actor' => trim($actorUrn),
            'object' => trim($objectUrn),
            'message' => ['text' => $text],
        ];

        if ($parentCommentUrn) {
            $payload['parentComment'] = trim($parentCommentUrn);
        }

        return $this->apiClient()->post(
            $this->apiUrl('rest/socialActions/' . rawurlencode(trim($targetUrn)) . '/comments'),
            $payload
        );
    }

    public function updateComment(
        string $objectUrn,
        string $commentId,
        string $actorUrn,
        string $text
    ): Response {
        return $this->apiClient()
            ->withHeader('X-RestLi-Method', 'PARTIAL_UPDATE')
            ->post($this->apiUrl(
                'rest/socialActions/' . rawurlencode(trim($objectUrn)) . '/comments/' . rawurlencode(trim($commentId)),
                ['actor' => trim($actorUrn)]
            ), [
                'patch' => [
                    'message' => [
                        '$set' => ['text' => $text],
                    ],
                ],
            ]);
    }

    public function deleteComment(string $objectUrn, string $commentId, string $actorUrn): Response
    {
        return $this->apiClient()->delete($this->apiUrl(
            'rest/socialActions/' . rawurlencode(trim($objectUrn)) . '/comments/' . rawurlencode(trim($commentId)),
            ['actor' => trim($actorUrn)]
        ));
    }

    public function getPostAnalytics(string $urn): Response
    {
        return $this->apiClient()->get($this->apiUrl('rest/socialMetadata/' . urlencode($urn)));
    }

    public function getVideo(string $urn): Response
    {
        return $this->apiClient()->get($this->apiUrl("rest/videos/{$urn}"));
    }

    public function getNetworkSize(string $memberId, string $edgeType = 'Connections'): Response
    {
        $urn = str_starts_with($memberId, 'urn:') ? $memberId : "urn:li:person:{$memberId}";

        return $this->apiClient()->get($this->apiUrl('rest/networkSizes/' . urlencode($urn), [
            'edgeType' => $edgeType,
        ]));
    }

    private function apiClient(): PendingRequest
    {
        return Http::withHeaders([
            'X-Restli-Protocol-Version' => config('social-media.linkedin.restli_protocol_version'),
            'LinkedIn-Version'          => config('social-media.linkedin.header_version'),
        ])->withToken($this->accessToken)->retry(1, 3000);
    }
}
