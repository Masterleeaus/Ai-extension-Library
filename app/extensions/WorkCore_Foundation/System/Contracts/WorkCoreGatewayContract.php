<?php

namespace Extensions\WorkCore_Foundation\System\Contracts;

/**
 * WorkCore Gateway Contract
 * Defines the interface for all WorkCore operations
 * Issues #181-#186: Shared Foundation contracts
 */
interface WorkCoreGatewayContract
{
    /**
     * Execute a read query against WorkCore
     */
    public function query(string $endpoint, array $params = []): QueryResponse;

    /**
     * Execute a governed action that may require approval
     */
    public function action(string $endpoint, array $params = []): ActionResponse;

    /**
     * Get current tenant context
     */
    public function getTenantContext(): TenantContextContract;

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool;

    /**
     * Check if action is authorized
     */
    public function isAuthorized(string $action, array $params = []): bool;

    /**
     * Get credential reference
     */
    public function getCredential(string $key): string;

    /**
     * Publish event for idempotent consumption
     */
    public function publishEvent(EventEnvelopeContract $event): EventResponse;
}

interface QueryResponse
{
    public function getData(): array;
    public function getStatus(): int;
    public function isSuccess(): bool;
}

interface ActionResponse
{
    public function getStatus(): string; // 'pending', 'approved', 'rejected', 'completed'
    public function getResult(): array;
    public function getApprovalId(): ?string;
    public function requiresApproval(): bool;
}

interface EventResponse
{
    public function isDeduped(): bool;
    public function getEventId(): string;
    public function isProcessed(): bool;
}

interface TenantContextContract
{
    public function getTenantId(): string;
    public function getUserId(): string;
    public function getPermissions(): array;
    public function hasPermission(string $permission): bool;
}

interface EventEnvelopeContract
{
    public function getEventId(): string;
    public function getIdempotencyKey(): string;
    public function getTenantId(): string;
    public function getEventType(): string;
    public function getPayload(): array;
}
