<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\AuthorizationPolicyContract;
use Foundation\Contracts\TenantContextContract;
use PDO;
use Foundation\Support\DateTimeHelper;

class AuthorizationPolicy implements AuthorizationPolicyContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'authz_';
    private const TABLE_AUDITS = self::TABLE_PREFIX . 'audits';
    private const TABLE_GRANTS = self::TABLE_PREFIX . 'grants';
    private const TABLE_POLICIES = self::TABLE_PREFIX . 'policies';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function authorize(
        TenantContextContract $context,
        string $action,
        string $resource,
        array $attributes = []
    ): bool {
        $tenantId = $context->getTenantId();
        $userId = $context->getUserId();

        if (!$tenantId) {
            return false;
        }

        // Check if user has permission for the action-resource combination
        $stmt = $this->db->prepare(
            "SELECT 1 FROM " . self::TABLE_POLICIES . "
             WHERE tenant_id = ? AND action = ? AND resource = ? AND active = 1
             LIMIT 1"
        );

        $stmt->execute([$tenantId, $action, $resource]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        // If user is not specified, check for public policies
        if (!$userId) {
            return true;
        }

        // Check if user has been granted this permission
        $stmt = $this->db->prepare(
            "SELECT 1 FROM " . self::TABLE_GRANTS . "
             WHERE tenant_id = ? AND user_id = ? AND action = ? AND resource = ? AND revoked_at IS NULL
             LIMIT 1"
        );

        $stmt->execute([$tenantId, $userId, $action, $resource]);

        return $stmt->rowCount() > 0;
    }

    public function getRequiredPermissions(string $action, string $resource): array {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT permission FROM " . self::TABLE_POLICIES . "
             WHERE action = ? AND resource = ? AND active = 1
             ORDER BY permission ASC"
        );

        $stmt->execute([$action, $resource]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permission');
    }

    public function audit(
        TenantContextContract $context,
        string $action,
        string $resource,
        bool $approved,
        array $attributes = []
    ): void {
        $tenantId = $context->getTenantId();
        $userId = $context->getUserId();
        $auditId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_AUDITS . "
             (id, tenant_id, user_id, action, resource, approved, attributes, recorded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $auditId,
            $tenantId,
            $userId,
            $action,
            $resource,
            (int)$approved,
            json_encode($attributes),
            DateTimeHelper::now(),
        ]);
    }
}
