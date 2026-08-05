<?php

namespace Tests\Unit\Domains\WorkCore\Calculating\Services;

use Tests\TestCase;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineRegistry;
use App\Domains\WorkCore\Pricing\Engines\DynamicPricingEngine;

class CalculatingEngineRegistryTest extends TestCase
{
    protected CalculatingEngineRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CalculatingEngineRegistry();
    }

    public function test_register_engine_class(): void
    {
        $this->registry->registerEngineClass(
            'dynamic_pricing',
            DynamicPricingEngine::class
        );

        $this->assertTrue($this->registry->hasEngine('dynamic_pricing'));
    }

    public function test_register_engine_instance(): void
    {
        $engine = new DynamicPricingEngine();
        $this->registry->registerEngine($engine);

        $this->assertTrue($this->registry->hasEngine('dynamic_pricing'));
        $retrieved = $this->registry->getEngine('dynamic_pricing');
        $this->assertInstanceOf(DynamicPricingEngine::class, $retrieved);
    }

    public function test_get_engine_returns_null_if_not_registered(): void
    {
        $engine = $this->registry->getEngine('non_existent');
        $this->assertNull($engine);
    }

    public function test_get_all_engines(): void
    {
        $this->registry->registerEngineClass(
            'dynamic_pricing',
            DynamicPricingEngine::class
        );

        $engines = $this->registry->getAllEngines();

        $this->assertArrayHasKey('dynamic_pricing', $engines);
    }

    public function test_get_enabled_engines(): void
    {
        $engine = new DynamicPricingEngine();
        $this->registry->registerEngine($engine);

        $enabled = $this->registry->getEnabledEngines();

        $this->assertArrayHasKey('dynamic_pricing', $enabled);
    }

    public function test_get_engines_by_type(): void
    {
        $engine = new DynamicPricingEngine();
        $this->registry->registerEngine($engine);

        $pricingEngines = $this->registry->getEnginesByType('pricing');

        $this->assertArrayHasKey('dynamic_pricing', $pricingEngines);
    }

    public function test_get_engines_by_priority(): void
    {
        $engine1 = new DynamicPricingEngine();
        $this->registry->registerEngine($engine1);

        $engines = $this->registry->getEnginesByPriority(['dynamic_pricing']);

        $this->assertNotEmpty($engines);
        $first = reset($engines);
        $this->assertEquals($first->getPriority(), $engine1->getPriority());
    }

    public function test_unregister_engine(): void
    {
        $engine = new DynamicPricingEngine();
        $this->registry->registerEngine($engine);

        $this->assertTrue($this->registry->hasEngine('dynamic_pricing'));

        $this->registry->unregisterEngine('dynamic_pricing');

        $this->assertFalse($this->registry->hasEngine('dynamic_pricing'));
    }

    public function test_get_engine_metadata(): void
    {
        $engine = new DynamicPricingEngine();
        $this->registry->registerEngine($engine);

        $metadata = $this->registry->getEngineMetadata('dynamic_pricing');

        $this->assertNotNull($metadata);
        $this->assertEquals('dynamic_pricing', $metadata['id']);
        $this->assertEquals('pricing', $metadata['type']);
    }
}
