<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WorkCoreCommercialContract
{
    public function recordTransaction(
        string $tenantId,
        array $transactionData
    ): string;

    public function getTransaction(
        string $tenantId,
        string $transactionId
    ): ?array;

    public function processPayroll(
        string $tenantId,
        array $payrollData
    ): string;

    public function getPayrollStatus(
        string $tenantId,
        string $payrollId
    ): ?array;

    public function manageInventory(
        string $tenantId,
        array $inventoryData
    ): bool;

    public function getInventoryLevel(
        string $tenantId,
        string $skuId
    ): ?int;

    public function generateFinancialReport(
        string $tenantId,
        string $reportType,
        array $parameters
    ): string;

    public function integratePaymentGateway(
        string $tenantId,
        array $gatewayConfig
    ): bool;
}
