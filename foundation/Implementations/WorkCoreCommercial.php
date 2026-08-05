<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkCoreCommercialContract;
use PDO;

class WorkCoreCommercial implements WorkCoreCommercialContract
{
    private PDO $db;
    private string $tablePrefix = 'workcore_commercial_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function recordTransaction(
        string $tenantId,
        array $transactionData
    ): string {
        $transactionId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}transactions (id, tenant_id, data, recorded_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $transactionId,
            $tenantId,
            json_encode($transactionData),
            DateTimeHelper::now(),
        ]);

        return $transactionId;
    }

    public function getTransaction(
        string $tenantId,
        string $transactionId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}transactions WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$transactionId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = json_decode($result['data'], true);
        }

        return $result ?: null;
    }

    public function processPayroll(
        string $tenantId,
        array $payrollData
    ): string {
        $payrollId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}payroll (id, tenant_id, data, status, processed_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $payrollId,
            $tenantId,
            json_encode($payrollData),
            'processing',
            DateTimeHelper::now(),
        ]);

        return $payrollId;
    }

    public function getPayrollStatus(
        string $tenantId,
        string $payrollId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}payroll WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$payrollId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = json_decode($result['data'], true);
        }

        return $result ?: null;
    }

    public function manageInventory(
        string $tenantId,
        array $inventoryData
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}inventory (tenant_id, sku_id, quantity, data, updated_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = ?, data = ?, updated_at = ?"
        );

        $sku = $inventoryData['sku_id'] ?? null;
        $quantity = $inventoryData['quantity'] ?? 0;
        $now = DateTimeHelper::now();

        return $stmt->execute([
            $tenantId,
            $sku,
            $quantity,
            json_encode($inventoryData),
            $now,
            $quantity,
            json_encode($inventoryData),
            $now,
        ]);
    }

    public function getInventoryLevel(
        string $tenantId,
        string $skuId
    ): ?int {
        $stmt = $this->db->prepare(
            "SELECT quantity FROM {$this->tablePrefix}inventory WHERE tenant_id = ? AND sku_id = ?"
        );

        $stmt->execute([$tenantId, $skuId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['quantity'] : null;
    }

    public function generateFinancialReport(
        string $tenantId,
        string $reportType,
        array $parameters
    ): string {
        $reportId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}reports (id, tenant_id, type, parameters, generated_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $reportId,
            $tenantId,
            $reportType,
            json_encode($parameters),
            DateTimeHelper::now(),
        ]);

        return $reportId;
    }

    public function integratePaymentGateway(
        string $tenantId,
        array $gatewayConfig
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}payment_gateways (tenant_id, config, integrated_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE config = ?, updated_at = ?"
        );

        $configJson = json_encode($gatewayConfig);
        $now = DateTimeHelper::now();

        return $stmt->execute([
            $tenantId,
            $configJson,
            $now,
            $configJson,
            $now,
        ]);
    }
}
