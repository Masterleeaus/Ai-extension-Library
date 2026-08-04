<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WebhookVerifierContract;

class WebhookVerifier implements WebhookVerifierContract
{
    private array $providers = [];
    private array $processedEvents = [];

    public function register(
        string $provider,
        string $secret,
        ?string $algorithm = 'sha256'
    ): bool {
        if (empty($provider) || empty($secret)) {
            return false;
        }

        $this->providers[$provider] = [
            'secret' => $secret,
            'algorithm' => $algorithm ?? 'sha256',
            'registered_at' => time(),
        ];

        return true;
    }

    public function verify(
        string $provider,
        string $payload,
        string $signature,
        ?array $headers = null
    ): bool {
        if (!isset($this->providers[$provider])) {
            return false;
        }

        $secret = $this->providers[$provider]['secret'];
        $algorithm = $this->providers[$provider]['algorithm'];

        $computedSignature = hash_hmac(
            $algorithm,
            $payload,
            $secret
        );

        return hash_equals($computedSignature, $signature);
    }

    public function checkReplay(
        string $provider,
        string $eventId,
        int $maxAgeSeconds = 300
    ): bool {
        $key = "{$provider}:{$eventId}";

        if (isset($this->processedEvents[$key])) {
            return false;
        }

        $this->processedEvents[$key] = time();

        return true;
    }

    public function recordEvent(
        string $provider,
        string $eventId,
        int $timestamp
    ): void {
        $key = "{$provider}:{$eventId}";
        $this->processedEvents[$key] = $timestamp;
    }

    public function resolveTenant(
        string $provider,
        array $payload,
        ?array $headers = null
    ): ?string {
        // Provider-specific tenant resolution strategies
        switch ($provider) {
            case 'whatsapp':
                return $payload['tenant_id'] ?? $headers['X-Tenant-ID'] ?? null;
            case 'slack':
                return $headers['X-Slack-Team-ID'] ?? $payload['team']['id'] ?? null;
            case 'gmail':
                return $headers['X-Tenant-ID'] ?? $payload['tenant_id'] ?? null;
            default:
                return $payload['tenant_id'] ?? $headers['X-Tenant-ID'] ?? null;
        }
    }
}
