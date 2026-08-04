<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface AuditTrailContract
{
    public function record(
        string $tenantId,
        string $action,
        string $resourceType,
        string $resourceId,
        array $changes,
        ?string $userId = null,
        array $metadata = []
    ): string;

    public function getEntry(
        string $tenantId,
        string $entryId
    ): ?array;

    public function getHistory(
        string $tenantId,
        string $resourceType,
        string $resourceId
    ): array;

    public function search(
        string $tenantId,
        array $filters = []
    ): array;

    public function getUserActivity(
        string $tenantId,
        string $userId,
        ?int $limit = null
    ): array;

    public function getResourceAudit(
        string $tenantId,
        string $resourceType,
        string $resourceId,
        ?int $limit = null
    ): array;

    public function export(
        string $tenantId,
        array $filters = []
    ): string;

    public function purgeOldEntries(
        string $tenantId,
        int $daysToKeep
    ): int;
}
