<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\GovernanceContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class Governance implements GovernanceContract
{
    private PDO $db;
    private string $tablePrefix = 'governance_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createPolicy(
        string $tenantId,
        string $policyName,
        array $rules
    ): string {
        $policyId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}policies (id, tenant_id, name, rules, version, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $policyId,
            $tenantId,
            $policyName,
            json_encode($rules),
            1,
            DateTimeHelper::now(),
        ]);

        return $policyId;
    }

    public function getPolicy(
        string $tenantId,
        string $policyId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}policies WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$policyId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['rules'] = JsonHelper::decode($result['rules']);
        }

        return $result ?: null;
    }

    public function updatePolicy(
        string $tenantId,
        string $policyId,
        array $rules
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT version FROM {$this->tablePrefix}policies WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$policyId, $tenantId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            return false;
        }

        $newVersion = $current['version'] + 1;

        $updateStmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}policies SET rules = ?, version = ?, updated_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $updateStmt->execute([
            json_encode($rules),
            $newVersion,
            DateTimeHelper::now(),
            $policyId,
            $tenantId,
        ]);
    }

    public function deletePolicy(
        string $tenantId,
        string $policyId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}policies WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$policyId, $tenantId]);
    }

    public function enforcePolicy(
        string $tenantId,
        string $policyId,
        string $resourceType,
        array $resourceData
    ): array {
        $policy = $this->getPolicy($tenantId, $policyId);

        if (!$policy) {
            return ['enforced' => false, 'violations' => []];
        }

        $violations = [];
        $rules = $policy['rules'];

        foreach ($rules as $rule) {
            if ($this->violatesRule($rule, $resourceType, $resourceData)) {
                $violations[] = $rule;
            }
        }

        $enforcementId = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}enforcement_log (id, tenant_id, policy_id, resource_type, violations, enforced_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $enforcementId,
            $tenantId,
            $policyId,
            $resourceType,
            json_encode($violations),
            DateTimeHelper::now(),
        ]);

        return [
            'enforced' => empty($violations),
            'violations' => $violations,
            'enforcement_id' => $enforcementId,
        ];
    }

    public function auditPolicyEnforcement(
        string $tenantId,
        string $policyId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}enforcement_log
             WHERE tenant_id = ? AND policy_id = ?
             ORDER BY enforced_at DESC"
        );

        $stmt->execute([$tenantId, $policyId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['violations'] = JsonHelper::decode($result['violations']);
        }

        return $results;
    }

    public function listPolicies(
        string $tenantId,
        ?string $category = null
    ): array {
        $query = "SELECT * FROM {$this->tablePrefix}policies WHERE tenant_id = ?";
        $params = [$tenantId];

        if ($category) {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['rules'] = JsonHelper::decode($result['rules']);
        }

        return $results;
    }

    public function getPolicyVersion(
        string $tenantId,
        string $policyId,
        int $version
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}policy_history
             WHERE tenant_id = ? AND policy_id = ? AND version = ?"
        );

        $stmt->execute([$tenantId, $policyId, $version]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['rules'] = JsonHelper::decode($result['rules']);
        }

        return $result ?: null;
    }

    private function violatesRule(array $rule, string $resourceType, array $resourceData): bool {
        $targetType = $rule['target_resource_type'] ?? null;

        if ($targetType && $targetType !== $resourceType) {
            return false;
        }

        $condition = $rule['condition'] ?? null;

        if (!$condition) {
            return false;
        }

        if ($condition['type'] === 'field_value') {
            $field = $condition['field'];
            $expectedValue = $condition['value'];
            $actualValue = $resourceData[$field] ?? null;

            return $actualValue !== $expectedValue;
        }

        if ($condition['type'] === 'field_contains') {
            $field = $condition['field'];
            $value = $condition['value'];
            $actualValue = $resourceData[$field] ?? '';

            return strpos($actualValue, $value) === false;
        }

        return false;
    }
}
