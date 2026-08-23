<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping\DHLProvider;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Shipping\AustraliaPostProvider;

class ShippingProviderTest extends TestCase
{
    public function testDHLRateCalculation(): void
    {
        $provider = new DHLProvider('test_key', 'test_secret', true);

        $rate = $provider->calculateRate(
            origin: ['city' => 'Los Angeles', 'country' => 'US'],
            destination: ['city' => 'New York', 'country' => 'US'],
            package: ['weight' => 2, 'dimensions' => '10x10x10']
        );

        $this->assertNotNull($rate);
        $this->assertGreater($rate->rate, 0);
        $this->assertEquals('USD', $rate->currency);
    }

    public function testDHLShipmentCreation(): void
    {
        $provider = new DHLProvider('test_key', 'test_secret', true);

        $result = $provider->createShipment(
            origin: ['city' => 'Los Angeles', 'country' => 'US'],
            destination: ['city' => 'New York', 'country' => 'US'],
            package: ['weight' => 2],
            items: [['product_id' => 'prod-123', 'quantity' => 1]]
        );

        $this->assertTrue($result->success);
        $this->assertNotNull($result->shipmentId);
        $this->assertNotNull($result->trackingNumber);
    }

    public function testDHLTracking(): void
    {
        $provider = new DHLProvider('test_key', 'test_secret', true);

        $tracking = $provider->getTracking('1Z123456789');

        $this->assertNotNull($tracking);
        $this->assertEquals('1Z123456789', $tracking->trackingNumber);
        $this->assertNotEmpty($tracking->events);
    }

    public function testAustraliaPostRateCalculation(): void
    {
        $provider = new AustraliaPostProvider('test_key', 'test_secret', true);

        $rate = $provider->calculateRate(
            origin: ['city' => 'Sydney', 'country' => 'AU'],
            destination: ['city' => 'Melbourne', 'country' => 'AU'],
            package: ['weight' => 500]
        );

        $this->assertNotNull($rate);
        $this->assertGreater($rate->rate, 0);
        $this->assertEquals('AUD', $rate->currency);
        $this->assertEquals(8.50, $rate->rate);
    }

    public function testAustraliaPostShipmentCreation(): void
    {
        $provider = new AustraliaPostProvider('test_key', 'test_secret', true);

        $result = $provider->createShipment(
            origin: ['city' => 'Sydney', 'country' => 'AU'],
            destination: ['city' => 'Melbourne', 'country' => 'AU'],
            package: ['weight' => 500],
            items: [['product_id' => 'prod-123', 'quantity' => 1]]
        );

        $this->assertTrue($result->success);
        $this->assertNotNull($result->shipmentId);
        $this->assertNotNull($result->trackingNumber);
    }

    public function testAustraliaPostTracking(): void
    {
        $provider = new AustraliaPostProvider('test_key', 'test_secret', true);

        $tracking = $provider->getTracking('AP123456789012');

        $this->assertNotNull($tracking);
        $this->assertEquals('AP123456789012', $tracking->trackingNumber);
        $this->assertNotEmpty($tracking->events);
    }
}
