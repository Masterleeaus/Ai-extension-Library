<?php

declare(strict_types=1);

namespace Foundation\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Base webhook verifier with common functionality.
 *
 * Provides shared webhook verification logic including:
 * - Fail-closed credential checking
 * - Timestamp freshness validation
 * - Replay detection via idempotency tracking
 * - Payload size limits
 * - Standardized logging
 */
abstract class BaseWebhookVerifier implements WebhookVerificationContract
{
    /**
     * Maximum age for webhook timestamps (5 minutes in milliseconds).
     */
    protected int $maxTimestampAge = 5 * 60 * 1000;

    /**
     * Maximum webhook payload size (10 MB).
     */
    protected int $maxPayloadSize = 10 * 1024 * 1024;

    /**
     * TTL for idempotency tracking (24 hours in seconds).
     */
    protected int $idempotencyTtl = 24 * 60 * 60;

    /**
     * Get the provider name.
     */
    abstract public function getProviderName(): string;

    /**
     * Verify the webhook signature.
     *
     * Subclasses must implement provider-specific signature verification.
     *
     * @return bool True if signature is valid
     */
    abstract protected function verifySignature(Request $request, string $tenantId): bool;

    /**
     * Extract the event ID from the webhook (for idempotency).
     *
     * Subclasses must implement provider-specific event ID extraction.
     *
     * @return ?string Event ID or null if not found
     */
    abstract protected function extractEventId(Request $request): ?string;

    /**
     * Extract the timestamp from the webhook.
     *
     * Subclasses must implement provider-specific timestamp extraction.
     *
     * @return ?int Timestamp in milliseconds or null if not found
     */
    abstract protected function extractTimestamp(Request $request): ?int;

    /**
     * Extract verified context (provider-specific data).
     *
     * @return array Context to include in verification result
     */
    protected function extractContext(Request $request): array
    {
        return [];
    }

    /**
     * Verify the webhook request.
     *
     * @param Request $request The incoming webhook request
     * @param string $tenantId The tenant owning this webhook endpoint
     * @return WebhookVerificationResult Result with verification status and context
     */
    public function verify(Request $request, string $tenantId): WebhookVerificationResult
    {
        // SECURITY: Fail closed when credentials missing
        if (!$this->hasRequiredCredentials($tenantId)) {
            Log::error("Webhook rejected: no credentials configured", [
                'provider' => $this->getProviderName(),
                'tenant_id' => $tenantId,
            ]);
            return WebhookVerificationResult::invalid(
                $this->getProviderName(),
                $tenantId,
                'Webhook verification not configured',
            );
        }

        // SECURITY: Check payload size
        if ($request->getContentLength() > $this->maxPayloadSize) {
            Log::warning("Webhook rejected: payload too large", [
                'provider' => $this->getProviderName(),
                'tenant_id' => $tenantId,
                'size' => $request->getContentLength(),
                'max_size' => $this->maxPayloadSize,
            ]);
            return WebhookVerificationResult::invalid(
                $this->getProviderName(),
                $tenantId,
                'Payload size exceeds maximum',
            );
        }

        // SECURITY: Verify signature
        if (!$this->verifySignature($request, $tenantId)) {
            Log::warning("Webhook rejected: invalid signature", [
                'provider' => $this->getProviderName(),
                'tenant_id' => $tenantId,
            ]);
            return WebhookVerificationResult::invalid(
                $this->getProviderName(),
                $tenantId,
                'Invalid webhook signature',
            );
        }

        // Extract event ID and timestamp
        $eventId = $this->extractEventId($request);
        $timestamp = $this->extractTimestamp($request);

        if (!$eventId) {
            Log::warning("Webhook rejected: missing event ID", [
                'provider' => $this->getProviderName(),
                'tenant_id' => $tenantId,
            ]);
            return WebhookVerificationResult::invalid(
                $this->getProviderName(),
                $tenantId,
                'Missing event identifier',
            );
        }

        if ($timestamp === null) {
            Log::warning("Webhook rejected: missing timestamp", [
                'provider' => $this->getProviderName(),
                'tenant_id' => $tenantId,
                'event_id' => $eventId,
            ]);
            return WebhookVerificationResult::invalid(
                $this->getProviderName(),
                $tenantId,
                'Missing or invalid timestamp',
            );
        }

        // SECURITY: Enforce timestamp freshness
        $now = (int) (microtime(true) * 1000);
        $age = $now - $timestamp;

        if ($age < 0 || $age > $this->maxTimestampAge) {
            Log::warning("Webhook rejected: timestamp outside freshness window", [
                'provider' => $this->getProviderName(),
                'tenant_id' => $tenantId,
                'event_id' => $eventId,
                'age_ms' => $age,
                'max_age_ms' => $this->maxTimestampAge,
            ]);
            return WebhookVerificationResult::invalid(
                $this->getProviderName(),
                $tenantId,
                'Webhook timestamp outside acceptable window',
            );
        }

        // SECURITY: Check for replayed events
        $idempotencyKey = "webhook:{$this->getProviderName()}:{$tenantId}:{$eventId}";
        if (Cache::has($idempotencyKey)) {
            Log::warning("Webhook rejected: replayed event", [
                'provider' => $this->getProviderName(),
                'tenant_id' => $tenantId,
                'event_id' => $eventId,
            ]);
            return WebhookVerificationResult::invalid(
                $this->getProviderName(),
                $tenantId,
                'Duplicate event (already processed)',
            );
        }

        // Mark event as processed
        Cache::put($idempotencyKey, true, $this->idempotencyTtl);

        // Extract verified context
        $context = $this->extractContext($request);

        Log::info("Webhook verified successfully", [
            'provider' => $this->getProviderName(),
            'tenant_id' => $tenantId,
            'event_id' => $eventId,
        ]);

        return WebhookVerificationResult::valid(
            $this->getProviderName(),
            $tenantId,
            $eventId,
            $timestamp,
            $context,
        );
    }

    /**
     * Check if required verification credentials are configured.
     *
     * Default implementation: subclasses should override for specific credentials.
     *
     * @param string $tenantId The tenant to check credentials for
     * @return bool True if all required credentials are available
     */
    public function hasRequiredCredentials(string $tenantId): bool
    {
        // Subclasses should override this method to check for specific credentials
        return true;
    }

    /**
     * Get a credential for the tenant.
     *
     * Helper method for subclasses to retrieve configured secrets.
     *
     * @param string $tenantId Tenant ID
     * @param string $key Credential key
     * @return ?string Credential value or null if not configured
     */
    protected function getCredential(string $tenantId, string $key): ?string
    {
        // In a real implementation, this would fetch from secure storage
        // For now, return null to force subclasses to implement their own
        return null;
    }
}
