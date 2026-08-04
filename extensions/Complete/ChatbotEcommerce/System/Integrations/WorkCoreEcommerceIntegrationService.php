<?php
namespace Extensions\ChatbotEcommerce\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreEcommerceIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeEcommerce(string $tenantId, string $userId): array
    {
        return [
            'products' => $this->getProducts($tenantId),
            'orders' => $this->getOrders($tenantId),
            'cart' => $this->getCartData($tenantId),
            'payments' => $this->getPayments($tenantId),
            'inventory' => $this->getInventory($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getProducts(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('ecommerce/products', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getOrders(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('ecommerce/orders', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getCartData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('ecommerce/cart', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getPayments(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('ecommerce/payments', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getInventory(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('ecommerce/inventory', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('ecommerce/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function processOrder(string $tenantId, array $orderData): array
    {
        $response = $this->workCoreGateway->action('ecommerce/process_order', [
            'tenant_id' => $tenantId,
            'order_data' => $orderData,
        ]);
        return $response->data ?? [];
    }
}
