<?php

namespace Extensions\Chatbot\System\Integrations\WorkCore\CRM;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class CRMAssistantAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getCRMContext(string $tenantId, array $conversationContext): array
    {
        return [
            'customer_profile' => $this->getCustomerProfile($tenantId, $conversationContext),
            'catalogue_search' => $this->searchCatalogue($tenantId, $conversationContext),
            'knowledge_base' => $this->queryKnowledgeBase($tenantId, $conversationContext),
            'recommendations' => $this->getRecommendations($tenantId, $conversationContext),
            'recent_interactions' => $this->getRecentInteractions($tenantId, $conversationContext),
        ];
    }

    protected function getCustomerProfile(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('business_network/customers', [
            'tenant_id' => $tenantId,
            'customer_id' => $context['customer_id'] ?? null,
        ]);
        return $response->data ?? [];
    }

    protected function searchCatalogue(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('business_network/catalogue', [
            'tenant_id' => $tenantId,
            'search' => $context['query'] ?? null,
        ]);
        return $response->data ?? [];
    }

    protected function queryKnowledgeBase(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('business_network/knowledge_base', [
            'tenant_id' => $tenantId,
            'query' => $context['query'] ?? null,
        ]);
        return $response->data ?? [];
    }

    protected function getRecommendations(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('business_network/recommendations', [
            'tenant_id' => $tenantId,
            'customer_id' => $context['customer_id'] ?? null,
        ]);
        return $response->data ?? [];
    }

    protected function getRecentInteractions(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('business_network/interactions', [
            'tenant_id' => $tenantId,
            'customer_id' => $context['customer_id'] ?? null,
        ]);
        return $response->data ?? [];
    }
}
