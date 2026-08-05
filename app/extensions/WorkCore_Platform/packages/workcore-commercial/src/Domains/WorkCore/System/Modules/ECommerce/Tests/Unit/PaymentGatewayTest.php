<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Payments\StripeGateway;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Payments\PayPalGateway;

class PaymentGatewayTest extends TestCase
{
    public function testStripeGatewayChargeSuccess(): void
    {
        $gateway = new StripeGateway('test_key', 'test_secret', true);

        $result = $gateway->charge(100.00, [
            'token' => 'tok_visa',
        ]);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->transactionId);
        $this->assertEquals(100.00, $result->amount);
        $this->assertEquals('succeeded', $result->status);
    }

    public function testStripeGatewayValidation(): void
    {
        $gateway = new StripeGateway('test_key', 'test_secret', true);

        $errors = $gateway->validate([]);

        $this->assertNotEmpty($errors);
    }

    public function testStripeGatewayAuthorizeAndCapture(): void
    {
        $gateway = new StripeGateway('test_key', 'test_secret', true);

        $authResult = $gateway->authorize(100.00, [
            'token' => 'tok_visa',
        ]);

        $this->assertTrue($authResult->success);
        $this->assertNotNull($authResult->authorizationId);

        $captureResult = $gateway->capture($authResult->authorizationId, 100.00);

        $this->assertTrue($captureResult->success);
        $this->assertEquals('succeeded', $captureResult->status);
    }

    public function testStripeGatewayRefund(): void
    {
        $gateway = new StripeGateway('test_key', 'test_secret', true);

        $result = $gateway->refund('ch_123456', 100.00);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->transactionId);
    }

    public function testPayPalGatewayChargeSuccess(): void
    {
        $gateway = new PayPalGateway('test_key', 'test_secret', true);

        $result = $gateway->charge(100.00, [
            'email' => 'buyer@example.com',
        ]);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->transactionId);
        $this->assertEquals(100.00, $result->amount);
        $this->assertEquals('completed', $result->status);
    }

    public function testPayPalGatewayValidation(): void
    {
        $gateway = new PayPalGateway('test_key', 'test_secret', true);

        $errors = $gateway->validate([]);

        $this->assertNotEmpty($errors);
    }

    public function testPayPalGatewayAuthorizeAndCapture(): void
    {
        $gateway = new PayPalGateway('test_key', 'test_secret', true);

        $authResult = $gateway->authorize(100.00, [
            'email' => 'buyer@example.com',
        ]);

        $this->assertTrue($authResult->success);
        $this->assertNotNull($authResult->authorizationId);

        $captureResult = $gateway->capture($authResult->authorizationId, 100.00);

        $this->assertTrue($captureResult->success);
        $this->assertEquals('completed', $captureResult->status);
    }
}
