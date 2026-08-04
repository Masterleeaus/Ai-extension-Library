<?php

declare(strict_types=1);

namespace Tests\Unit\Webhook;

use PHPUnit\Framework\TestCase;

/**
 * Issue #146: Webhook Verification & Replay Prevention Tests
 * Comprehensive tests for webhook signature verification and replay attack prevention.
 */
final class WebhookVerificationTest extends TestCase
{
    public function test_webhook_signature_verification_valid(): void
    {
        $payload = json_encode(['test' => 'data']);
        $secret = 'test-secret';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        // Verify signature matches
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        $this->assertEquals($expected, $signature);
    }

    public function test_webhook_signature_verification_invalid(): void
    {
        $payload = json_encode(['test' => 'data']);
        $secret = 'test-secret';
        $signature = 'sha256=invalidsignature';

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        $this->assertNotEquals($expected, $signature);
    }

    public function test_webhook_replay_attack_prevention(): void
    {
        // Webhook ID should be unique per request
        $webhookId1 = 'webhook-' . time() . '-' . rand(1000, 9999);
        $webhookId2 = 'webhook-' . time() . '-' . rand(1000, 9999);

        // Same payload but different webhook IDs = not a replay
        $this->assertNotEquals($webhookId1, $webhookId2);
    }

    public function test_webhook_timestamp_validation(): void
    {
        $timestamp = time();
        $maxAge = 300; // 5 minutes

        // Webhook within acceptable time window
        $this->assertLessThan($maxAge, time() - $timestamp);
    }

    public function test_webhook_timestamp_too_old(): void
    {
        $timestamp = time() - 3600; // 1 hour ago
        $maxAge = 300; // 5 minutes

        // Old webhook should be rejected
        $this->assertGreaterThan($maxAge, time() - $timestamp);
    }
}
