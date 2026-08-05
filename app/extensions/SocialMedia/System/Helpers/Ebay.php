<?php

namespace App\Extensions\SocialMedia\System\Helpers;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class Ebay
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('social-media.ebay', []);
        $this->config['client_id'] = setting('EBAY_CLIENT_ID', $this->config['client_id'] ?? null);
        $this->config['client_secret'] = setting('EBAY_CLIENT_SECRET', $this->config['client_secret'] ?? null);
        $this->config['redirect_uri'] = setting('EBAY_REDIRECT_URI', $this->config['redirect_uri'] ?? null);
    }

    public function authRedirect(string $state): RedirectResponse
    {
        return redirect($this->authorizationUrl() . '?' . http_build_query([
            'client_id'     => $this->requiredConfig('client_id'),
            'redirect_uri'  => $this->requiredConfig('redirect_uri'),
            'response_type' => 'code',
            'scope'         => implode(' ', (array) ($this->config['scopes'] ?? [])),
            'state'         => $state,
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function getAccessToken(string $code): Response
    {
        return Http::asForm()
            ->withBasicAuth($this->requiredConfig('client_id'), $this->requiredConfig('client_secret'))
            ->post($this->tokenUrl(), [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => $this->requiredConfig('redirect_uri'),
            ]);
    }

    public function refreshAccessToken(string $refreshToken): Response
    {
        return Http::asForm()
            ->withBasicAuth($this->requiredConfig('client_id'), $this->requiredConfig('client_secret'))
            ->post($this->tokenUrl(), [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope'         => implode(' ', (array) ($this->config['scopes'] ?? [])),
            ]);
    }

    public function getInventoryLocations(SocialMediaPlatform $platform): Response
    {
        return $this->request($platform, 'GET', '/sell/inventory/v1/location', query: ['limit' => 200]);
    }

    public function createOrReplaceInventoryLocation(
        SocialMediaPlatform $platform,
        string $merchantLocationKey,
        array $payload
    ): Response {
        return $this->request(
            $platform,
            'POST',
            '/sell/inventory/v1/location/' . rawurlencode($merchantLocationKey),
            $payload
        );
    }

    public function getPaymentPolicies(SocialMediaPlatform $platform, string $marketplaceId): Response
    {
        return $this->request(
            $platform,
            'GET',
            '/sell/account/v1/payment_policy',
            query: ['marketplace_id' => $marketplaceId]
        );
    }

    public function getFulfillmentPolicies(SocialMediaPlatform $platform, string $marketplaceId): Response
    {
        return $this->request(
            $platform,
            'GET',
            '/sell/account/v1/fulfillment_policy',
            query: ['marketplace_id' => $marketplaceId]
        );
    }

    public function getReturnPolicies(SocialMediaPlatform $platform, string $marketplaceId): Response
    {
        return $this->request(
            $platform,
            'GET',
            '/sell/account/v1/return_policy',
            query: ['marketplace_id' => $marketplaceId]
        );
    }

    public function getPrivileges(SocialMediaPlatform $platform): Response
    {
        return $this->request($platform, 'GET', '/sell/account/v1/privilege');
    }

    public function createOrReplaceInventoryItem(
        SocialMediaPlatform $platform,
        string $sku,
        array $payload
    ): Response {
        return $this->request(
            $platform,
            'PUT',
            '/sell/inventory/v1/inventory_item/' . rawurlencode($sku),
            $payload
        );
    }

    public function getInventoryItem(SocialMediaPlatform $platform, string $sku): Response
    {
        return $this->request(
            $platform,
            'GET',
            '/sell/inventory/v1/inventory_item/' . rawurlencode($sku)
        );
    }

    public function createOffer(SocialMediaPlatform $platform, array $payload): Response
    {
        return $this->request($platform, 'POST', '/sell/inventory/v1/offer', $payload);
    }

    public function updateOffer(SocialMediaPlatform $platform, string $offerId, array $payload): Response
    {
        return $this->request(
            $platform,
            'PUT',
            '/sell/inventory/v1/offer/' . rawurlencode($offerId),
            $payload
        );
    }

    public function getOffer(SocialMediaPlatform $platform, string $offerId): Response
    {
        return $this->request(
            $platform,
            'GET',
            '/sell/inventory/v1/offer/' . rawurlencode($offerId)
        );
    }

    public function publishOffer(SocialMediaPlatform $platform, string $offerId): Response
    {
        return $this->request(
            $platform,
            'POST',
            '/sell/inventory/v1/offer/' . rawurlencode($offerId) . '/publish'
        );
    }

    public function withdrawOffer(SocialMediaPlatform $platform, string $offerId): Response
    {
        return $this->request(
            $platform,
            'POST',
            '/sell/inventory/v1/offer/' . rawurlencode($offerId) . '/withdraw'
        );
    }

    public function environment(): string
    {
        return $this->isSandbox() ? 'sandbox' : 'production';
    }

    private function request(
        SocialMediaPlatform $platform,
        string $method,
        string $path,
        array $payload = [],
        array $query = []
    ): Response {
        $method = strtoupper($method);
        $maxAttempts = in_array($method, ['GET', 'PUT'], true) ? 2 : 1;
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
                $this->apiRoot() . $path,
                $options
            );

            if (! in_array($response->status(), [429, 500, 502, 503, 504], true)
                || $attempt === $maxAttempts) {
                return $response;
            }

            $retryAfter = min(2, max(1, (int) ($response->header('Retry-After') ?: 1)));
            usleep($retryAfter * 1000000);
        }

        throw new RuntimeException('eBay request did not return a response.');
    }

    private function apiClient(SocialMediaPlatform $platform): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Content-Language' => (string) ($this->config['content_language'] ?? 'en-AU'),
            ])
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
            throw new RuntimeException('The eBay access token cannot be decrypted.', previous: $exception);
        }
    }

    private function refreshPlatformToken(SocialMediaPlatform $platform, array $credentials): array
    {
        try {
            $refreshToken = Crypt::decryptString((string) ($credentials['refresh_token_encrypted'] ?? ''));
        } catch (Throwable $exception) {
            throw new RuntimeException('The eBay refresh token cannot be decrypted.', previous: $exception);
        }

        $response = $this->refreshAccessToken($refreshToken);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException('eBay access token refresh failed.');
        }

        $credentials['access_token_encrypted'] = Crypt::encryptString((string) $response->json('access_token'));
        $credentials['access_token_expires_at'] = now()
            ->addSeconds((int) $response->json('expires_in', 7200))
            ->toIso8601String();

        $platform->update([
            'credentials' => $credentials,
            'connected_at' => now(),
        ]);

        return $credentials;
    }

    private function authorizationUrl(): string
    {
        return $this->isSandbox()
            ? 'https://auth.sandbox.ebay.com/oauth2/authorize'
            : 'https://auth.ebay.com/oauth2/authorize';
    }

    private function apiRoot(): string
    {
        return $this->isSandbox()
            ? 'https://api.sandbox.ebay.com'
            : 'https://api.ebay.com';
    }

    private function tokenUrl(): string
    {
        return $this->apiRoot() . '/identity/v1/oauth2/token';
    }

    private function isSandbox(): bool
    {
        return strtolower((string) ($this->config['environment'] ?? 'sandbox')) !== 'production';
    }

    private function requiredConfig(string $key): string
    {
        $value = trim((string) ($this->config[$key] ?? ''));

        if ($value === '') {
            throw new RuntimeException("Missing eBay configuration: {$key}.");
        }

        return $value;
    }
}
