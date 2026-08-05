<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\MediaQuarantineContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class MediaQuarantine implements MediaQuarantineContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'media_quarantine_';
    private const TABLE_RECORDS = self::TABLE_PREFIX . 'records';
    private string $tablePrefix = self::TABLE_PREFIX;
    private string $quarantineDir;

    public function __construct(PDO $db, string $quarantineDir)
    {
        $this->db = $db;
        $this->quarantineDir = $quarantineDir;
    }

    public function quarantine(
        string $tenantId,
        string $mediaId,
        string $mediaType,
        string $sourcePath,
        array $metadata = []
    ): string {
        $quarantineId = bin2hex(random_bytes(16));
        $destinationPath = $this->quarantineDir . '/' . $quarantineId;

        if (!copy($sourcePath, $destinationPath)) {
            throw new \RuntimeException("Failed to quarantine media file");
        }

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_RECORDS . " (id, tenant_id, media_id, media_type, source_path, quarantine_path, metadata, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $quarantineId,
            $tenantId,
            $mediaId,
            $mediaType,
            $sourcePath,
            $destinationPath,
            json_encode($metadata),
            'quarantined',
            DateTimeHelper::now(),
        ]);

        return $quarantineId;
    }

    public function scan(
        string $tenantId,
        string $quarantineId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_RECORDS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$quarantineId, $tenantId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return ['status' => 'not_found'];
        }

        return [
            'quarantine_id' => $quarantineId,
            'status' => $record['status'],
            'scan_results' => JsonHelper::decode($record['scan_results'] ?? '{}'),
            'scanned_at' => $record['scanned_at'],
        ];
    }

    public function release(
        string $tenantId,
        string $quarantineId,
        string $destination
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT quarantine_path FROM " . self::TABLE_RECORDS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$quarantineId, $tenantId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record || !copy($record['quarantine_path'], $destination)) {
            return false;
        }

        $updateStmt = $this->db->prepare(
            "UPDATE " . self::TABLE_RECORDS . " SET status = ?, released_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $updateStmt->execute(['released', DateTimeHelper::now(), $quarantineId, $tenantId]);
    }

    public function reject(
        string $tenantId,
        string $quarantineId,
        string $reason
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_RECORDS . "
             SET status = ?, rejection_reason = ?, rejected_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['rejected', $reason, DateTimeHelper::now(), $quarantineId, $tenantId]);
    }

    public function getStatus(
        string $tenantId,
        string $quarantineId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_RECORDS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$quarantineId, $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listQuarantined(
        string $tenantId,
        array $filters = []
    ): array {
        $query = "SELECT * FROM " . self::TABLE_RECORDS . " WHERE tenant_id = ?";
        $params = [$tenantId];

        if (isset($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function purgeExpired(string $tenantId): int {
        $stmt = $this->db->prepare(
            "DELETE FROM " . self::TABLE_RECORDS . "
             WHERE tenant_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        $stmt->execute([$tenantId]);
        return $stmt->rowCount();
    }
}
