<?php

declare(strict_types=1);

namespace App\Extensions\FluxPro\Tests\Feature;

use App\Extensions\FluxPro\System\Services\FalWebhookVerificationService;
use App\Models\UserOpenai;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FalWebhookSecurityTest extends TestCase
{
    private FalWebhookVerificationService $verificationService;
    private string $testTimestamp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verificationService = app(FalWebhookVerificationService::class);
        $this->testTimestamp = (string) time();
    }

    // Route and Method Tests
    public function test_webhook_route_only_accepts_post_method(): void
    {
        $response = $this->get('/api/webhooks/fal/flux-pro');
        $this->assertNotEquals(200, $response->status());

        $response = $this->put('/api/webhooks/fal/flux-pro');
        $this->assertNotEquals(200, $response->status());

        $response = $this->patch('/api/webhooks/fal/flux-pro');
        $this->assertNotEquals(200, $response->status());

        $response = $this->delete('/api/webhooks/fal/flux-pro');
        $this->assertNotEquals(200, $response->status());
    }

    public function test_legacy_route_rejects_non_post_methods(): void
    {
        $response = $this->get('/generator/webhook/fal-ai');
        $this->assertEquals(405, $response->status());

        $response = $this->put('/generator/webhook/fal-ai');
        $this->assertEquals(405, $response->status());
    }

    // Header Validation Tests
    public function test_rejects_webhook_without_request_id_header(): void
    {
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [], [
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        $this->assertEquals(401, $response->status());
        $this->assertFalse($response->json('valid'));
    }

    public function test_rejects_webhook_without_user_id_header(): void
    {
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        $this->assertEquals(401, $response->status());
    }

    public function test_rejects_webhook_without_timestamp_header(): void
    {
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        $this->assertEquals(401, $response->status());
    }

    public function test_rejects_webhook_without_signature_header(): void
    {
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
        ]);

        $this->assertEquals(401, $response->status());
    }

    // Timestamp Validation Tests
    public function test_rejects_expired_webhook_timestamp(): void
    {
        $oldTimestamp = (string) (time() - 600); // 10 minutes old

        $response = $this->postJson('/api/webhooks/fal/flux-pro', [], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $oldTimestamp,
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        $this->assertEquals(401, $response->status());
    }

    public function test_rejects_invalid_timestamp_format(): void
    {
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => 'not-a-timestamp',
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        $this->assertEquals(401, $response->status());
    }

    // Payload Validation Tests
    public function test_rejects_oversized_webhook_payload(): void
    {
        $largePayload = array_fill(0, 100000, 'x');

        $response = $this->postJson('/api/webhooks/fal/flux-pro', $largePayload, [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
            'X-Fal-Webhook-Signature' => 'test-signature',
            'Content-Length' => (string) (6 * 1024 * 1024), // 6MB
        ]);

        $this->assertEquals(400, $response->status());
        $this->assertStringContainsString('size', $response->json('error'));
    }

    public function test_rejects_non_json_content_type(): void
    {
        $response = $this->post(
            '/api/webhooks/fal/flux-pro',
            'not json',
            [
                'X-Fal-Webhook-Request-Id' => 'req-123',
                'X-Fal-Webhook-User-Id' => 'user-123',
                'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
                'X-Fal-Webhook-Signature' => 'test-signature',
                'Content-Type' => 'text/plain',
            ]
        );

        $this->assertEquals(400, $response->status());
    }

    // Provider Validation Tests
    public function test_rejects_unknown_provider(): void
    {
        $response = $this->postJson('/api/webhooks/fal/unknown-provider', [], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        $this->assertEquals(400, $response->status());
    }

    public function test_accepts_valid_providers(): void
    {
        $validProviders = ['flux-pro', 'nano-banana', 'seed-dream-v4'];

        foreach ($validProviders as $provider) {
            $response = $this->postJson("/api/webhooks/fal/{$provider}", [], [
                'X-Fal-Webhook-Request-Id' => 'req-123',
                'X-Fal-Webhook-User-Id' => 'user-123',
                'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
                'X-Fal-Webhook-Signature' => 'invalid-sig',
            ]);

            // Should fail on signature, not provider validation
            $this->assertNotEquals(400, $response->status());
        }
    }

    // Idempotency Tests
    public function test_webhook_request_id_idempotency(): void
    {
        $requestId = 'idempotent-req-123';

        // Mark as processed
        $this->verificationService->markRequestAsProcessed($requestId);
        $this->assertTrue($this->verificationService->hasRequestBeenProcessed($requestId));

        // Should return 200 on replay
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [], [
            'X-Fal-Webhook-Request-Id' => $requestId,
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('already_processed', $response->json('status'));
    }

    // Cross-Provider Tampering Tests
    public function test_webhook_cannot_complete_different_provider_request(): void
    {
        // Create a request for NanoBanana
        $openai = UserOpenai::create([
            'user_id' => 1,
            'response' => 'NB', // NanoBanana
            'status' => 'IN_QUEUE',
            'request_id' => 'cross-provider-test-123',
        ]);

        // Try to complete it with FluxPro webhook
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [
            'request_id' => 'cross-provider-test-123',
            'status' => 'completed',
        ], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
            'X-Fal-Webhook-Signature' => 'test-signature',
        ]);

        // Should succeed but not update (cross-provider tampering prevention)
        $this->assertEquals(200, $response->status());

        // Request should still be IN_QUEUE
        $openai->refresh();
        $this->assertEquals('IN_QUEUE', $openai->status);
    }

    // Signature Verification Tests
    public function test_rejects_invalid_webhook_signature(): void
    {
        $response = $this->postJson('/api/webhooks/fal/flux-pro', [
            'request_id' => 'sig-test-123',
        ], [
            'X-Fal-Webhook-Request-Id' => 'req-123',
            'X-Fal-Webhook-User-Id' => 'user-123',
            'X-Fal-Webhook-Timestamp' => $this->testTimestamp,
            'X-Fal-Webhook-Signature' => 'invalid-signature-' . hash('sha256', 'random'),
        ]);

        $this->assertEquals(401, $response->status());
    }

    // Cache Cleanup Tests
    public function test_processed_requests_are_cached_for_idempotency(): void
    {
        Cache::flush();

        $requestId = 'cache-test-' . time();
        $this->assertFalse($this->verificationService->hasRequestBeenProcessed($requestId));

        $this->verificationService->markRequestAsProcessed($requestId);
        $this->assertTrue(Cache::has('fal:webhook:' . $requestId));
    }
}
