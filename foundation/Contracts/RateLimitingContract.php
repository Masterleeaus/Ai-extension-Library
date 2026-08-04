<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface RateLimitingContract
{
    public function checkLimit(
        string $tenantId,
        string $identifier,
        string $bucket,
        int $limit,
        int $windowSeconds
    ): bool;

    public function recordRequest(
        string $tenantId,
        string $identifier,
        string $bucket
    ): int;

    public function getRemainingQuota(
        string $tenantId,
        string $identifier,
        string $bucket
    ): int;

    public function resetQuota(
        string $tenantId,
        string $identifier,
        string $bucket
    ): bool;

    public function setPolicy(
        string $tenantId,
        string $policyName,
        array $limits
    ): bool;

    public function getPolicy(
        string $tenantId,
        string $policyName
    ): ?array;

    public function getCurrentUsage(
        string $tenantId,
        string $identifier,
        string $bucket
    ): array;

    public function getMetrics(
        string $tenantId,
        ?string $bucket = null
    ): array;
}
