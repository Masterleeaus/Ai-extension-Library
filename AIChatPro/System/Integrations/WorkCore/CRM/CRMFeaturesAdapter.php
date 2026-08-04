<?php

namespace Extensions\AIChatPro\System\Integrations\WorkCore\CRM;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class CRMFeaturesAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getCRMDashboard(string $tenantId, string $userId): array
    {
        return [
            'customers' => $this->getCustomerData($tenantId),
            'catalogue' => $this->getCatalogueData($tenantId),
            'knowledge_base' => $this->getKnowledgeBase($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
            'reviews_feedback' => $this->getReviewsAndFeedback($tenantId),
            'territory_insights' => $this->getTerritoryInsights($tenantId),
        ];
    }

    protected function getCustomerData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('business_network/customers', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getCatalogueData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('business_network/catalogue', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getKnowledgeBase(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('business_network/knowledge_base', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('business_network/analytics', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getReviewsAndFeedback(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('business_network/reviews_feedback', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getTerritoryInsights(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('business_network/territory', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
