<?php
namespace Extensions\ChatbotCustomerTag\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreCustomerTagIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeCustomerTags(string $tenantId, string $userId): array
    {
        return [
            'tags' => $this->getTags($tenantId),
            'customers' => $this->getCustomers($tenantId),
            'segments' => $this->getSegments($tenantId),
            'metadata' => $this->getMetadata($tenantId),
            'relationships' => $this->getRelationships($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getTags(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('customer_tag/tags', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getCustomers(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('customer_tag/customers', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getSegments(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('customer_tag/segments', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getMetadata(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('customer_tag/metadata', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getRelationships(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('customer_tag/relationships', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('customer_tag/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function tagCustomer(string $tenantId, string $customerId, array $tags): array
    {
        $response = $this->workCoreGateway->action('customer_tag/tag_customer', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'tags' => $tags,
        ]);
        return $response->data ?? [];
    }
}
