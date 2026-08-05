<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ShadowValidationContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class ShadowValidation implements ShadowValidationContract
{
    private PDO $db;
    private string $tablePrefix = 'shadow_validation_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createValidation(
        string $tenantId,
        string $migrationId,
        string $dataSource,
        array $rules
    ): string {
        $validationId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}sessions (id, tenant_id, migration_id, data_source, rules, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $validationId,
            $tenantId,
            $migrationId,
            $dataSource,
            json_encode($rules),
            'active',
            DateTimeHelper::now(),
        ]);

        return $validationId;
    }

    public function validateRecord(
        string $tenantId,
        string $validationId,
        array $sourceRecord,
        array $targetRecord
    ): array {
        $validation = $this->getValidationStatus($tenantId, $validationId);

        if (!$validation) {
            return ['valid' => false, 'errors' => ['Validation not found']];
        }

        $rules = JsonHelper::decode($validation['rules']);
        $errors = [];

        foreach ($rules as $rule) {
            $fieldName = $rule['field'];
            $ruleType = $rule['type'];

            if ($ruleType === 'field_match') {
                if (($sourceRecord[$fieldName] ?? null) !== ($targetRecord[$fieldName] ?? null)) {
                    $errors[] = "Field '{$fieldName}' mismatch";
                }
            } elseif ($ruleType === 'field_presence') {
                if (!isset($targetRecord[$fieldName])) {
                    $errors[] = "Field '{$fieldName}' missing in target";
                }
            } elseif ($ruleType === 'value_transform') {
                $expectedValue = $rule['transform']($sourceRecord[$fieldName] ?? null);

                if ($expectedValue !== ($targetRecord[$fieldName] ?? null)) {
                    $errors[] = "Field '{$fieldName}' transformation failed";
                }
            }
        }

        $validationResultId = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}results (id, validation_id, tenant_id, source_data, target_data, errors, valid, recorded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $isValid = empty($errors);
        $stmt->execute([
            $validationResultId,
            $validationId,
            $tenantId,
            json_encode($sourceRecord),
            json_encode($targetRecord),
            json_encode($errors),
            $isValid ? 1 : 0,
            DateTimeHelper::now(),
        ]);

        return [
            'valid' => $isValid,
            'errors' => $errors,
            'result_id' => $validationResultId,
        ];
    }

    public function getValidationStatus(
        string $tenantId,
        string $validationId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}sessions WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$validationId, $tenantId]);
        $validation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($validation) {
            $validation['rules'] = JsonHelper::decode($validation['rules']);
        }

        return $validation ?: null;
    }

    public function compareResults(
        string $tenantId,
        string $validationId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total, SUM(CASE WHEN valid = 1 THEN 1 ELSE 0 END) as passed
             FROM {$this->tablePrefix}results WHERE validation_id = ? AND tenant_id = ?"
        );

        $stmt->execute([$validationId, $tenantId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_records' => $stats['total'] ?? 0,
            'passed' => $stats['passed'] ?? 0,
            'failed' => ($stats['total'] ?? 0) - ($stats['passed'] ?? 0),
            'pass_rate' => $stats['total'] > 0 ? (($stats['passed'] ?? 0) / $stats['total']) * 100 : 0,
        ];
    }

    public function recordDiscrepancy(
        string $tenantId,
        string $validationId,
        array $discrepancy
    ): string {
        $discrepancyId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}discrepancies (id, validation_id, tenant_id, details, recorded_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $discrepancyId,
            $validationId,
            $tenantId,
            json_encode($discrepancy),
            DateTimeHelper::now(),
        ]);

        return $discrepancyId;
    }

    public function getDiscrepancies(
        string $tenantId,
        string $validationId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}discrepancies WHERE validation_id = ? AND tenant_id = ? ORDER BY recorded_at DESC"
        );

        $stmt->execute([$validationId, $tenantId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $result['details'] = JsonHelper::decode($result['details']);
        }

        return $results;
    }

    public function markValidationComplete(
        string $tenantId,
        string $validationId,
        bool $approved
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}sessions SET status = ?, approved = ?, completed_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([
            'completed',
            $approved ? 1 : 0,
            DateTimeHelper::now(),
            $validationId,
            $tenantId,
        ]);
    }

    public function generateReport(
        string $tenantId,
        string $validationId
    ): string {
        $comparison = $this->compareResults($tenantId, $validationId);
        $discrepancies = $this->getDiscrepancies($tenantId, $validationId);

        $report = [
            'validation_id' => $validationId,
            'comparison' => $comparison,
            'discrepancies_count' => count($discrepancies),
            'discrepancies' => $discrepancies,
            'generated_at' => DateTimeHelper::now(),
        ];

        return json_encode($report, JSON_PRETTY_PRINT);
    }
}
