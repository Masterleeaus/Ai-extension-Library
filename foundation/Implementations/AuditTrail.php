<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\AuditTrailContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class AuditTrail implements AuditTrailContract
{
    private PDO $db;
    private string $tablePrefix = 'audit_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function record(
        string $tenantId,
        string $action,
        string $resourceType,
        string $resourceId,
        array $changes,
        ?string $userId = null,
        array $metadata = []
    ): string {
        $entryId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}entries (id, tenant_id, action, resource_type, resource_id, changes, user_id, metadata, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $entryId,
            $tenantId,
            $action,
            $resourceType,
            $resourceId,
            json_encode($changes),
            $userId,
            json_encode($metadata),
            DateTimeHelper::now(),
        ]);

        return $entryId;
    }

    public function getEntry(
        string $tenantId,
        string $entryId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}entries WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$entryId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['changes'] = JsonHelper::decode($result['changes']);
            $result['metadata'] = JsonHelper::decode($result['metadata']);
        }

        return $result ?: null;
    }

    public function getHistory(
        string $tenantId,
        string $resourceType,
        string $resourceId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}entries
             WHERE tenant_id = ? AND resource_type = ? AND resource_id = ?
             ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId, $resourceType, $resourceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(
        string $tenantId,
        array $filters = []
    ): array {
        $query = "SELECT * FROM {$this->tablePrefix}entries WHERE tenant_id = ?";
        $params = [$tenantId];

        if (isset($filters['action'])) {
            $query .= " AND action = ?";
            $params[] = $filters['action'];
        }

        if (isset($filters['resource_type'])) {
            $query .= " AND resource_type = ?";
            $params[] = $filters['resource_type'];
        }

        if (isset($filters['user_id'])) {
            $query .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserActivity(
        string $tenantId,
        string $userId,
        ?int $limit = null
    ): array {
        $query = "SELECT * FROM {$this->tablePrefix}entries
                  WHERE tenant_id = ? AND user_id = ?
                  ORDER BY created_at DESC";
        $params = [$tenantId, $userId];

        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResourceAudit(
        string $tenantId,
        string $resourceType,
        string $resourceId,
        ?int $limit = null
    ): array {
        $query = "SELECT * FROM {$this->tablePrefix}entries
                  WHERE tenant_id = ? AND resource_type = ? AND resource_id = ?
                  ORDER BY created_at DESC";
        $params = [$tenantId, $resourceType, $resourceId];

        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function export(
        string $tenantId,
        array $filters = []
    ): string {
        $entries = $this->search($tenantId, $filters);
        return json_encode($entries, JSON_PRETTY_PRINT);
    }

    public function purgeOldEntries(
        string $tenantId,
        int $daysToKeep
    ): int {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}entries
             WHERE tenant_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );

        $stmt->execute([$tenantId, $daysToKeep]);
        return $stmt->rowCount();
    }
}
