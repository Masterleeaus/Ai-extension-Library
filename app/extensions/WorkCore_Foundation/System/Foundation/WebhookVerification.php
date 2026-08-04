<?php

namespace Extensions\WorkCore_Foundation\System\Foundation;

use Illuminate\Support\Facades\Cache;

/**
 * Issue #146: Webhook Verification & Replay Prevention Tests
 * Secure webhook validation with signature verification and replay prevention
 */
class WebhookVerification
{
    protected $secret;
    protected const REPLAY_CACHE_TTL = 86400; // 24 hours
    protected const TIMESTAMP_TOLERANCE = 300; // 5 minutes

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $timestamp): bool
    {
        // Verify timestamp is recent (prevent old replays)
        if (!$this->verifyTimestamp($timestamp)) {
            return false;
        }

        // Create expected signature
        $expectedSignature = $this->computeSignature($payload, $timestamp);

        // Use timing-safe comparison
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check for replay attack
     */
    public function checkReplay(string $webhookId, string $signature, string $timestamp): bool
    {
        $cacheKey = "webhook:replay:{$webhookId}:{$signature}";

        if (Cache::has($cacheKey)) {
            return false; // Replay detected
        }

        // Record this webhook ID to prevent replay
        Cache::put($cacheKey, true, self::REPLAY_CACHE_TTL);

        return true;
    }

    /**
     * Compute webhook signature
     */
    public function computeSignature(string $payload, string $timestamp): string
    {
        $data = "{$timestamp}.{$payload}";
        return hash_hmac('sha256', $data, $this->secret);
    }

    /**
     * Verify webhook timestamp is within tolerance
     */
    protected function verifyTimestamp(string $timestamp): bool
    {
        $webhookTime = intval($timestamp);
        $currentTime = time();
        $difference = abs($currentTime - $webhookTime);

        return $difference <= self::TIMESTAMP_TOLERANCE;
    }

    /**
     * Create webhook signature for outbound webhooks
     */
    public function createSignature(string $payload): array
    {
        $timestamp = time();
        $signature = $this->computeSignature($payload, (string)$timestamp);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
        ];
    }
}
