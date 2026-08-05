<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Tests\Feature;

use PHPUnit\Framework\TestCase;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CartService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CheckoutService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\OrderService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\TaxService;

class CheckoutFlowTest extends TestCase
{
    protected CartService $cartService;
    protected CheckoutService $checkoutService;
    protected OrderService $orderService;
    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = new TaxService();
        $this->cartService = new CartService();
        $this->orderService = new OrderService($this->taxService);
        $this->checkoutService = new CheckoutService($this->taxService, $this->orderService);
    }

    public function testCompleteCheckoutFlow(): void
    {
        // Step 1: Create cart
        $cart = $this->cartService->createCart(
            customerId: 'customer-123',
            tenantId: 'tenant-789'
        );

        $this->assertNotNull($cart);

        // Step 2: Add items to cart
        $item1 = $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-001',
            quantity: 2,
            price: 29.99
        );

        $item2 = $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-002',
            quantity: 1,
            price: 49.99
        );

        $cart = $this->cartService->getCart($cart->id);

        $this->assertEquals(2, $cart->items->count());
        $this->assertEquals(109.97, $cart->subtotal);

        // Step 3: Validate cart
        $errors = $this->checkoutService->validateCart($cart);

        $this->assertEmpty($errors);

        // Step 4: Calculate tax
        $this->checkoutService->calculateTax($cart, 'AU');

        $cart = $this->cartService->getCart($cart->id);

        $this->assertGreater($cart->tax, 0);

        // Step 5: Select shipping
        $this->checkoutService->selectShipping($cart, 'express', 15.00);

        $cart = $this->cartService->getCart($cart->id);

        $this->assertEquals(15.00, $cart->shipping);

        // Step 6: Process payment
        $paymentResult = $this->checkoutService->processPayment(
            $cart,
            'stripe',
            ['token' => 'tok_visa']
        );

        $this->assertTrue($paymentResult['success']);

        // Step 7: Create order
        $order = $this->checkoutService->createOrder(
            $cart,
            'customer@example.com',
            [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            'stripe',
            'customer-123'
        );

        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(2, $order->items->count());

        // Step 8: Mark as paid
        $order->markAsPaid();

        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);

        // Step 9: Export order
        $exported = $this->orderService->export($order->id);

        $this->assertNotNull($exported);
        $this->assertEquals($order->order_number, $exported['order_number']);
        $this->assertEquals(2, count($exported['items']));
    }

    public function testCheckoutWithInvalidCart(): void
    {
        $cart = $this->cartService->createCart(tenantId: 'tenant-789');

        $errors = $this->checkoutService->validateCart($cart);

        $this->assertNotEmpty($errors);
        $this->assertContains('Cart is empty', $errors);
    }

    public function testCheckoutSummary(): void
    {
        $cart = $this->cartService->createCart(tenantId: 'tenant-789');

        $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-001',
            quantity: 1,
            price: 99.99
        );

        $cart = $this->cartService->getCart($cart->id);

        $this->checkoutService->calculateTax($cart, 'AU');
        $this->checkoutService->selectShipping($cart, 'standard', 10.00);

        $summary = $this->checkoutService->getCheckoutSummary($cart);

        $this->assertEquals(1, $summary['items_count']);
        $this->assertEquals(1, $summary['items_total']);
        $this->assertEquals(99.99, $summary['subtotal']);
        $this->assertGreater($summary['tax'], 0);
        $this->assertEquals(10.00, $summary['shipping']);
    }

    public function testOrderStatusTransitions(): void
    {
        $cart = $this->cartService->createCart(tenantId: 'tenant-789');

        $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-001',
            quantity: 1,
            price: 99.99
        );

        $cart = $this->cartService->getCart($cart->id);

        $order = $this->checkoutService->createOrder(
            $cart,
            'customer@example.com',
            [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            'stripe',
            'customer-123'
        );

        // Test status transitions
        $order->markAsPaid();

        $updated = $this->orderService->updateStatus($order->id, 'confirmed');

        $this->assertEquals('confirmed', $updated->status);

        $updated = $this->orderService->updateStatus($order->id, 'processing');

        $this->assertEquals('processing', $updated->status);

        $updated = $this->orderService->updateStatus($order->id, 'shipped');

        $this->assertEquals('shipped', $updated->status);

        $updated = $this->orderService->updateStatus($order->id, 'delivered');

        $this->assertEquals('delivered', $updated->status);
    }
}
