<?php

namespace Tests\Unit\Calculating;

use App\Domains\WorkCore\Calculating\Engines\DiscountCalculatingEngine;
use App\Domains\WorkCore\Calculating\Services\PricingContext;
use App\Extensions\DiscountManager\System\Models\ConditionalDiscount;
use App\Models\Coupon;
use PHPUnit\Framework\TestCase;

class DiscountCalculatingEngineTest extends TestCase
{
    protected DiscountCalculatingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DiscountCalculatingEngine();
    }

    public function test_engine_identity()
    {
        $this->assertEquals('discount_engine', $this->engine->getEngineId());
        $this->assertEquals('Discount Engine', $this->engine->getEngineName());
        $this->assertEquals('1.0.0', $this->engine->getEngineVersion());
        $this->assertEquals('discount', $this->engine->getEngineType());
    }

    public function test_engine_priority()
    {
        $this->assertEquals(20, $this->engine->getPriority());
    }

    public function test_engine_is_enabled_by_default()
    {
        $this->assertTrue($this->engine->isEnabled());
    }

    public function test_can_set_enabled_status()
    {
        $this->engine->setEnabled(false);
        $this->assertFalse($this->engine->isEnabled());

        $this->engine->setEnabled(true);
        $this->assertTrue($this->engine->isEnabled());
    }

    public function test_engine_metadata()
    {
        $metadata = $this->engine->getMetadata();

        $this->assertArrayHasKey('description', $metadata);
        $this->assertArrayHasKey('capabilities', $metadata);
        $this->assertArrayHasKey('supported_conditions', $metadata);
        $this->assertContains('conditional_discounts', $metadata['capabilities']);
        $this->assertContains('coupon_validation', $metadata['capabilities']);
    }

    public function test_engine_conflicts()
    {
        $this->assertTrue($this->engine->hasConflictWith('promotion_engine'));
        $this->assertTrue($this->engine->hasConflictWith('loyalty_engine'));
        $this->assertFalse($this->engine->hasConflictWith('tax_engine'));
    }

    public function test_conflict_resolution_strategy()
    {
        $this->assertEquals('maximum', $this->engine->getConflictResolution('promotion_engine'));
    }

    public function test_engine_config()
    {
        $config = $this->engine->getConfig();

        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('allow_multiple_discounts', $config);
        $this->assertArrayHasKey('max_discount_percentage', $config);
        $this->assertArrayHasKey('min_discount_amount', $config);
    }

    public function test_engine_with_custom_config()
    {
        $customConfig = [
            'enabled' => false,
            'allow_multiple_discounts' => true,
            'max_discount_percentage' => 50,
        ];

        $engine = new DiscountCalculatingEngine($customConfig);
        $config = $engine->getConfig();

        $this->assertFalse($config['enabled']);
        $this->assertTrue($config['allow_multiple_discounts']);
        $this->assertEquals(50, $config['max_discount_percentage']);
    }

    public function test_calculate_with_no_applicable_discount()
    {
        $context = new PricingContext(
            'company-1',
            100.00,
            ['type' => 'product'],
            []
        );

        $result = $this->engine->calculate($context);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(100.00, $result->getCalculatedPrice());
        $this->assertEquals(0.00, $result->getAdjustmentAmount());
        $this->assertStringContainsString('No applicable discount', $result->getReason());
    }
}
