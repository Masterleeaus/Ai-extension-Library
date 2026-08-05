<?php

declare(strict_types=1);

namespace Foundation\Webhooks\Providers;

use Foundation\Webhooks\BaseWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Security\RequestValidator;

/**
 * Webhook verifier for Twilio.
 *
 * Verifies Twilio webhook signatures using HMAC-SHA1 and enforces
 * fail-closed behavior when credentials are missing.
 */
class TwilioWebhookVerifier extends BaseWebhookVerifier
{
    public function getProviderName(): string
    {
        return 'twilio';
    }

    public function hasRequiredCredentials(string $tenantId): bool
    {
        $token = $this->getCredential($tenantId, 'twilio_auth_token');
        return !empty($token);
    }

    protected function verifySignature(Request $request, string $tenantId): bool
    {
        $token = $this->getCredential($tenantId, 'twilio_auth_token');

        if (empty($token)) {
            Log::error('[Twilio] Webhook rejected: no auth token configured', [
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $signature = $request->header('X-Twilio-Signature', '');

        if (empty($signature)) {
            Log::warning('[Twilio] Webhook rejected: missing X-Twilio-Signature header', [
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $isValid = (new RequestValidator($token))->validate(
            $signature,
            $request->fullUrl(),
            $request->post()
        );

        if (!$isValid) {
            Log::warning('[Twilio] Webhook rejected: invalid signature', [
                'tenant_id' => $tenantId,
            ]);
        }

        return $isValid;
    }

    protected function extractEventId(Request $request): ?string
    {
        // For Twilio, use CallSid as the event ID (most important identifier)
        return $request->input('CallSid');
    }

    protected function extractTimestamp(Request $request): ?int
    {
        // Twilio doesn't include timestamp in most webhooks
        // Use current time as fallback (close to real-time)
        return (int) (microtime(true) * 1000);
    }

    protected function extractContext(Request $request): array
    {
        return [
            'call_sid' => $request->input('CallSid'),
            'from' => $request->input('From'),
            'to' => $request->input('To'),
            'status' => $request->input('CallStatus'),
        ];
    }

    protected function getCredential(string $tenantId, string $key): ?string
    {
        // In a real implementation, fetch from vault-backed config
        // For now, use setting helper (will be replaced in full implementation)
        if ($key === 'twilio_auth_token') {
            return setting('twilio_auth_token');
        }
        return null;
    }
}
