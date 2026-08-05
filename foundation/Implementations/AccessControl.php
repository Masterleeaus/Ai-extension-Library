<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\AccessControlContract;
use Foundation\Contracts\DatabaseRepositoryContract;
use Foundation\Support\DateTimeHelper;
use PDO;

class AccessControl implements AccessControlContract
{
    private DatabaseRepositoryContract $repository;
    private PDO $db;
    private const TABLE_PERMISSIONS = 'access_permissions';
    private const TABLE_ROLES = 'access_roles';
    private const TABLE_ROLE_PERMS = 'access_role_perms';

    public function __construct(DatabaseRepositoryContract $repository)
    {
        $this->repository = $repository;
        $this->db = $repository->getPDO();
    }

    public function grantPermission(
        string $tenantId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $permission
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO " . self::TABLE_PERMISSIONS . "
             (tenant_id, user_id, resource_type, resource_id, permission, granted_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $tenantId,
            $userId,
            $resourceType,
            $resourceId,
            $permission,
            DateTimeHelper::now(),
        ]);
    }

    public function revokePermission(
        string $tenantId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $permission
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM " . self::TABLE_PERMISSIONS . "
             WHERE tenant_id = ? AND user_id = ? AND resource_type = ? AND resource_id = ? AND permission = ?"
        );

        return $stmt->execute([
            $tenantId,
            $userId,
            $resourceType,
            $resourceId,
            $permission,
        ]);
    }

    public function hasPermission(
        string $tenantId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $permission
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM " . self::TABLE_PERMISSIONS . "
             WHERE tenant_id = ? AND user_id = ? AND resource_type = ? AND resource_id = ? AND permission = ?
             LIMIT 1"
        );

        $stmt->execute([
            $tenantId,
            $userId,
            $resourceType,
            $resourceId,
            $permission,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getUserPermissions(
        string $tenantId,
        string $userId,
        ?string $resourceType = null
    ): array {
        $query = "SELECT * FROM " . self::TABLE_PERMISSIONS . "
                  WHERE tenant_id = ? AND user_id = ?";
        $params = [$tenantId, $userId];

        if ($resourceType) {
            $query .= " AND resource_type = ?";
            $params[] = $resourceType;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResourceAccess(
        string $tenantId,
        string $resourceType,
        string $resourceId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT user_id, permission FROM " . self::TABLE_PERMISSIONS . "
             WHERE tenant_id = ? AND resource_type = ? AND resource_id = ?"
        );

        $stmt->execute([$tenantId, $resourceType, $resourceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignRole(
        string $tenantId,
        string $userId,
        string $role
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO " . self::TABLE_ROLES . " (tenant_id, user_id, role, assigned_at)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([$tenantId, $userId, $role, DateTimeHelper::now()]);
    }

    public function revokeRole(
        string $tenantId,
        string $userId,
        string $role
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM " . self::TABLE_ROLES . "
             WHERE tenant_id = ? AND user_id = ? AND role = ?"
        );

        return $stmt->execute([$tenantId, $userId, $role]);
    }

    public function getRolePermissions(
        string $tenantId,
        string $role
    ): array {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT permission FROM " . self::TABLE_ROLE_PERMS . "
             WHERE tenant_id = ? AND role = ?"
        );

        $stmt->execute([$tenantId, $role]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permission');
    }
}
