<?php

declare(strict_types=1);

namespace Foundation\Webhooks\Providers;

use Foundation\Webhooks\BaseWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook verifier for ElevenLabs.
 *
 * Verifies ElevenLabs webhook signatures using HMAC-SHA256 with timestamp
 * and enforces fail-closed behavior when credentials are missing.
 */
class ElevenLabsWebhookVerifier extends BaseWebhookVerifier
{
    public function getProviderName(): string
    {
        return 'elevenlabs';
    }

    public function hasRequiredCredentials(string $tenantId): bool
    {
        $secret = $this->getCredential($tenantId, 'elevenlabs_webhook_secret');
        return !empty($secret);
    }

    protected function verifySignature(Request $request, string $tenantId): bool
    {
        $secret = $this->getCredential($tenantId, 'elevenlabs_webhook_secret');

        if (empty($secret)) {
            Log::error('[ElevenLabs] Webhook rejected: no webhook secret configured', [
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $header = $request->header('ElevenLabs-Signature', '');
        $body = $request->getContent();

        if (empty($header)) {
            Log::warning('[ElevenLabs] Webhook rejected: missing ElevenLabs-Signature header', [
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        // header format: t=<timestamp>,v0=<hex_signature>
        preg_match('/t=(\d+)/', $header, $tMatch);
        preg_match('/v0=([a-f0-9]+)/', $header, $vMatch);

        if (empty($tMatch[1]) || empty($vMatch[1])) {
            Log::warning('[ElevenLabs] Webhook rejected: invalid signature header format', [
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $timestamp = (int) $tMatch[1];
        $receivedSig = $vMatch[1];

        // ElevenLabs signs: HMAC-SHA256(secret, "{timestamp}.{body}")
        $expectedWithDot = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
        // Fallback: some versions sign without dot separator
        $expectedNoDot = hash_hmac('sha256', "{$timestamp}{$body}", $secret);

        $isValid = hash_equals($expectedWithDot, $receivedSig) || hash_equals($expectedNoDot, $receivedSig);

        if (!$isValid) {
            Log::warning('[ElevenLabs] Webhook rejected: invalid signature', [
                'tenant_id' => $tenantId,
            ]);
        }

        return $isValid;
    }

    protected function extractEventId(Request $request): ?string
    {
        $payload = $request->all();
        $type = $payload['type'] ?? null;

        if ($type === 'post_call_transcription') {
            $data = $payload['data'] ?? [];
            return $data['conversation_id'] ?? null;
        }

        // For real-time events, use conversation_id
        return $payload['conversation_id'] ?? null;
    }

    protected function extractTimestamp(Request $request): ?int
    {
        $header = $request->header('ElevenLabs-Signature', '');
        preg_match('/t=(\d+)/', $header, $matches);

        if (!empty($matches[1])) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function extractContext(Request $request): array
    {
        $payload = $request->all();

        return [
            'event_type' => $payload['type'] ?? null,
            'agent_id' => $payload['agent_id'] ?? null,
            'conversation_id' => $payload['conversation_id'] ?? null,
        ];
    }

    protected function getCredential(string $tenantId, string $key): ?string
    {
        // In a real implementation, fetch from vault-backed config
        if ($key === 'elevenlabs_webhook_secret') {
            return setting('elevenlabs_webhook_secret');
        }
        return null;
    }
}
