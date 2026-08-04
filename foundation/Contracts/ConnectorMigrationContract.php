<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ConnectorMigrationContract
{
    public function assessConnector(
        string $tenantId,
        string $connectorId
    ): array;

    public function planMigration(
        string $tenantId,
        string $connectorId,
        string $targetRuntime
    ): array;

    public function executeConformance(
        string $tenantId,
        string $connectorId,
        array $conformanceRules
    ): array;

    public function validateConnectorOutput(
        string $tenantId,
        string $connectorId,
        array $testData
    ): bool;

    public function migrateConnectorConfig(
        string $tenantId,
        string $connectorId,
        string $targetRuntime
    ): bool;

    public function testConnectorCapabilities(
        string $tenantId,
        string $connectorId,
        array $capabilities
    ): array;

    public function recordMigrationPath(
        string $tenantId,
        string $connectorId,
        array $migrationPath
    ): void;

    public function getRollbackPlan(
        string $tenantId,
        string $connectorId
    ): ?array;
}
