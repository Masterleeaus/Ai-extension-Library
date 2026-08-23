<?php

declare(strict_types=1);

namespace Foundation\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Centralized webhook verification service.
 *
 * Coordinates webhook verification across all providers using registered verifiers.
 * Ensures consistent fail-closed security posture across all integrations.
 */
class WebhookVerificationService
{
    /**
     * Registered webhook verifiers by provider.
     *
     * @var array<string, WebhookVerificationContract>
     */
    private array $verifiers = [];

    /**
     * Register a webhook verifier for a provider.
     *
     * @param WebhookVerificationContract $verifier The verifier implementation
     */
    public function register(WebhookVerificationContract $verifier): void
    {
        $provider = $verifier->getProviderName();
        $this->verifiers[$provider] = $verifier;
        Log::debug("Registered webhook verifier", ['provider' => $provider]);
    }

    /**
     * Verify a webhook request for a specific provider.
     *
     * @param string $provider The provider name (e.g., 'twilio', 'elevenlabs')
     * @param Request $request The incoming webhook request
     * @param string $tenantId The tenant owning this webhook
     * @return WebhookVerificationResult Verification result with context
     */
    public function verify(string $provider, Request $request, string $tenantId): WebhookVerificationResult
    {
        $verifier = $this->verifiers[$provider] ?? null;

        if (!$verifier) {
            Log::error("Webhook rejected: provider not registered", [
                'provider' => $provider,
                'tenant_id' => $tenantId,
            ]);
            return WebhookVerificationResult::invalid(
                $provider,
                $tenantId,
                'Provider not supported',
            );
        }

        return $verifier->verify($request, $tenantId);
    }

    /**
     * Get a verifier for a specific provider.
     *
     * @param string $provider The provider name
     * @return ?WebhookVerificationContract The verifier or null if not registered
     */
    public function getVerifier(string $provider): ?WebhookVerificationContract
    {
        return $this->verifiers[$provider] ?? null;
    }

    /**
     * Check if a provider has a registered verifier.
     *
     * @param string $provider The provider name
     * @return bool True if registered
     */
    public function hasVerifier(string $provider): bool
    {
        return isset($this->verifiers[$provider]);
    }

    /**
     * Get all registered providers.
     *
     * @return array<string> Array of provider names
     */
    public function getRegisteredProviders(): array
    {
        return array_keys($this->verifiers);
    }
}
