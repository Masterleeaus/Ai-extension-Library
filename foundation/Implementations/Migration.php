<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\MigrationContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class Migration implements MigrationContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'migrations_';
    private const TABLE_PROGRESS = self::TABLE_PREFIX . 'progress';
    private const TABLE_REGISTRY = self::TABLE_PREFIX . 'registry';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function start(
        string $tenantId,
        string $migrationId,
        string $sourceSystem,
        string $targetSystem,
        array $options = []
    ): string {
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_REGISTRY . " (id, tenant_id, source_system, target_system, options, status, started_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $migrationId,
            $tenantId,
            $sourceSystem,
            $targetSystem,
            json_encode($options),
            'running',
            DateTimeHelper::now(),
        ]);

        return $migrationId;
    }

    public function getStatus(
        string $tenantId,
        string $migrationId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_REGISTRY . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$migrationId, $tenantId]);
        $migration = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($migration) {
            $migration['options'] = JsonHelper::decode($migration['options']);
        }

        return $migration ?: null;
    }

    public function rollback(
        string $tenantId,
        string $migrationId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_REGISTRY . " SET status = ?, rolled_back_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['rolled_back', DateTimeHelper::now(), $migrationId, $tenantId]);
    }

    public function commit(
        string $tenantId,
        string $migrationId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_REGISTRY . " SET status = ?, committed_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['committed', DateTimeHelper::now(), $migrationId, $tenantId]);
    }

    public function getMigrationHistory(
        string $tenantId,
        ?string $sourceSystem = null,
        ?string $targetSystem = null
    ): array {
        $query = "SELECT * FROM " . self::TABLE_REGISTRY . " WHERE tenant_id = ?";
        $params = [$tenantId];

        if ($sourceSystem) {
            $query .= " AND source_system = ?";
            $params[] = $sourceSystem;
        }

        if ($targetSystem) {
            $query .= " AND target_system = ?";
            $params[] = $targetSystem;
        }

        $query .= " ORDER BY started_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['options'] = JsonHelper::decode($result['options']);
        }

        return $results;
    }

    public function pauseMigration(
        string $tenantId,
        string $migrationId,
        string $reason
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_REGISTRY . " SET status = ?, pause_reason = ?, paused_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['paused', $reason, DateTimeHelper::now(), $migrationId, $tenantId]);
    }

    public function resumeMigration(
        string $tenantId,
        string $migrationId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_REGISTRY . " SET status = ?, resumed_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['running', DateTimeHelper::now(), $migrationId, $tenantId]);
    }

    public function recordProgress(
        string $tenantId,
        string $migrationId,
        int $recordsProcessed,
        int $recordsFailed
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_PROGRESS . " (migration_id, tenant_id, records_processed, records_failed, recorded_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $migrationId,
            $tenantId,
            $recordsProcessed,
            $recordsFailed,
            DateTimeHelper::now(),
        ]);
    }
}
