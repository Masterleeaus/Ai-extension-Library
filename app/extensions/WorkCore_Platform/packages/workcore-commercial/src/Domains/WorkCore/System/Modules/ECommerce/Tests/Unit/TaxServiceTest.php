<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\TaxService;

class TaxServiceTest extends TestCase
{
    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = new TaxService();
    }

    public function testAustralianTaxCalculation(): void
    {
        $tax = $this->taxService->calculateTax(100, 'AU');

        $this->assertEquals(10.0, $tax);
    }

    public function testUsaTaxCalculation(): void
    {
        $tax = $this->taxService->calculateTax(100, 'US', 'CA');

        $this->assertEquals(7.25, $tax);
    }

    public function testUkTaxCalculation(): void
    {
        $tax = $this->taxService->calculateTax(100, 'GB');

        $this->assertEquals(20.0, $tax);
    }

    public function testDefaultTaxWhenCountryNotFound(): void
    {
        $tax = $this->taxService->calculateTax(100, 'ZZ');

        $this->assertEquals(10.0, $tax);
    }

    public function testTaxCanBeDisabled(): void
    {
        $tax = $this->taxService->calculateTax(100, 'AU', applyTax: false);

        $this->assertEquals(0.0, $tax);
    }

    public function testTotalWithTax(): void
    {
        $total = $this->taxService->getTotalWithTax(100, 'AU');

        $this->assertEquals(110.0, $total);
    }

    public function testTaxRateCanBeRegistered(): void
    {
        $this->taxService->registerTaxRate('ZZ', 0.15);

        $tax = $this->taxService->calculateTax(100, 'ZZ');

        $this->assertEquals(15.0, $tax);
    }

    public function testStateTaxCanBeRegistered(): void
    {
        $this->taxService->registerTaxRate('US', 0.09, 'NV');

        $tax = $this->taxService->calculateTax(100, 'US', 'NV');

        $this->assertEquals(9.0, $tax);
    }
}
