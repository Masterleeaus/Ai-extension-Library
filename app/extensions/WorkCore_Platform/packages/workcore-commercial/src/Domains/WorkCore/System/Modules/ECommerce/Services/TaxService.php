<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services;

class TaxService
{
    protected array $taxRates = [
        'AU' => 0.10, // 10% GST in Australia
        'US' => [
            'CA' => 0.0725, // 7.25% in California
            'TX' => 0.0625, // 6.25% in Texas
            'NY' => 0.08,   // 8% in New York
            'default' => 0.07,
        ],
        'GB' => 0.20,  // 20% VAT in UK
        'EU' => 0.19,  // 19% VAT (average for EU)
        'default' => 0.10,
    ];

    public function calculateTax(
        float $subtotal,
        string $country,
        ?string $state = null,
        bool $applyTax = true
    ): float {
        if (!$applyTax) {
            return 0;
        }

        $rate = $this->getTaxRate($country, $state);

        return round($subtotal * $rate, 2);
    }

    public function getTaxRate(string $country, ?string $state = null): float
    {
        if (!isset($this->taxRates[$country])) {
            return $this->taxRates['default'];
        }

        $countryRate = $this->taxRates[$country];

        // If it's an array (US-like), check for state-specific rates
        if (is_array($countryRate)) {
            if ($state && isset($countryRate[$state])) {
                return $countryRate[$state];
            }

            return $countryRate['default'] ?? $this->taxRates['default'];
        }

        return $countryRate;
    }

    public function getTaxByRegion(string $country, ?string $state = null): array
    {
        $rate = $this->getTaxRate($country, $state);

        return [
            'country' => $country,
            'state' => $state,
            'rate' => $rate,
            'percentage' => round($rate * 100, 2),
        ];
    }

    public function registerTaxRate(string $country, $rate, ?string $state = null): void
    {
        if ($state) {
            if (!isset($this->taxRates[$country])) {
                $this->taxRates[$country] = [];
            }

            if (!is_array($this->taxRates[$country])) {
                $this->taxRates[$country] = ['default' => $this->taxRates[$country]];
            }

            $this->taxRates[$country][$state] = $rate;
        } else {
            $this->taxRates[$country] = $rate;
        }
    }

    public function isTaxRequired(string $country): bool
    {
        return isset($this->taxRates[$country]) && $this->taxRates[$country] > 0;
    }

    public function getTotalWithTax(float $subtotal, string $country, ?string $state = null): float
    {
        $tax = $this->calculateTax($subtotal, $country, $state);

        return round($subtotal + $tax, 2);
    }
}
