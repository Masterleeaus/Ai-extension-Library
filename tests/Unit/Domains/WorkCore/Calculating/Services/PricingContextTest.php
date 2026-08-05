<?php

namespace Tests\Unit\Domains\WorkCore\Calculating\Services;

use Tests\TestCase;
use App\Domains\WorkCore\Calculating\Services\PricingContext;

class PricingContextTest extends TestCase
{
    public function test_context_creation(): void
    {
        $context = new PricingContext(
            'company-1',
            100.00,
            ['type' => 'property', 'id' => 1],
            ['occupancy' => 85]
        );

        $this->assertEquals('company-1', $context->getCompanyId());
        $this->assertEquals(100.00, $context->getBasePrice());
        $this->assertEquals(100.00, $context->getCurrentPrice());
    }

    public function test_set_and_get_current_price(): void
    {
        $context = new PricingContext('company-1', 100.00);

        $context->setCurrentPrice(120.00);

        $this->assertEquals(120.00, $context->getCurrentPrice());
    }

    public function test_context_data_operations(): void
    {
        $context = new PricingContext('company-1', 100.00);

        $context->set('occupancy', 85);
        $context->set('demand', 75);

        $this->assertTrue($context->has('occupancy'));
        $this->assertEquals(85, $context->get('occupancy'));
        $this->assertEquals(75, $context->get('demand'));
        $this->assertNull($context->get('non_existent'));
        $this->assertEquals('default', $context->get('non_existent', 'default'));
    }

    public function test_add_applied_engine(): void
    {
        $context = new PricingContext('company-1', 100.00);

        $context->addAppliedEngine('dynamic_pricing', ['adjustment' => 20]);

        $this->assertContains('dynamic_pricing', $context->getAppliedEngines());
        $this->assertNotNull($context->getEngineResult('dynamic_pricing'));
    }

    public function test_price_history(): void
    {
        $context = new PricingContext('company-1', 100.00);

        $context->addPriceHistoryEntry('initial', 100.00, ['reason' => 'base_price']);
        $context->addPriceHistoryEntry('dynamic_pricing', 120.00, ['adjustment' => 20]);

        $history = $context->getPriceHistory();

        $this->assertCount(3, $history); // initial + base_price in constructor + our entry
        $this->assertEquals(120.00, $history[2]['price']);
    }

    public function test_execution_log(): void
    {
        $context = new PricingContext('company-1', 100.00);

        $context->addLogEntry('Calculation started', 'info');
        $context->addLogEntry('Engine applied', 'info');
        $context->addLogEntry('Price adjusted to 120', 'info');

        $log = $context->getExecutionLog();

        $this->assertCount(3, $log);
        $this->assertEquals('Calculation started', $log[0]['message']);
        $this->assertEquals('info', $log[0]['level']);
    }

    public function test_fluent_interface(): void
    {
        $context = new PricingContext('company-1', 100.00)
            ->set('occupancy', 85)
            ->set('demand', 75)
            ->setCurrentPrice(120.00)
            ->addLogEntry('Test');

        $this->assertEquals(120.00, $context->getCurrentPrice());
        $this->assertEquals(85, $context->get('occupancy'));
    }
}
