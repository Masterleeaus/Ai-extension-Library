<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services;

use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\Order;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\OrderFulfillment;

class FulfillmentService
{
    public function createShipment(
        Order $order,
        string $shippingMethod,
        string $shippingProvider,
        ?array $items = null
    ): OrderFulfillment {
        $fulfillment = OrderFulfillment::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'shipping_method' => $shippingMethod,
            'shipping_provider' => $shippingProvider,
            'items' => $items ?? $order->items->toArray(),
            'shipping_cost' => $order->shipping,
        ]);

        return $fulfillment;
    }

    public function updateTracking(
        int $fulfillmentId,
        string $trackingNumber,
        ?string $trackingUrl = null
    ): OrderFulfillment {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);

        $fulfillment->update([
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl ? [$trackingUrl] : null,
        ]);

        return $fulfillment;
    }

    public function generateLabel(
        int $fulfillmentId,
        string $format = 'pdf'
    ): string {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);

        // This would integrate with shipping provider APIs
        // For now, we'll return a placeholder
        return "label_{$fulfillmentId}.{$format}";
    }

    public function markAsShipped(int $fulfillmentId): OrderFulfillment
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);
        $fulfillment->markAsShipped();

        return $fulfillment;
    }

    public function markAsDelivered(int $fulfillmentId): OrderFulfillment
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);
        $fulfillment->markAsDelivered();

        return $fulfillment;
    }

    public function getFulfillmentStatus(int $fulfillmentId): array
    {
        $fulfillment = OrderFulfillment::findOrFail($fulfillmentId);

        return [
            'id' => $fulfillment->id,
            'order_id' => $fulfillment->order_id,
            'status' => $fulfillment->status,
            'status_label' => $fulfillment->getStatusLabel(),
            'shipping_provider' => $fulfillment->shipping_provider,
            'tracking_number' => $fulfillment->tracking_number,
            'tracking_url' => $fulfillment->tracking_url,
            'shipped_at' => $fulfillment->shipped_at,
            'delivered_at' => $fulfillment->delivered_at,
        ];
    }
}
