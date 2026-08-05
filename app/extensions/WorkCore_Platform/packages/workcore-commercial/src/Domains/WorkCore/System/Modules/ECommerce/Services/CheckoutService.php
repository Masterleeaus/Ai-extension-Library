<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services;

use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\ShoppingCart;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\Order;

class CheckoutService
{
    public function __construct(
        protected TaxService $taxService,
        protected OrderService $orderService
    ) {
    }

    public function validateCart(ShoppingCart $cart): array
    {
        $errors = [];

        if ($cart->items->isEmpty()) {
            $errors[] = 'Cart is empty';
        }

        if ($cart->isExpired()) {
            $errors[] = 'Cart has expired';
        }

        if ($cart->subtotal <= 0) {
            $errors[] = 'Cart total is invalid';
        }

        return $errors;
    }

    public function selectShipping(
        ShoppingCart $cart,
        string $shippingMethod,
        float $shippingCost
    ): ShoppingCart {
        $cart->update([
            'shipping' => max(0, $shippingCost),
        ]);

        $cart->calculateTotals();

        return $cart;
    }

    public function calculateTax(
        ShoppingCart $cart,
        string $country,
        ?string $state = null
    ): ShoppingCart {
        $tax = $this->taxService->calculateTax($cart->subtotal, $country, $state);

        $cart->update(['tax' => $tax]);
        $cart->calculateTotals();

        return $cart;
    }

    public function processPayment(
        ShoppingCart $cart,
        string $paymentMethod,
        array $paymentDetails
    ): array {
        // This should be implemented with actual payment gateway integration
        // For now, we'll just validate that required details are present
        $requiredFields = match ($paymentMethod) {
            'card' => ['card_number', 'expiry', 'cvv'],
            'paypal' => ['email'],
            default => [],
        };

        foreach ($requiredFields as $field) {
            if (!isset($paymentDetails[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        // In a real implementation, this would call a payment gateway
        return [
            'success' => true,
            'transaction_id' => 'txn_' . uniqid(),
        ];
    }

    public function createOrder(
        ShoppingCart $cart,
        string $customerEmail,
        array $shippingAddress,
        array $billingAddress,
        string $paymentMethod,
        ?string $customerId = null
    ): Order {
        // Validate cart
        $errors = $this->validateCart($cart);
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }

        // Validate addresses
        if (empty($shippingAddress) || !isset($shippingAddress['street'], $shippingAddress['city'], $shippingAddress['country'])) {
            throw new \Exception('Invalid shipping address');
        }

        if (empty($billingAddress) || !isset($billingAddress['street'], $billingAddress['city'], $billingAddress['country'])) {
            throw new \Exception('Invalid billing address');
        }

        // Create order
        $order = $this->orderService->createOrderFromCart(
            $cart,
            $customerEmail,
            $shippingAddress,
            $billingAddress,
            $paymentMethod,
            $customerId
        );

        return $order;
    }

    public function getCheckoutSummary(ShoppingCart $cart): array
    {
        return [
            'items_count' => $cart->items->count(),
            'items_total' => $cart->items->sum(fn ($item) => $item->quantity),
            'subtotal' => (float) $cart->subtotal,
            'tax' => (float) $cart->tax,
            'shipping' => (float) $cart->shipping,
            'discount' => (float) $cart->discount,
            'total' => (float) $cart->total,
            'coupon_code' => $cart->coupon_code,
        ];
    }
}
