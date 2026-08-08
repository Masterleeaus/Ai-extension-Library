<?php

namespace App\Extensions\SocialMedia\System\Helpers\Contracts;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class BaseMetaHelper
{
    public function getAccessToken(string $code): Response
    {
        $redirect_uri = $this->apiUrl('/oauth/access_token', [
            'code'          => $code,
            'client_id'     => $this->config['app_id'],
            'client_secret' => $this->config['app_secret'],
            'redirect_uri'  => $this->config['redirect_uri'],
        ]);

        return Http::post($redirect_uri);
    }

    public function debugToken(?string $token = null): Response
    {
        $inputToken = trim((string) ($token ?? $this->accessToken));
        $appId = trim((string) ($this->config['app_id'] ?? ''));
        $appSecret = trim((string) ($this->config['app_secret'] ?? ''));

        if ($inputToken === '' || $appId === '' || $appSecret === '') {
            throw new RuntimeException('Meta token capability discovery is not configured.');
        }

        return Http::acceptJson()->get($this->apiUrl('debug_token'), [
            'input_token' => $inputToken,
            'access_token' => $appId . '|' . $appSecret,
        ]);
    }

    protected function apiUrl(string $endpoint, array $params = [], bool $isBaseUrl = false): string
    {
        $apiUrl = $isBaseUrl ? $this->config['base_url'] : $this->config['api_url'];

        if (str_starts_with($endpoint, '/')) {
            $endpoint = substr($endpoint, 1);
        }

        $v = $this->config['api_version'] ?? '';
        $versionedUrlWithEndpoint = $apiUrl . '/' . ($v ? ($v . '/') : '') . $endpoint;

        if (count($params)) {
            $versionedUrlWithEndpoint .= '?' . http_build_query($params);
        }

        return $versionedUrlWithEndpoint;
    }

    public function setToken(string $bearerToken): self
    {
        $this->accessToken = $bearerToken;

        return $this;
    }
}
