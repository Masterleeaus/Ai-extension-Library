<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\AuthorizationPolicyContract;
use Foundation\Contracts\TenantContextContract;
use PDO;

class AuthorizationPolicy implements AuthorizationPolicyContract
{
    private PDO $db;
    private string $tablePrefix = 'authz_';

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
            "SELECT 1 FROM {$this->tablePrefix}policies
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
            "SELECT 1 FROM {$this->tablePrefix}grants
             WHERE tenant_id = ? AND user_id = ? AND action = ? AND resource = ? AND revoked_at IS NULL
             LIMIT 1"
        );

        $stmt->execute([$tenantId, $userId, $action, $resource]);

        return $stmt->rowCount() > 0;
    }

    public function getRequiredPermissions(string $action, string $resource): array {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT permission FROM {$this->tablePrefix}policies
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
            "INSERT INTO {$this->tablePrefix}audits
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
