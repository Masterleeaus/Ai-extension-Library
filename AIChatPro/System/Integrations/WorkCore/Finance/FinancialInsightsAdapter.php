<?php

namespace Extensions\AIChatPro\System\Integrations\WorkCore\Finance;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class FinancialInsightsAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getFinancialDashboard(string $tenantId, string $userId): array
    {
        return [
            'financial_reports' => $this->getFinancialReports($tenantId),
            'inventory' => $this->getInventory($tenantId),
            'payroll' => $this->getPayroll($tenantId),
            'procurement' => $this->getProcurement($tenantId),
            'vault_operations' => $this->getVaultOperations($tenantId),
            'analytics' => $this->getFinancialAnalytics($tenantId),
        ];
    }

    protected function getFinancialReports(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/financial_reports', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getInventory(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/inventory', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getPayroll(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/payroll', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getProcurement(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/procurement', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getVaultOperations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/vault', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getFinancialAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/analytics', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
