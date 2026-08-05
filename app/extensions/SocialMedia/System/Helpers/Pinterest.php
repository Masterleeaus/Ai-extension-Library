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

class Pinterest
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('social-media.pinterest', []);
        $this->config['client_id'] = setting(
            'PINTEREST_CLIENT_ID',
            $this->config['client_id'] ?? null
        );
        $this->config['client_secret'] = setting(
            'PINTEREST_CLIENT_SECRET',
            $this->config['client_secret'] ?? null
        );
        $this->config['redirect_uri'] = setting(
            'PINTEREST_REDIRECT_URI',
            $this->config['redirect_uri'] ?? null
        );
    }

    public function authorizationUrl(string $state): string
    {
        return $this->requiredConfig('authorization_url') . '?' . http_build_query([
            'client_id' => $this->requiredConfig('client_id'),
            'redirect_uri' => $this->requiredConfig('redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(',', (array) ($this->config['scopes'] ?? [])),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeCode(string $code): Response
    {
        return Http::asForm()
            ->withBasicAuth(
                $this->requiredConfig('client_id'),
                $this->requiredConfig('client_secret')
            )
            ->timeout($this->timeoutSeconds())
            ->post($this->requiredConfig('token_url'), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->requiredConfig('redirect_uri'),
                'continuous_refresh' => (bool) ($this->config['continuous_refresh'] ?? true),
            ]);
    }

    public function refreshAccessToken(string $refreshToken): Response
    {
        return Http::asForm()
            ->withBasicAuth(
                $this->requiredConfig('client_id'),
                $this->requiredConfig('client_secret')
            )
            ->timeout($this->timeoutSeconds())
            ->post($this->requiredConfig('token_url'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope' => implode(',', (array) ($this->config['scopes'] ?? [])),
            ]);
    }

    public function userAccount(SocialMediaPlatform $platform): Response
    {
        return $this->request($platform, 'GET', '/user_account');
    }

    public function boards(
        SocialMediaPlatform $platform,
        ?string $bookmark = null
    ): Response {
        $query = ['page_size' => 100];

        if ($bookmark) {
            $query['bookmark'] = $bookmark;
        }

        return $this->request($platform, 'GET', '/boards', query: $query);
    }

    public function createPin(SocialMediaPlatform $platform, array $payload): Response
    {
        return $this->request($platform, 'POST', '/pins', $payload);
    }

    public function getPin(SocialMediaPlatform $platform, string $pinId): Response
    {
        return $this->request(
            $platform,
            'GET',
            '/pins/' . rawurlencode($this->requiredIdentifier($pinId, 'Pinterest Pin'))
        );
    }

    public function pinAnalytics(
        SocialMediaPlatform $platform,
        string $pinId,
        string $startDate,
        string $endDate,
        array $metrics
    ): Response {
        return $this->request(
            $platform,
            'GET',
            '/pins/' . rawurlencode($this->requiredIdentifier($pinId, 'Pinterest Pin')) . '/analytics',
            query: [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'metric_types' => implode(',', array_values($metrics)),
                'app_types' => 'ALL',
                'split_field' => 'NO_SPLIT',
            ]
        );
    }

    public function rateLimit(Response $response): array
    {
        return [
            'status' => $response->status(),
            'rate_limited' => $response->status() === 429,
            'limit' => $response->header('X-RateLimit-Limit'),
            'remaining' => $response->header('X-RateLimit-Remaining'),
            'reset_at' => $response->header('X-RateLimit-Reset'),
            'retry_after' => $response->header('Retry-After'),
            'provider_request_id' => $response->header('X-Pinterest-Trace-Id')
                ?: $response->header('X-Request-Id'),
        ];
    }

    private function request(
        SocialMediaPlatform $platform,
        string $method,
        string $path,
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

            $response = $this->apiClient($platform)->send(
                $method,
                $this->requiredConfig('api_root') . $path,
                $options
            );

            if (! in_array($response->status(), [429, 500, 502, 503, 504], true)
                || $attempt === $maxAttempts) {
                return $response;
            }

            $retryAfter = min(2, max(1, (int) ($response->header('Retry-After') ?: 1)));
            usleep($retryAfter * 1000000);
        }

        throw new RuntimeException('Pinterest request did not return a response.');
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
                'The Pinterest access token cannot be decrypted.',
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
                'The Pinterest refresh token cannot be decrypted.',
                previous: $exception
            );
        }

        $response = $this->refreshAccessToken($refreshToken);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException('Pinterest access token refresh failed.');
        }

        $credentials['access_token_encrypted'] = Crypt::encryptString(
            (string) $response->json('access_token')
        );
        $credentials['access_token_expires_at'] = now()
            ->addSeconds((int) $response->json('expires_in', 2592000))
            ->toIso8601String();

        if ($response->json('refresh_token')) {
            $credentials['refresh_token_encrypted'] = Crypt::encryptString(
                (string) $response->json('refresh_token')
            );
            $credentials['refresh_token_expires_at'] = now()
                ->addSeconds((int) $response->json('refresh_token_expires_in', 5184000))
                ->toIso8601String();
        }

        $platform->update([
            'credentials' => $credentials,
            'connected_at' => now(),
        ]);

        return $credentials;
    }

    private function requiredIdentifier(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^[A-Za-z0-9_-]{1,255}$/', $value)) {
            throw new RuntimeException("The {$label} identifier is invalid.");
        }

        return $value;
    }

    private function timeoutSeconds(): int
    {
        return max(5, min(60, (int) ($this->config['timeout_seconds'] ?? 20)));
    }

    private function requiredConfig(string $key): string
    {
        $value = trim((string) ($this->config[$key] ?? ''));

        if ($value === '') {
            throw new RuntimeException("Missing Pinterest configuration: {$key}.");
        }

        return $value;
    }
}
