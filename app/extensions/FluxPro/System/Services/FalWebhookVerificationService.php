<?php

declare(strict_types=1);

namespace App\Extensions\FluxPro\System\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FalWebhookVerificationService
{
    private const JWKS_CACHE_KEY = 'fal:jwks';
    private const JWKS_CACHE_TTL = 3600; // 1 hour
    private const MAX_JWKS_AGE = 86400; // 24 hours
    private const TIMESTAMP_TOLERANCE = 300; // 5 minutes
    private const FAL_JWKS_URL = 'https://api.fal.ai/.well-known/jwks.json';

    /**
     * Verify FAL webhook signature and headers
     */
    public function verifyWebhook(Request $request): array
    {
        $errors = [];

        // Require all FAL webhook headers
        $requestId = $request->header('X-Fal-Webhook-Request-Id');
        $userId = $request->header('X-Fal-Webhook-User-Id');
        $timestamp = $request->header('X-Fal-Webhook-Timestamp');
        $signature = $request->header('X-Fal-Webhook-Signature');

        if (!$requestId) {
            $errors[] = 'Missing X-Fal-Webhook-Request-Id header';
        }

        if (!$userId) {
            $errors[] = 'Missing X-Fal-Webhook-User-Id header';
        }

        if (!$timestamp) {
            $errors[] = 'Missing X-Fal-Webhook-Timestamp header';
        }

        if (!$signature) {
            $errors[] = 'Missing X-Fal-Webhook-Signature header';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
            ];
        }

        // Validate timestamp (not older than 5 minutes)
        try {
            $requestTime = (int) $timestamp;
            $currentTime = time();
            if (abs($currentTime - $requestTime) > self::TIMESTAMP_TOLERANCE) {
                $errors[] = 'Webhook timestamp is outside acceptable tolerance window';
            }
        } catch (\Throwable) {
            $errors[] = 'Invalid webhook timestamp format';
        }

        // Verify ED25519 signature using FAL's JWKS
        if (empty($errors)) {
            $signatureValid = $this->verifySignature(
                $request->getContent(),
                $signature,
                $timestamp
            );

            if (!$signatureValid) {
                $errors[] = 'Invalid webhook signature - verification failed';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'headers' => [
                'request_id' => $requestId,
                'user_id' => $userId,
                'timestamp' => $timestamp,
            ],
        ];
    }

    /**
     * Verify ED25519 signature using FAL's JWKS
     */
    private function verifySignature(string $payload, string $signature, string $timestamp): bool
    {
        try {
            // Get JWKS from FAL (with caching)
            $jwks = $this->getJwks();

            if (empty($jwks['keys'])) {
                return false;
            }

            // For now, we'll verify against the first key (in production, check kid)
            // FAL uses ED25519, so we need to decode and verify properly
            foreach ($jwks['keys'] as $key) {
                if (isset($key['kty']) && $key['kty'] === 'OKP' && $key['crv'] === 'Ed25519') {
                    // Construct the signed message (timestamp.payload)
                    $message = $timestamp . '.' . $payload;

                    // Decode the signature (base64url)
                    $decodedSignature = $this->base64UrlDecode($signature);

                    // Decode the public key (base64url)
                    $publicKeyPem = $this->convertJwkToPublicKeyPem($key);

                    if (!$publicKeyPem) {
                        continue;
                    }

                    // Verify signature using openssl
                    $verifyKey = openssl_pkey_get_public($publicKeyPem);
                    if ($verifyKey === false) {
                        continue;
                    }

                    $result = openssl_verify(
                        $message,
                        $decodedSignature,
                        $verifyKey,
                        OPENSSL_ALGO_SHA256
                    );

                    if ($result === 1) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Throwable $exception) {
            logger()->warning('FAL signature verification error', [
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get and cache FAL's JWKS
     */
    private function getJwks(): array
    {
        // Try to get from cache
        $cached = Cache::get(self::JWKS_CACHE_KEY);
        if ($cached) {
            return $cached;
        }

        // Fetch from FAL JWKS endpoint
        try {
            $response = Http::timeout(10)->get(self::FAL_JWKS_URL);

            if ($response->successful()) {
                $jwks = $response->json();

                // Cache for 1 hour
                Cache::put(self::JWKS_CACHE_KEY, $jwks, self::JWKS_CACHE_TTL);

                return $jwks;
            }
        } catch (\Throwable $exception) {
            logger()->warning('Failed to fetch FAL JWKS', [
                'error' => $exception->getMessage(),
            ]);
        }

        // Return empty keys if we can't fetch - fail closed
        return ['keys' => []];
    }

    /**
     * Convert JWK to OpenSSL public key PEM format
     */
    private function convertJwkToPublicKeyPem(array $key): ?string
    {
        try {
            if (!isset($key['x'])) {
                return null;
            }

            // Decode base64url public key
            $publicKeyBytes = $this->base64UrlDecode($key['x']);

            if (strlen($publicKeyBytes) !== 32) {
                return null; // ED25519 keys must be 32 bytes
            }

            // Convert to OpenSSL PEM format for ED25519
            $pem = "-----BEGIN PUBLIC KEY-----\n";
            $pem .= chunk_split(base64_encode("\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00" . $publicKeyBytes), 64, "\n");
            $pem .= "-----END PUBLIC KEY-----";

            return $pem;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Decode base64url encoded string
     */
    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'), true);
    }

    /**
     * Validate webhook payload limits
     */
    public function validatePayloadLimits(Request $request): array
    {
        $errors = [];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $contentLength = (int) ($request->server('CONTENT_LENGTH') ?? 0);
        if ($contentLength > $maxSize) {
            $errors[] = 'Webhook payload exceeds maximum size limit';
        }

        $contentType = $request->header('Content-Type') ?? '';
        if ($contentType !== 'application/json') {
            $errors[] = 'Webhook must use application/json content-type';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if webhook request has already been processed (idempotency)
     */
    public function hasRequestBeenProcessed(string $requestId): bool
    {
        $cacheKey = 'fal:webhook:' . $requestId;
        return Cache::has($cacheKey);
    }

    /**
     * Mark webhook request as processed
     */
    public function markRequestAsProcessed(string $requestId): void
    {
        $cacheKey = 'fal:webhook:' . $requestId;
        // Keep processed requests in cache for 24 hours for idempotency
        Cache::put($cacheKey, true, 86400);
    }
}
