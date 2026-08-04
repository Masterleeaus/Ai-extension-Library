<?php

namespace Extensions\Chatbot\System\Integrations\WorkCore\Commerce;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class CommerceOperationsAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getCommerceContext(string $tenantId, array $conversationContext): array
    {
        return [
            'inventory' => $this->getInventory($tenantId),
            'pricing' => $this->getPricing($tenantId, $conversationContext),
            'orders' => $this->getOrders($tenantId, $conversationContext),
            'payments' => $this->getPaymentInfo($tenantId),
            'procurement' => $this->getProcurement($tenantId),
        ];
    }

    protected function getInventory(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/inventory', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getPricing(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('commercial/pricing', [
            'tenant_id' => $tenantId,
            'product_ids' => $context['products'] ?? [],
        ]);
        return $response->data ?? [];
    }

    protected function getOrders(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('commercial/orders', [
            'tenant_id' => $tenantId,
            'customer_id' => $context['customer_id'] ?? null,
        ]);
        return $response->data ?? [];
    }

    protected function getPaymentInfo(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/payments', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getProcurement(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('commercial/procurement', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
}
