<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WebhookSecurityContract
{
    public function registerEndpoint(
        string $tenantId,
        string $endpointUrl,
        array $events,
        string $secret
    ): string;

    public function unregisterEndpoint(
        string $tenantId,
        string $endpointId
    ): bool;

    public function validateSignature(
        string $payload,
        string $signature,
        string $secret
    ): bool;

    public function verifyTenant(
        string $endpointId,
        string $tenantId
    ): bool;

    public function dispatchEvent(
        string $tenantId,
        string $eventType,
        array $payload
    ): array;

    public function retryFailed(
        string $tenantId,
        string $webhookId,
        int $maxAttempts = 3
    ): bool;

    public function getDeliveryStatus(
        string $tenantId,
        string $webhookId
    ): ?array;

    public function listEndpoints(
        string $tenantId,
        ?string $event = null
    ): array;
}
