<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services;

use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\ShoppingCart;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\CartItem;
use Illuminate\Support\Str;

class CartService
{
    public function createCart(?string $customerId = null, ?string $sessionId = null, string $tenantId): ShoppingCart
    {
        $sessionId = $sessionId ?? Str::uuid();

        return ShoppingCart::create([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'total' => 0,
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function getCart(int $cartId): ?ShoppingCart
    {
        return ShoppingCart::with('items')->find($cartId);
    }

    public function getCartBySession(string $sessionId, string $tenantId): ?ShoppingCart
    {
        return ShoppingCart::where('session_id', $sessionId)
            ->where('tenant_id', $tenantId)
            ->with('items')
            ->first();
    }

    public function getOrCreateCart(?string $customerId, ?string $sessionId, string $tenantId): ShoppingCart
    {
        if ($customerId) {
            $cart = ShoppingCart::where('customer_id', $customerId)
                ->where('tenant_id', $tenantId)
                ->where('cart_id', null) // No associated order
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        if ($sessionId) {
            $cart = $this->getCartBySession($sessionId, $tenantId);
            if ($cart && !$cart->isExpired()) {
                return $cart;
            }
        }

        return $this->createCart($customerId, $sessionId, $tenantId);
    }

    public function addItem(
        int $cartId,
        string $productId,
        int $quantity,
        float $price,
        ?string $variantId = null,
        ?array $attributes = null
    ): CartItem {
        $cart = $this->getCart($cartId);

        if (!$cart) {
            throw new \Exception("Cart not found");
        }

        return $cart->addItem($productId, $quantity, $price, $variantId, $attributes);
    }

    public function removeItem(int $cartId, int $itemId): bool
    {
        $cart = $this->getCart($cartId);

        if (!$cart) {
            throw new \Exception("Cart not found");
        }

        return $cart->removeItem($itemId);
    }

    public function updateQuantity(int $cartId, int $itemId, int $quantity): CartItem
    {
        $cart = $this->getCart($cartId);

        if (!$cart) {
            throw new \Exception("Cart not found");
        }

        return $cart->updateQuantity($itemId, $quantity);
    }

    public function clearCart(int $cartId): void
    {
        $cart = $this->getCart($cartId);

        if ($cart) {
            $cart->clear();
        }
    }

    public function applyCoupon(int $cartId, string $couponCode): bool
    {
        $cart = $this->getCart($cartId);

        if (!$cart) {
            throw new \Exception("Cart not found");
        }

        return $cart->applyCoupon($couponCode);
    }

    public function removeCoupon(int $cartId): void
    {
        $cart = $this->getCart($cartId);

        if ($cart) {
            $cart->coupon_code = null;
            $cart->discount = 0;
            $cart->calculateTotals();
        }
    }
}
