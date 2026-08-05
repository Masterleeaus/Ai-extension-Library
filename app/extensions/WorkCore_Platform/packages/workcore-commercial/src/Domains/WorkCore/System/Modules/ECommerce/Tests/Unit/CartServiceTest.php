<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\ShoppingCart;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CartService;

class CartServiceTest extends TestCase
{
    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }

    public function testCartCanBeCreated(): void
    {
        $cart = $this->cartService->createCart(
            customerId: 'user-123',
            sessionId: 'session-456',
            tenantId: 'tenant-789'
        );

        $this->assertNotNull($cart);
        $this->assertEquals('tenant-789', $cart->tenant_id);
        $this->assertEquals('user-123', $cart->customer_id);
    }

    public function testItemCanBeAddedToCart(): void
    {
        $cart = $this->cartService->createCart(
            tenantId: 'tenant-789'
        );

        $item = $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-123',
            quantity: 2,
            price: 29.99
        );

        $this->assertNotNull($item);
        $this->assertEquals('prod-123', $item->product_id);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(29.99, $item->price_at_time);
    }

    public function testItemCanBeRemovedFromCart(): void
    {
        $cart = $this->cartService->createCart(
            tenantId: 'tenant-789'
        );

        $item = $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-123',
            quantity: 1,
            price: 29.99
        );

        $removed = $this->cartService->removeItem($cart->id, $item->id);

        $this->assertTrue($removed);
    }

    public function testQuantityCanBeUpdated(): void
    {
        $cart = $this->cartService->createCart(
            tenantId: 'tenant-789'
        );

        $item = $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-123',
            quantity: 1,
            price: 29.99
        );

        $updated = $this->cartService->updateQuantity($cart->id, $item->id, 5);

        $this->assertEquals(5, $updated->quantity);
    }

    public function testCartCanBeCleared(): void
    {
        $cart = $this->cartService->createCart(
            tenantId: 'tenant-789'
        );

        $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-123',
            quantity: 1,
            price: 29.99
        );

        $this->cartService->clearCart($cart->id);

        $cart = $this->cartService->getCart($cart->id);
        $this->assertTrue($cart->items->isEmpty());
    }
}
