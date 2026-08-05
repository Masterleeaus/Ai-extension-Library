<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\FulfillmentService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CartService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CheckoutService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\OrderService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\TaxService;

class FulfillmentServiceTest extends TestCase
{
    protected FulfillmentService $fulfillmentService;
    protected CartService $cartService;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fulfillmentService = new FulfillmentService();
        $this->cartService = new CartService();
        $this->orderService = new OrderService(new TaxService());
    }

    public function testShipmentCanBeCreated(): void
    {
        // Create a mock order
        $cart = $this->cartService->createCart(tenantId: 'tenant-123');

        $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-001',
            quantity: 1,
            price: 99.99
        );

        $cart = $this->cartService->getCart($cart->id);

        $order = $this->orderService->createOrderFromCart(
            cart: $cart,
            customerEmail: 'customer@example.com',
            shippingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            billingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            paymentMethod: 'stripe',
            customerId: 'customer-123'
        );

        // Create shipment
        $shipment = $this->fulfillmentService->createShipment(
            order: $order,
            shippingMethod: 'express',
            shippingProvider: 'dhl'
        );

        $this->assertNotNull($shipment);
        $this->assertEquals('pending', $shipment->status);
        $this->assertEquals('dhl', $shipment->shipping_provider);
    }

    public function testTrackingCanBeUpdated(): void
    {
        $cart = $this->cartService->createCart(tenantId: 'tenant-123');

        $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-001',
            quantity: 1,
            price: 99.99
        );

        $cart = $this->cartService->getCart($cart->id);

        $order = $this->orderService->createOrderFromCart(
            cart: $cart,
            customerEmail: 'customer@example.com',
            shippingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            billingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            paymentMethod: 'stripe',
            customerId: 'customer-123'
        );

        $shipment = $this->fulfillmentService->createShipment(
            order: $order,
            shippingMethod: 'express',
            shippingProvider: 'dhl'
        );

        // Update tracking
        $updated = $this->fulfillmentService->updateTracking(
            fulfillmentId: $shipment->id,
            trackingNumber: '1Z999AA10123456784',
            trackingUrl: 'https://track.dhl.com/1Z999AA10123456784'
        );

        $this->assertEquals('1Z999AA10123456784', $updated->tracking_number);
    }

    public function testShipmentCanBeMarkedAsShipped(): void
    {
        $cart = $this->cartService->createCart(tenantId: 'tenant-123');

        $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-001',
            quantity: 1,
            price: 99.99
        );

        $cart = $this->cartService->getCart($cart->id);

        $order = $this->orderService->createOrderFromCart(
            cart: $cart,
            customerEmail: 'customer@example.com',
            shippingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            billingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            paymentMethod: 'stripe',
            customerId: 'customer-123'
        );

        $shipment = $this->fulfillmentService->createShipment(
            order: $order,
            shippingMethod: 'express',
            shippingProvider: 'dhl'
        );

        $shipped = $this->fulfillmentService->markAsShipped($shipment->id);

        $this->assertEquals('shipped', $shipped->status);
        $this->assertNotNull($shipped->shipped_at);
    }

    public function testShipmentCanBeMarkedAsDelivered(): void
    {
        $cart = $this->cartService->createCart(tenantId: 'tenant-123');

        $this->cartService->addItem(
            cartId: $cart->id,
            productId: 'prod-001',
            quantity: 1,
            price: 99.99
        );

        $cart = $this->cartService->getCart($cart->id);

        $order = $this->orderService->createOrderFromCart(
            cart: $cart,
            customerEmail: 'customer@example.com',
            shippingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            billingAddress: [
                'street' => '123 Main St',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postal_code' => '2000',
                'country' => 'AU',
            ],
            paymentMethod: 'stripe',
            customerId: 'customer-123'
        );

        $shipment = $this->fulfillmentService->createShipment(
            order: $order,
            shippingMethod: 'express',
            shippingProvider: 'dhl'
        );

        $this->fulfillmentService->markAsShipped($shipment->id);

        $delivered = $this->fulfillmentService->markAsDelivered($shipment->id);

        $this->assertEquals('delivered', $delivered->status);
        $this->assertNotNull($delivered->delivered_at);
    }
}
