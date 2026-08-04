<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface MigrationContract
{
    public function start(
        string $tenantId,
        string $migrationId,
        string $sourceSystem,
        string $targetSystem,
        array $options = []
    ): string;

    public function getStatus(
        string $tenantId,
        string $migrationId
    ): ?array;

    public function rollback(
        string $tenantId,
        string $migrationId
    ): bool;

    public function commit(
        string $tenantId,
        string $migrationId
    ): bool;

    public function getMigrationHistory(
        string $tenantId,
        ?string $sourceSystem = null,
        ?string $targetSystem = null
    ): array;

    public function pauseMigration(
        string $tenantId,
        string $migrationId,
        string $reason
    ): bool;

    public function resumeMigration(
        string $tenantId,
        string $migrationId
    ): bool;

    public function recordProgress(
        string $tenantId,
        string $migrationId,
        int $recordsProcessed,
        int $recordsFailed
    ): void;
}
