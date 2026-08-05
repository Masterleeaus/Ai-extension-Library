<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkCoreWorkforceContract;
use PDO;

class WorkCoreWorkforce implements WorkCoreWorkforceContract
{
    private PDO $db;
    private string $tablePrefix = 'workcore_workforce_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function registerEmployee(
        string $tenantId,
        array $employeeData
    ): string {
        $employeeId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}employees (id, tenant_id, data, registered_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $employeeId,
            $tenantId,
            json_encode($employeeData),
            DateTimeHelper::now(),
        ]);

        return $employeeId;
    }

    public function getEmployee(
        string $tenantId,
        string $employeeId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}employees WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$employeeId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = json_decode($result['data'], true);
        }

        return $result ?: null;
    }

    public function updateEmployee(
        string $tenantId,
        string $employeeId,
        array $updates
    ): bool {
        $employee = $this->getEmployee($tenantId, $employeeId);

        if (!$employee) {
            return false;
        }

        $mergedData = array_merge($employee['data'], $updates);

        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}employees SET data = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($mergedData), DateTimeHelper::now(), $employeeId, $tenantId]);
    }

    public function trackCompliance(
        string $tenantId,
        string $employeeId,
        array $complianceData
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}compliance (employee_id, tenant_id, data, tracked_at)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $employeeId,
            $tenantId,
            json_encode($complianceData),
            DateTimeHelper::now(),
        ]);
    }

    public function verifyNDISEligibility(
        string $tenantId,
        string $employeeId
    ): bool {
        $employee = $this->getEmployee($tenantId, $employeeId);

        if (!$employee) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}ndis_verification (employee_id, tenant_id, verified, verified_at)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([$employeeId, $tenantId, 1, DateTimeHelper::now()]);
    }

    public function recordTraining(
        string $tenantId,
        string $employeeId,
        array $trainingData
    ): string {
        $trainingId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}training (id, employee_id, tenant_id, data, recorded_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $trainingId,
            $employeeId,
            $tenantId,
            json_encode($trainingData),
            DateTimeHelper::now(),
        ]);

        return $trainingId;
    }

    public function generateComplianceReport(
        string $tenantId,
        array $filters
    ): string {
        $reportId = bin2hex(random_bytes(16));

        $query = "SELECT COUNT(*) as total_employees, SUM(CASE WHEN data LIKE '%verified%' THEN 1 ELSE 0 END) as verified
                  FROM {$this->tablePrefix}compliance WHERE tenant_id = ?";
        $params = [$tenantId];

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $reportData = [
            'report_id' => $reportId,
            'tenant_id' => $tenantId,
            'total_employees' => $stats['total_employees'] ?? 0,
            'verified_employees' => $stats['verified'] ?? 0,
            'generated_at' => DateTimeHelper::now(),
        ];

        return json_encode($reportData, JSON_PRETTY_PRINT);
    }

    public function manageRoles(
        string $tenantId,
        string $employeeId,
        array $roles
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}employee_roles (employee_id, tenant_id, roles, assigned_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE roles = ?, updated_at = ?"
        );

        $rolesJson = json_encode($roles);
        $now = DateTimeHelper::now();

        return $stmt->execute([
            $employeeId,
            $tenantId,
            $rolesJson,
            $now,
            $rolesJson,
            $now,
        ]);
    }
}
