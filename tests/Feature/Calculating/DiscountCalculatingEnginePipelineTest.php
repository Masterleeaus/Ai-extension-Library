<?php

namespace Tests\Feature\Calculating;

use App\Domains\WorkCore\Calculating\Engines\DiscountCalculatingEngine;
use App\Domains\WorkCore\Calculating\Services\PricingContext;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineRegistry;
use App\Domains\WorkCore\Calculating\Services\CalculatingEnginePipeline;
use Tests\TestCase;

class DiscountCalculatingEnginePipelineTest extends TestCase
{
    protected CalculatingEngineRegistry $registry;

    protected CalculatingEnginePipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new CalculatingEngineRegistry();
        $this->pipeline = new CalculatingEnginePipeline($this->registry);

        // Register discount engine
        $this->registry->registerEngineClass(
            'discount_engine',
            DiscountCalculatingEngine::class
        );
    }

    public function test_discount_engine_is_registered()
    {
        $this->assertTrue($this->registry->hasEngine('discount_engine'));
    }

    public function test_discount_engine_can_be_retrieved()
    {
        $engine = $this->registry->getEngine('discount_engine');

        $this->assertNotNull($engine);
        $this->assertInstanceOf(DiscountCalculatingEngine::class, $engine);
    }

    public function test_discount_engine_in_enabled_engines()
    {
        $enabledEngines = $this->registry->getEnabledEngines();

        $this->assertArrayHasKey('discount_engine', $enabledEngines);
    }

    public function test_discount_engine_by_type()
    {
        $discountEngines = $this->registry->getEnginesByType('discount');

        $this->assertArrayHasKey('discount_engine', $discountEngines);
    }

    public function test_discount_engine_priority()
    {
        $enginesByPriority = $this->registry->getEnginesByPriority();

        $this->assertArrayHasKey('discount_engine', $enginesByPriority);

        $engine = $enginesByPriority['discount_engine'];
        $this->assertEquals(20, $engine->getPriority());
    }

    public function test_pipeline_with_discount_engine()
    {
        $this->pipeline->setEngines(['discount_engine']);

        $context = new PricingContext(
            'company-1',
            100.00,
            ['type' => 'product'],
            ['user_id' => 1, 'plan_id' => '1', 'payment_gateway' => 'stripe', 'subscription_active' => false]
        );

        $result = $this->pipeline->execute($context);

        $this->assertTrue($result->isSuccessful());
        $this->assertNotNull($result->getContext());
    }

    public function test_discount_engine_price_history()
    {
        $this->pipeline->setEngines(['discount_engine']);

        $context = new PricingContext(
            'company-1',
            100.00,
            ['type' => 'product'],
            ['user_id' => 1, 'plan_id' => '1', 'payment_gateway' => 'stripe', 'subscription_active' => false]
        );

        $result = $this->pipeline->execute($context);

        // Check that price history was recorded
        $priceHistory = $context->getPriceHistory();
        $this->assertNotEmpty($priceHistory);

        // Initial price should be 100
        $this->assertEquals(100.00, $priceHistory[0]['price']);
    }

    public function test_discount_engine_execution_logging()
    {
        $this->pipeline->setEngines(['discount_engine']);

        $context = new PricingContext(
            'company-1',
            100.00,
            ['type' => 'product'],
            ['user_id' => 1, 'plan_id' => '1', 'payment_gateway' => 'stripe', 'subscription_active' => false]
        );

        $result = $this->pipeline->execute($context);

        // Check execution log
        $executionLog = $context->getExecutionLog();
        $this->assertNotEmpty($executionLog);
    }

    public function test_discount_engine_metadata()
    {
        $metadata = $this->registry->getEngineMetadata('discount_engine');

        $this->assertNotNull($metadata);
        $this->assertEquals('discount_engine', $metadata['id']);
        $this->assertEquals('Discount Engine', $metadata['name']);
        $this->assertEquals('1.0.0', $metadata['version']);
        $this->assertEquals('discount', $metadata['type']);
        $this->assertTrue($metadata['enabled']);
        $this->assertEquals(20, $metadata['priority']);
    }

    public function test_discount_engine_metadata_all_engines()
    {
        $allMetadata = $this->registry->getAllEnginesMetadata();

        $this->assertArrayHasKey('discount_engine', $allMetadata);
        $this->assertEquals('Discount Engine', $allMetadata['discount_engine']['name']);
    }
}
