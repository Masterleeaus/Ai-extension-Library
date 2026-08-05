<?php

declare(strict_types=1);

namespace Foundation\Tools\Services;

/**
 * Service for recording tool execution audit trails.
 *
 * Records all tool executions with context, status, and outcomes.
 */
interface ToolAuditService
{
    /**
     * Record a tool execution.
     *
     * @param string $tenantId Tenant ID
     * @param string $userId User ID
     * @param string $toolName Tool name
     * @param string $status Execution status (success, denied, failed, error)
     * @param string $correlationId Correlation ID for tracing
     * @param array $parameters Tool parameters (sanitized)
     * @param mixed $result Tool execution result
     * @param ?string $errorDetail Error message if execution failed
     */
    public function recordToolExecution(
        string $tenantId,
        string $userId,
        string $toolName,
        string $status,
        string $correlationId,
        array $parameters,
        mixed $result,
        ?string $errorDetail = null
    ): void;

    /**
     * Get audit trail for a tool execution.
     *
     * @param string $correlationId Correlation ID
     * @return ?array Audit record or null if not found
     */
    public function getAuditTrail(string $correlationId): ?array;
}
