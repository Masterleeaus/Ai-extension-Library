<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FalWebhookVerifier
{
    private const JWKS_URL = 'https://rest.fal.ai/.well-known/jwks.json';

    private const JWKS_CACHE_KEY = 'security:fal:webhook:jwks';

    private const JWKS_CACHE_SECONDS = 21600;

    private const MAX_AGE_SECONDS = 300;

    public function assertValid(Request $request): void
    {
        $requestId = trim((string) $request->header('X-Fal-Webhook-Request-Id'));
        $userId = trim((string) $request->header('X-Fal-Webhook-User-Id'));
        $timestamp = trim((string) $request->header('X-Fal-Webhook-Timestamp'));
        $signatureHex = trim((string) $request->header('X-Fal-Webhook-Signature'));

        if ($requestId === '' || $userId === '' || $timestamp === '' || $signatureHex === '') {
            throw new RuntimeException('Required FAL webhook authentication headers are missing.');
        }

        if (! ctype_digit($timestamp)) {
            throw new RuntimeException('FAL webhook timestamp is invalid.');
        }

        $sentAt = (int) $timestamp;
        if (abs(now()->timestamp - $sentAt) > self::MAX_AGE_SECONDS) {
            throw new RuntimeException('FAL webhook timestamp is outside the allowed window.');
        }

        if (strlen($signatureHex) !== 128 || ! ctype_xdigit($signatureHex)) {
            throw new RuntimeException('FAL webhook signature encoding is invalid.');
        }

        $signature = hex2bin($signatureHex);
        if (! is_string($signature) || strlen($signature) !== 64) {
            throw new RuntimeException('FAL webhook signature is invalid.');
        }

        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            throw new RuntimeException('Ed25519 verification is unavailable.');
        }

        $message = implode("\n", [
            $requestId,
            $userId,
            $timestamp,
            hash('sha256', $request->getContent()),
        ]);

        foreach ($this->publicKeys() as $publicKey) {
            if (sodium_crypto_sign_verify_detached($signature, $message, $publicKey)) {
                return;
            }
        }

        throw new RuntimeException('FAL webhook signature verification failed.');
    }

    /** @return list<string> */
    private function publicKeys(): array
    {
        $jwkSet = Cache::remember(
            self::JWKS_CACHE_KEY,
            self::JWKS_CACHE_SECONDS,
            static function (): array {
                $response = Http::connectTimeout(5)
                    ->timeout(10)
                    ->withOptions([
                        'allow_redirects' => false,
                        'http_errors' => false,
                    ])
                    ->acceptJson()
                    ->get(self::JWKS_URL);

                if (! $response->successful()) {
                    throw new RuntimeException('Unable to retrieve FAL webhook verification keys.');
                }

                $keys = $response->json('keys');
                if (! is_array($keys) || $keys === []) {
                    throw new RuntimeException('FAL webhook verification keys are unavailable.');
                }

                return $keys;
            }
        );

        $publicKeys = [];

        foreach ($jwkSet as $jwk) {
            if (! is_array($jwk)
                || ($jwk['kty'] ?? null) !== 'OKP'
                || ($jwk['crv'] ?? null) !== 'Ed25519'
                || ! is_string($jwk['x'] ?? null)
            ) {
                continue;
            }

            $publicKey = $this->base64UrlDecode($jwk['x']);
            if ($publicKey !== null && strlen($publicKey) === 32) {
                $publicKeys[] = $publicKey;
            }
        }

        if ($publicKeys === []) {
            throw new RuntimeException('FAL webhook verification keys are invalid.');
        }

        return $publicKeys;
    }

    private function base64UrlDecode(string $value): ?string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;

        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);

        return is_string($decoded) ? $decoded : null;
    }
}
