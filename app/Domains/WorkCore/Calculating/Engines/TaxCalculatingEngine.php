<?php

namespace App\Domains\WorkCore\Calculating\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;

class TaxCalculatingEngine implements CalculatingEngineContract
{
    protected bool $enabled = true;

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function getEngineId(): string
    {
        return 'tax_engine';
    }

    public function getEngineName(): string
    {
        return 'Tax Calculation Engine';
    }

    public function getEngineVersion(): string
    {
        return '1.0.0';
    }

    public function getEngineType(): string
    {
        return 'tax';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && config('calculating-engines.engines.tax_engine.enabled', false);
    }

    public function shouldApply(PricingContextContract $context): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $country = $context->get('country');
        $state = $context->get('state');

        return !empty($country);
    }

    public function calculate(PricingContextContract $context): CalculatingEngineResult
    {
        try {
            $basePrice = $context->getCurrentPrice();
            $country = $context->get('country');
            $state = $context->get('state');

            // Calculate tax based on region
            $taxRate = $this->getTaxRate($country, $state);
            $taxAmount = ($basePrice * $taxRate) / 100;

            $adjustedPrice = $basePrice + $taxAmount;

            return CalculatingEngineResult::success(
                $this->getEngineId(),
                $basePrice,
                $adjustedPrice,
                sprintf('Tax applied: %.2f%% ($%.2f)', $taxRate, $taxAmount),
                [
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'country' => $country,
                    'state' => $state,
                ],
                [
                    'tax_rate_code' => "{$country}_{$state}",
                ]
            );
        } catch (\Exception $e) {
            return CalculatingEngineResult::failure(
                $this->getEngineId(),
                $context->getCurrentPrice(),
                "Tax calculation failed: {$e->getMessage()}",
                ['error' => $e->getMessage()]
            );
        }
    }

    protected function getTaxRate(string $country, ?string $state = null): float
    {
        // Standard tax rates by country (in percentage)
        // These are default values - should be customized per implementation
        $countryTaxRates = [
            'US' => 7.5,  // US average sales tax
            'CA' => 5.0,  // Canada GST
            'GB' => 20.0, // UK VAT
            'DE' => 19.0, // Germany VAT
            'FR' => 20.0, // France VAT
            'JP' => 10.0, // Japan consumption tax
            'AU' => 10.0, // Australia GST
            'NZ' => 15.0, // New Zealand GST
        ];

        // State-specific rates for US
        if ($country === 'US' && !empty($state)) {
            $stateRates = [
                'CA' => 7.25, // California
                'TX' => 6.25, // Texas
                'NY' => 8.875, // New York
                'FL' => 6.0, // Florida
            ];
            if (isset($stateRates[$state])) {
                return $stateRates[$state];
            }
        }

        return $countryTaxRates[$country] ?? 0.0;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMetadata(): array
    {
        return [
            'description' => 'Calculates and applies tax based on region and product type',
            'capabilities' => [
                'regional_tax_rates',
                'product_tax_classes',
                'tax_exemptions',
                'tax_reporting',
            ],
            'supported_conditions' => [
                'country',
                'state',
                'product_type',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 5; // Run early in pipeline
    }

    public function hasConflictWith(string $engineId): bool
    {
        return false;
    }

    public function getConflictResolution(string $engineId): string
    {
        return 'maximum';
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => false,
            'use_product_tax_class' => true,
            'include_tax_in_price' => false,
            'tax_rounding' => 'standard',
        ];
    }
}
