<?php

namespace App\Extensions\SocialMedia\System\Helpers;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleBusinessProfile
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('social-media.google_business_profile', []);
        $this->config['client_id'] = setting(
            'GOOGLE_BUSINESS_PROFILE_CLIENT_ID',
            $this->config['client_id'] ?? null
        );
        $this->config['client_secret'] = setting(
            'GOOGLE_BUSINESS_PROFILE_CLIENT_SECRET',
            $this->config['client_secret'] ?? null
        );
        $this->config['redirect_uri'] = setting(
            'GOOGLE_BUSINESS_PROFILE_REDIRECT_URI',
            $this->config['redirect_uri'] ?? null
        );
    }

    public function authorizationUrl(string $state): string
    {
        return $this->requiredConfig('authorization_url') . '?' . http_build_query([
            'client_id' => $this->requiredConfig('client_id'),
            'redirect_uri' => $this->requiredConfig('redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', (array) ($this->config['scopes'] ?? [])),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeCode(string $code): Response
    {
        return Http::asForm()
            ->timeout($this->timeoutSeconds())
            ->post($this->requiredConfig('token_url'), [
                'client_id' => $this->requiredConfig('client_id'),
                'client_secret' => $this->requiredConfig('client_secret'),
                'redirect_uri' => $this->requiredConfig('redirect_uri'),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);
    }

    public function refreshAccessToken(string $refreshToken): Response
    {
        return Http::asForm()
            ->timeout($this->timeoutSeconds())
            ->post($this->requiredConfig('token_url'), [
                'client_id' => $this->requiredConfig('client_id'),
                'client_secret' => $this->requiredConfig('client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
    }

    public function accounts(SocialMediaPlatform $platform): Response
    {
        return $this->request(
            $platform,
            'GET',
            $this->requiredConfig('account_api_root') . '/accounts',
            query: ['pageSize' => 50]
        );
    }

    public function locations(
        SocialMediaPlatform $platform,
        string $accountName,
        ?string $pageToken = null
    ): Response {
        $query = [
            'readMask' => 'name,title,storeCode,websiteUri,phoneNumbers,categories,metadata,openInfo',
            'pageSize' => 100,
        ];

        if ($pageToken) {
            $query['pageToken'] = $pageToken;
        }

        return $this->request(
            $platform,
            'GET',
            $this->requiredConfig('business_information_api_root')
                . '/' . $this->resourceName($accountName, 'accounts') . '/locations',
            query: $query
        );
    }

    public function createLocalPost(
        SocialMediaPlatform $platform,
        string $locationName,
        array $payload
    ): Response {
        return $this->request(
            $platform,
            'POST',
            $this->requiredConfig('my_business_api_root')
                . '/' . $this->resourceName($locationName, 'accounts') . '/localPosts',
            $payload
        );
    }

    public function getLocalPost(SocialMediaPlatform $platform, string $localPostName): Response
    {
        return $this->request(
            $platform,
            'GET',
            $this->requiredConfig('my_business_api_root')
                . '/' . $this->resourceName($localPostName, 'accounts')
        );
    }

    public function createMedia(
        SocialMediaPlatform $platform,
        string $locationName,
        array $payload
    ): Response {
        return $this->request(
            $platform,
            'POST',
            $this->requiredConfig('my_business_api_root')
                . '/' . $this->resourceName($locationName, 'accounts') . '/media',
            $payload
        );
    }

    public function reviews(
        SocialMediaPlatform $platform,
        string $locationName,
        ?string $pageToken = null
    ): Response {
        $query = ['pageSize' => 50, 'orderBy' => 'update_time desc'];

        if ($pageToken) {
            $query['pageToken'] = $pageToken;
        }

        return $this->request(
            $platform,
            'GET',
            $this->requiredConfig('my_business_api_root')
                . '/' . $this->resourceName($locationName, 'accounts') . '/reviews',
            query: $query
        );
    }

    public function replyToReview(
        SocialMediaPlatform $platform,
        string $reviewName,
        string $comment
    ): Response {
        return $this->request(
            $platform,
            'PUT',
            $this->requiredConfig('my_business_api_root')
                . '/' . $this->resourceName($reviewName, 'accounts') . '/reply',
            ['comment' => $comment]
        );
    }

    public function performance(
        SocialMediaPlatform $platform,
        string $locationName,
        array $metrics,
        string $startDate,
        string $endDate
    ): Response {
        [$startYear, $startMonth, $startDay] = array_map('intval', explode('-', $startDate));
        [$endYear, $endMonth, $endDay] = array_map('intval', explode('-', $endDate));
        $queryParts = array_map(
            static fn (string $metric): string => 'dailyMetrics=' . rawurlencode($metric),
            array_values($metrics)
        );
        $queryParts[] = http_build_query([
            'dailyRange.start_date.year' => $startYear,
            'dailyRange.start_date.month' => $startMonth,
            'dailyRange.start_date.day' => $startDay,
            'dailyRange.end_date.year' => $endYear,
            'dailyRange.end_date.month' => $endMonth,
            'dailyRange.end_date.day' => $endDay,
        ], '', '&', PHP_QUERY_RFC3986);
        $url = $this->requiredConfig('performance_api_root')
            . '/' . $this->performanceLocationName($locationName)
            . ':fetchMultiDailyMetricsTimeSeries?'
            . implode('&', $queryParts);

        return $this->request($platform, 'GET', $url);
    }

    public function rateLimit(Response $response): array
    {
        return [
            'status' => $response->status(),
            'rate_limited' => $response->status() === 429,
            'retry_after' => $response->header('Retry-After'),
            'provider_request_id' => $response->header('X-GUploader-UploadID')
                ?: $response->header('X-Request-Id'),
        ];
    }

    private function request(
        SocialMediaPlatform $platform,
        string $method,
        string $url,
        array $payload = [],
        array $query = []
    ): Response {
        $method = strtoupper($method);
        $maxAttempts = in_array($method, ['GET', 'PUT'], true)
            ? max(1, (int) ($this->config['max_read_attempts'] ?? 2))
            : 1;
        $response = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $options = [];

            if ($payload !== []) {
                $options['json'] = $payload;
            }

            if ($query !== []) {
                $options['query'] = $query;
            }

            $response = $this->apiClient($platform)->send($method, $url, $options);

            if (! in_array($response->status(), [429, 500, 502, 503, 504], true)
                || $attempt === $maxAttempts) {
                return $response;
            }

            $retryAfter = min(2, max(1, (int) ($response->header('Retry-After') ?: 1)));
            usleep($retryAfter * 1000000);
        }

        throw new RuntimeException('Google Business Profile request did not return a response.');
    }

    private function apiClient(SocialMediaPlatform $platform): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout($this->timeoutSeconds())
            ->withToken($this->accessToken($platform));
    }

    private function accessToken(SocialMediaPlatform $platform): string
    {
        $credentials = (array) $platform->credentials;
        $expiresAt = isset($credentials['access_token_expires_at'])
            ? Carbon::parse($credentials['access_token_expires_at'])
            : now()->subMinute();

        if ($expiresAt->lte(now()->addMinute())) {
            $credentials = $this->refreshPlatformToken($platform, $credentials);
        }

        try {
            return Crypt::decryptString((string) ($credentials['access_token_encrypted'] ?? ''));
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The Google Business Profile access token cannot be decrypted.',
                previous: $exception
            );
        }
    }

    private function refreshPlatformToken(SocialMediaPlatform $platform, array $credentials): array
    {
        try {
            $refreshToken = Crypt::decryptString(
                (string) ($credentials['refresh_token_encrypted'] ?? '')
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The Google Business Profile refresh token cannot be decrypted.',
                previous: $exception
            );
        }

        $response = $this->refreshAccessToken($refreshToken);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException('Google Business Profile access token refresh failed.');
        }

        $credentials['access_token_encrypted'] = Crypt::encryptString(
            (string) $response->json('access_token')
        );
        $credentials['access_token_expires_at'] = now()
            ->addSeconds((int) $response->json('expires_in', 3600))
            ->toIso8601String();

        $platform->update([
            'credentials' => $credentials,
            'connected_at' => now(),
        ]);

        return $credentials;
    }

    private function resourceName(string $name, string $requiredPrefix): string
    {
        $name = trim($name, '/');

        if (! str_starts_with($name, $requiredPrefix . '/')) {
            throw new RuntimeException('The Google Business Profile resource name is invalid.');
        }

        return $name;
    }

    private function performanceLocationName(string $locationName): string
    {
        $locationName = trim($locationName, '/');
        $segments = explode('/', $locationName);
        $locationId = end($segments);

        if (! is_string($locationId) || $locationId === '') {
            throw new RuntimeException('The Google Business Profile location name is invalid.');
        }

        return 'locations/' . rawurlencode($locationId);
    }

    private function timeoutSeconds(): int
    {
        return max(5, min(60, (int) ($this->config['timeout_seconds'] ?? 20)));
    }

    private function requiredConfig(string $key): string
    {
        $value = trim((string) ($this->config[$key] ?? ''));

        if ($value === '') {
            throw new RuntimeException("Missing Google Business Profile configuration: {$key}.");
        }

        return $value;
    }
}
