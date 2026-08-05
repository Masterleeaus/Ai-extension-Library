<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services;

use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\Order;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\OrderItem;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\ShoppingCart;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    public function __construct(
        protected TaxService $taxService
    ) {
    }

    public function getOrder(int $orderId): ?Order
    {
        return Order::with(['items', 'fulfillment', 'payments'])->find($orderId);
    }

    public function getOrderByNumber(string $orderNumber, string $tenantId): ?Order
    {
        return Order::where('order_number', $orderNumber)
            ->where('tenant_id', $tenantId)
            ->with(['items', 'fulfillment', 'payments'])
            ->first();
    }

    public function listOrders(string $tenantId, ?string $customerId = null, ?string $status = null): Collection
    {
        $query = Order::where('tenant_id', $tenantId);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with('items')->orderByDesc('created_at')->get();
    }

    public function createOrderFromCart(
        ShoppingCart $cart,
        string $customerEmail,
        array $shippingAddress,
        array $billingAddress,
        string $paymentMethod,
        ?string $customerId = null
    ): Order {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'tenant_id' => $cart->tenant_id,
            'customer_id' => $customerId ?? $cart->customer_id,
            'customer_email' => $customerEmail,
            'cart_id' => $cart->id,
            'status' => 'pending',
            'subtotal' => $cart->subtotal,
            'tax' => $cart->tax,
            'shipping' => $cart->shipping,
            'discount' => $cart->discount,
            'total' => $cart->total,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
        ]);

        // Copy items from cart to order
        foreach ($cart->items as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product_name ?? 'Product',
                'variant_id' => $cartItem->variant_id,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->price_at_time,
                'total_price' => $cartItem->getTotalPrice(),
                'attributes' => $cartItem->attributes,
            ]);
        }

        return $order;
    }

    public function updateStatus(int $orderId, string $status): Order
    {
        $order = $this->getOrder($orderId);

        if (!$order) {
            throw new \Exception("Order not found");
        }

        if (!in_array($status, ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])) {
            throw new \InvalidArgumentException("Invalid order status: {$status}");
        }

        $order->update(['status' => $status]);

        return $order;
    }

    public function cancel(int $orderId): bool
    {
        $order = $this->getOrder($orderId);

        if (!$order) {
            throw new \Exception("Order not found");
        }

        return $order->cancel();
    }

    public function refund(int $orderId, ?float $amount = null): Order
    {
        $order = $this->getOrder($orderId);

        if (!$order) {
            throw new \Exception("Order not found");
        }

        $order->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);

        return $order;
    }

    public function export(int $orderId): array
    {
        $order = $this->getOrder($orderId);

        if (!$order) {
            throw new \Exception("Order not found");
        }

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer_email' => $order->customer_email,
            'subtotal' => (float) $order->subtotal,
            'tax' => (float) $order->tax,
            'shipping' => (float) $order->shipping,
            'discount' => (float) $order->discount,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'shipping_address' => $order->shipping_address?->toArray(),
            'billing_address' => $order->billing_address?->toArray(),
            'items' => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->toArray(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}
