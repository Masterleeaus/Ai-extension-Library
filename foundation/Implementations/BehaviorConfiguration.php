<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\BehaviorConfigurationContract;
use PDO;
use Foundation\Support\JsonHelper;

class BehaviorConfiguration implements BehaviorConfigurationContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'behavior_configuration_';
    private const TABLE_APPLIED_SCOPES = self::TABLE_PREFIX . 'applied_scopes';
    private const TABLE_CONFIGS = self::TABLE_PREFIX . 'configs';
    private const TABLE_RULES = self::TABLE_PREFIX . 'rules';
    private const TABLE_TEST_RESULTS = self::TABLE_PREFIX . 'test_results';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createBehaviorConfig(
        string $tenantId,
        string $configName,
        array $behaviors
    ): string {
        $configId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_CONFIGS . " (id, tenant_id, name, behaviors, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $configId,
            $tenantId,
            $configName,
            json_encode($behaviors),
            date('c'),
        ]);

        return $configId;
    }

    public function getBehaviorConfig(
        string $tenantId,
        string $configId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_CONFIGS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$configId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['behaviors'] = JsonHelper::decode($result['behaviors']);
        }

        return $result ?: null;
    }

    public function updateBehaviorConfig(
        string $tenantId,
        string $configId,
        array $behaviors
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_CONFIGS . " SET behaviors = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($behaviors), date('c'), $configId, $tenantId]);
    }

    public function addBehaviorRule(
        string $tenantId,
        string $configId,
        string $trigger,
        string $action,
        array $conditions
    ): bool {
        $ruleId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_RULES . " (id, config_id, tenant_id, trigger, action, conditions, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $ruleId,
            $configId,
            $tenantId,
            $trigger,
            $action,
            json_encode($conditions),
            date('c'),
        ]);
    }

    public function removeBehaviorRule(
        string $tenantId,
        string $configId,
        string $ruleId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM " . self::TABLE_RULES . " WHERE id = ? AND config_id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$ruleId, $configId, $tenantId]);
    }

    public function testBehaviorRule(
        string $tenantId,
        string $ruleId,
        array $testData
    ): array {
        $stmt = $this->db->prepare(
            "SELECT trigger, action, conditions FROM " . self::TABLE_RULES . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$ruleId, $tenantId]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rule) {
            return ['passed' => false, 'error' => 'Rule not found'];
        }

        $conditions = JsonHelper::decode($rule['conditions']);
        $conditionsMet = true;

        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $value = $testData[$field] ?? null;
            $expectedValue = $condition['value'];

            if ($value !== $expectedValue) {
                $conditionsMet = false;

                break;
            }
        }

        $testId = bin2hex(random_bytes(16));
        $testStmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_TEST_RESULTS . " (id, rule_id, tenant_id, test_data, passed, tested_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $testStmt->execute([
            $testId,
            $ruleId,
            $tenantId,
            json_encode($testData),
            $conditionsMet ? 1 : 0,
            date('c'),
        ]);

        return [
            'passed' => $conditionsMet,
            'test_id' => $testId,
            'trigger' => $rule['trigger'],
            'action' => $conditionsMet ? $rule['action'] : 'no_action',
        ];
    }

    public function listBehaviorConfigs(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, created_at FROM " . self::TABLE_CONFIGS . " WHERE tenant_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function applyBehaviorConfig(
        string $tenantId,
        string $configId,
        string $scope
    ): bool {
        $config = $this->getBehaviorConfig($tenantId, $configId);

        if (!$config) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_APPLIED_SCOPES . " (config_id, tenant_id, scope, applied_at)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([$configId, $tenantId, $scope, date('c')]);
    }
}
