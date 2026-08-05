<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkCoreFoundationContract;
use PDO;
use Foundation\Support\JsonHelper;

class WorkCoreFoundation implements WorkCoreFoundationContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'workcore_foundation_';
    private const TABLE_PERMISSIONS = self::TABLE_PREFIX . 'permissions';
    private const TABLE_SYNCS = self::TABLE_PREFIX . 'syncs';
    private const TABLE_TENANTS = self::TABLE_PREFIX . 'tenants';
    private const TABLE_USER_ROLES = self::TABLE_PREFIX . 'user_roles';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function registerTenant(
        string $tenantId,
        array $tenantConfig
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_TENANTS . " (tenant_id, config, registered_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE config = ?, updated_at = ?"
        );

        $configJson = json_encode($tenantConfig);
        $now = DateTimeHelper::now();

        return $stmt->execute([
            $tenantId,
            $configJson,
            $now,
            $configJson,
            $now,
        ]);
    }

    public function getTenantConfig(
        string $tenantId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT config FROM " . self::TABLE_TENANTS . " WHERE tenant_id = ?"
        );

        $stmt->execute([$tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return JsonHelper::decode($result['config']);
        }

        return null;
    }

    public function updateTenantConfig(
        string $tenantId,
        array $updates
    ): bool {
        $currentConfig = $this->getTenantConfig($tenantId);

        if (!$currentConfig) {
            return false;
        }

        $mergedConfig = array_merge($currentConfig, $updates);

        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_TENANTS . " SET config = ?, updated_at = ? WHERE tenant_id = ?"
        );

        return $stmt->execute([json_encode($mergedConfig), DateTimeHelper::now(), $tenantId]);
    }

    public function grantPermission(
        string $tenantId,
        string $userId,
        string $resource,
        string $action
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO " . self::TABLE_PERMISSIONS . " (tenant_id, user_id, resource, action, granted_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([$tenantId, $userId, $resource, $action, DateTimeHelper::now()]);
    }

    public function checkPermission(
        string $tenantId,
        string $userId,
        string $resource,
        string $action
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM " . self::TABLE_PERMISSIONS . " WHERE tenant_id = ? AND user_id = ? AND resource = ? AND action = ?"
        );

        $stmt->execute([$tenantId, $userId, $resource, $action]);
        return $stmt->rowCount() > 0;
    }

    public function listUserRoles(
        string $tenantId,
        string $userId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT role FROM " . self::TABLE_USER_ROLES . " WHERE tenant_id = ? AND user_id = ?"
        );

        $stmt->execute([$tenantId, $userId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'role');
    }

    public function syncWithFoundation(
        string $tenantId,
        array $data
    ): bool {
        $syncId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_SYNCS . " (id, tenant_id, data, status, synced_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $syncId,
            $tenantId,
            json_encode($data),
            'completed',
            DateTimeHelper::now(),
        ]);
    }

    public function getFoundationStatus(
        string $tenantId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT tenant_id, registered_at, updated_at FROM " . self::TABLE_TENANTS . " WHERE tenant_id = ?"
        );

        $stmt->execute([$tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return [
                'tenant_id' => $result['tenant_id'],
                'status' => 'active',
                'registered_at' => $result['registered_at'],
                'updated_at' => $result['updated_at'],
            ];
        }

        return null;
    }
}
