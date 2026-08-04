<?php

namespace Extensions\AIChatPro\System\Integrations;

use Extensions\AIChatPro\System\Integrations\WorkCore\HR\HRDashboardAdapter;
use Extensions\AIChatPro\System\Integrations\WorkCore\Assets\AssetManagementAdapter;
use Extensions\AIChatPro\System\Integrations\WorkCore\Operations\OperationsDashboardAdapter;
use Extensions\AIChatPro\System\Integrations\WorkCore\Finance\FinancialInsightsAdapter;
use Extensions\AIChatPro\System\Integrations\WorkCore\CRM\CRMFeaturesAdapter;
use Extensions\AIChatPro\System\Integrations\WorkCore\Foundation\PlatformAIFoundationAdapter;

class WorkCoreIntegrationService
{
    protected $hrAdapter;
    protected $assetsAdapter;
    protected $operationsAdapter;
    protected $financeAdapter;
    protected $crmAdapter;
    protected $foundationAdapter;

    public function __construct(
        HRDashboardAdapter $hrAdapter,
        AssetManagementAdapter $assetsAdapter,
        OperationsDashboardAdapter $operationsAdapter,
        FinancialInsightsAdapter $financeAdapter,
        CRMFeaturesAdapter $crmAdapter,
        PlatformAIFoundationAdapter $foundationAdapter
    ) {
        $this->hrAdapter = $hrAdapter;
        $this->assetsAdapter = $assetsAdapter;
        $this->operationsAdapter = $operationsAdapter;
        $this->financeAdapter = $financeAdapter;
        $this->crmAdapter = $crmAdapter;
        $this->foundationAdapter = $foundationAdapter;
    }

    public function initializePlatformAI(string $tenantId, string $userId): array
    {
        return [
            'foundation' => $this->foundationAdapter->initializeFoundation($tenantId, $userId),
            'hr_operations' => $this->hrAdapter->getHRDashboard($tenantId, $userId),
            'asset_management' => $this->assetsAdapter->getAssetDashboard($tenantId, $userId),
            'operations_dashboard' => $this->operationsAdapter->getOperationsDashboard($tenantId, $userId),
            'financial_insights' => $this->financeAdapter->getFinancialDashboard($tenantId, $userId),
            'crm_features' => $this->crmAdapter->getCRMDashboard($tenantId, $userId),
        ];
    }

    public function getHROperations(string $tenantId, string $userId): array
    {
        return $this->hrAdapter->getHRDashboard($tenantId, $userId);
    }

    public function getAssetManagement(string $tenantId, string $userId): array
    {
        return $this->assetsAdapter->getAssetDashboard($tenantId, $userId);
    }

    public function getOperationsDashboard(string $tenantId, string $userId): array
    {
        return $this->operationsAdapter->getOperationsDashboard($tenantId, $userId);
    }

    public function getFinancialInsights(string $tenantId, string $userId): array
    {
        return $this->financeAdapter->getFinancialDashboard($tenantId, $userId);
    }

    public function getCRMFeatures(string $tenantId, string $userId): array
    {
        return $this->crmAdapter->getCRMDashboard($tenantId, $userId);
    }

    public function getFoundation(string $tenantId, string $userId): array
    {
        return $this->foundationAdapter->initializeFoundation($tenantId, $userId);
    }
}
