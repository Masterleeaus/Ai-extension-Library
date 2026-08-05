<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\FileOwnershipContract;
use PDO;

class FileOwnership implements FileOwnershipContract
{
    private PDO $db;
    private string $tablePrefix = 'file_ownership_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function assertOwnership(
        string $tenantId,
        string $filePath,
        string $userId
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->tablePrefix}registry WHERE tenant_id = ? AND file_path = ? AND owner_id = ?"
        );

        $stmt->execute([$tenantId, $filePath, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function transferOwnership(
        string $tenantId,
        string $filePath,
        string $fromUserId,
        string $toUserId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}registry SET owner_id = ?, transferred_at = ? WHERE tenant_id = ? AND file_path = ? AND owner_id = ?"
        );

        return $stmt->execute([$toUserId, DateTimeHelper::now(), $tenantId, $filePath, $fromUserId]);
    }

    public function getOwner(
        string $tenantId,
        string $filePath
    ): ?string {
        $stmt = $this->db->prepare(
            "SELECT owner_id FROM {$this->tablePrefix}registry WHERE tenant_id = ? AND file_path = ?"
        );

        $stmt->execute([$tenantId, $filePath]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['owner_id'] : null;
    }

    public function listOwned(
        string $tenantId,
        string $userId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT file_path FROM {$this->tablePrefix}registry WHERE tenant_id = ? AND owner_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId, $userId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'file_path');
    }

    public function isAccessible(
        string $tenantId,
        string $filePath,
        string $userId,
        string $permission
    ): bool {
        $owner = $this->getOwner($tenantId, $filePath);

        if ($owner === $userId) {
            return true;
        }

        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->tablePrefix}access WHERE tenant_id = ? AND file_path = ? AND user_id = ? AND permission = ?"
        );

        $stmt->execute([$tenantId, $filePath, $userId, $permission]);
        return $stmt->rowCount() > 0;
    }

    public function grantAccess(
        string $tenantId,
        string $filePath,
        string $userId,
        string $permission
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO {$this->tablePrefix}access (tenant_id, file_path, user_id, permission, granted_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([$tenantId, $filePath, $userId, $permission, DateTimeHelper::now()]);
    }

    public function revokeAccess(
        string $tenantId,
        string $filePath,
        string $userId,
        string $permission
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}access WHERE tenant_id = ? AND file_path = ? AND user_id = ? AND permission = ?"
        );

        return $stmt->execute([$tenantId, $filePath, $userId, $permission]);
    }

    public function auditAccess(
        string $tenantId,
        string $filePath
    ): array {
        $stmt = $this->db->prepare(
            "SELECT user_id, permission, granted_at FROM {$this->tablePrefix}access WHERE tenant_id = ? AND file_path = ? ORDER BY granted_at DESC"
        );

        $stmt->execute([$tenantId, $filePath]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
