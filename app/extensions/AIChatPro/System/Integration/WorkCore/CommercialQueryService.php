<?php declare(strict_types=1);
namespace App\Extensions\AIChatPro\System\Integration\WorkCore;

/**
 * WorkCore Commercial Integration for AiChatPro
 * Issue #189: Commercial → AiChatPro Commerce Operations
 */
final class CommercialQueryService extends BaseWorkCoreService
{
    public function listInventory(int $limit = 100, int $offset = 0): array
    {
        return $this->authorize('read', 'inventory') ? $this->list('inventory', $limit, $offset) : [];
    }

    public function getPricing(string $productId): ?array
    {
        return $this->authorize('read', 'pricing') ? null : null;
    }

    public function getOrder(string $orderId): ?array
    {
        return $this->authorize('read', 'order') ? null : null;
    }

    public function listOrders(int $limit = 100): array
    {
        return $this->authorize('read', 'order') ? $this->list('order', $limit) : [];
    }
}
