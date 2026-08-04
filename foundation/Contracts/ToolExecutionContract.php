<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ToolExecutionContract
{
    public function execute(
        string $tenantId,
        string $toolId,
        array $params = [],
        array $context = []
    ): array;

    public function authorize(
        TenantContextContract $context,
        string $toolId,
        array $params = []
    ): bool;

    public function getRateLimit(string $tenantId, string $toolId): ?int;

    public function recordExecution(
        string $tenantId,
        string $toolId,
        array $execution,
        float $costEstimate = 0.0
    ): void;

    public function listTools(string $tenantId): array;

    public function getToolDefinition(string $toolId): ?array;
}
