<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\AccessControlContract;
use Foundation\Contracts\DatabaseRepositoryContract;

class AccessControl implements AccessControlContract
{
    private DatabaseRepositoryContract $repository;
    private string $tablePrefix = 'access_';

    public function __construct(DatabaseRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function grantPermission(
        string $tenantId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $permission
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO {$this->tablePrefix}permissions
             (tenant_id, user_id, resource_type, resource_id, permission, granted_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $tenantId,
            $userId,
            $resourceType,
            $resourceId,
            $permission,
            date('c'),
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
            "DELETE FROM {$this->tablePrefix}permissions
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
        try {
            $stmt = $this->helper->safePrepare(
                "SELECT 1 FROM {$this->tablePrefix}permissions
                 WHERE tenant_id = ? AND user_id = ? AND resource_type = ? AND resource_id = ? AND permission = ?
                 LIMIT 1"
            );

            $this->helper->safeExecute($stmt, [
                $tenantId,
                $userId,
                $resourceType,
                $resourceId,
                $permission,
            ], 'SELECT');

            return $stmt->rowCount() > 0;
        } catch (DatabaseException $e) {
            error_log("Database error in hasPermission: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getUserPermissions(
        string $tenantId,
        string $userId,
        ?string $resourceType = null
    ): array {
        try {
            $query = "SELECT * FROM {$this->tablePrefix}permissions
                      WHERE tenant_id = ? AND user_id = ?";
            $params = [$tenantId, $userId];

            if ($resourceType) {
                $query .= " AND resource_type = ?";
                $params[] = $resourceType;
            }

            $stmt = $this->helper->safePrepare($query);
            $this->helper->safeExecute($stmt, $params, 'SELECT');

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (DatabaseException $e) {
            error_log("Database error in getUserPermissions: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getResourceAccess(
        string $tenantId,
        string $resourceType,
        string $resourceId
    ): array {
        try {
            $stmt = $this->helper->safePrepare(
                "SELECT user_id, permission FROM {$this->tablePrefix}permissions
                 WHERE tenant_id = ? AND resource_type = ? AND resource_id = ?"
            );

            $this->helper->safeExecute($stmt, [$tenantId, $resourceType, $resourceId], 'SELECT');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (DatabaseException $e) {
            error_log("Database error in getResourceAccess: {$e->getMessage()}");
            throw $e;
        }
    }

    public function assignRole(
        string $tenantId,
        string $userId,
        string $role
    ): bool {
        try {
            $stmt = $this->helper->safePrepare(
                "INSERT IGNORE INTO {$this->tablePrefix}roles (tenant_id, user_id, role, assigned_at)
                 VALUES (?, ?, ?, ?)"
            );

            return $this->helper->safeExecute($stmt, [$tenantId, $userId, $role, date('c')], 'INSERT');
        } catch (DatabaseException $e) {
            error_log("Database error in assignRole: {$e->getMessage()}");
            throw $e;
        }
    }

    public function revokeRole(
        string $tenantId,
        string $userId,
        string $role
    ): bool {
        try {
            $stmt = $this->helper->safePrepare(
                "DELETE FROM {$this->tablePrefix}roles
                 WHERE tenant_id = ? AND user_id = ? AND role = ?"
            );

            return $this->helper->safeExecute($stmt, [$tenantId, $userId, $role], 'DELETE');
        } catch (DatabaseException $e) {
            error_log("Database error in revokeRole: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getRolePermissions(
        string $tenantId,
        string $role
    ): array {
        try {
            $stmt = $this->helper->safePrepare(
                "SELECT DISTINCT permission FROM {$this->tablePrefix}role_perms
                 WHERE tenant_id = ? AND role = ?"
            );

            $this->helper->safeExecute($stmt, [$tenantId, $role], 'SELECT');
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permission');
        } catch (DatabaseException $e) {
            error_log("Database error in getRolePermissions: {$e->getMessage()}");
            throw $e;
        }
    }
}
