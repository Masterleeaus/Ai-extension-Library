<?php

namespace App\Domains\WorkCore\Pricing\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;
use App\Domains\WorkCore\Pricing\Algorithms\DemandPricingAlgorithm;
use App\Domains\WorkCore\Pricing\Algorithms\RevenueManagementAlgorithm;
use App\Domains\WorkCore\Pricing\Models\PricingRule;
use App\Domains\WorkCore\Pricing\Models\SeasonalRate;
use App\Domains\WorkCore\Pricing\Models\OccupancyData;
use App\Domains\WorkCore\Pricing\Models\DemandIndicator;

class DynamicPricingEngine implements CalculatingEngineContract
{
    protected DemandPricingAlgorithm $demandAlgorithm;

    protected RevenueManagementAlgorithm $revenueAlgorithm;

    protected array $config;

    protected bool $enabled = true;

    public function __construct(
        DemandPricingAlgorithm $demandAlgorithm = null,
        RevenueManagementAlgorithm $revenueAlgorithm = null,
        array $config = []
    ) {
        $this->demandAlgorithm = $demandAlgorithm ?? new DemandPricingAlgorithm();
        $this->revenueAlgorithm = $revenueAlgorithm ?? new RevenueManagementAlgorithm();
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function getEngineId(): string
    {
        return 'dynamic_pricing';
    }

    public function getEngineName(): string
    {
        return 'Dynamic Pricing Engine';
    }

    public function getEngineVersion(): string
    {
        return '1.0.0';
    }

    public function getEngineType(): string
    {
        return 'pricing';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function shouldApply(PricingContextContract $context): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        // Check if resource type is supported
        $resource = $context->getResource();
        $supportedTypes = $this->config['supported_resource_types'] ?? [];

        if (! empty($supportedTypes) && ! in_array($resource['type'] ?? null, $supportedTypes)) {
            return false;
        }

        // Check if company has dynamic pricing enabled
        $companyId = $context->getCompanyId();
        $hasPricingRules = PricingRule::where('company_id', $companyId)
            ->where('is_active', true)
            ->exists();

        return $hasPricingRules || $context->has('force_dynamic_pricing');
    }

    public function calculate(PricingContextContract $context): CalculatingEngineResult
    {
        try {
            $companyId = $context->getCompanyId();
            $basePrice = $context->getBasePrice();
            $resource = $context->getResource();
            $resourceType = $resource['type'] ?? 'unknown';
            $resourceId = $resource['id'] ?? 0;

            $factors = [];
            $adjustedPrice = $basePrice;
            $details = [];

            // 1. Apply pricing rules
            if ($this->config['enable_rules'] ?? true) {
                $rulesResult = $this->applyPricingRules(
                    $companyId,
                    $basePrice,
                    $context
                );
                $adjustedPrice = $rulesResult['price'];
                $factors['rules'] = $rulesResult['factors'];
                $details['applied_rules'] = $rulesResult['applied_rules'];
            }

            // 2. Apply seasonal rates
            if ($this->config['enable_seasonal'] ?? true) {
                $seasonalResult = $this->applySeasonalRates($companyId, $adjustedPrice);
                $adjustedPrice = $seasonalResult['price'];
                $factors['seasonal'] = $seasonalResult['factors'];
                $details['applied_seasons'] = $seasonalResult['applied_seasons'];
            }

            // 4. Apply occupancy-based pricing
            if ($this->config['enable_occupancy'] ?? true) {
                $occupancyResult = $this->applyOccupancyPricing(
                    $companyId,
                    $resourceType,
                    $resourceId,
                    $adjustedPrice
                );
                $adjustedPrice = $occupancyResult['price'];
                $factors['occupancy'] = $occupancyResult['factors'];
                $details['occupancy_percentage'] = $occupancyResult['occupancy_percentage'] ?? null;
            }

            // 5. Apply demand-based pricing
            if ($this->config['enable_demand'] ?? true) {
                $demandResult = $this->applyDemandPricing(
                    $companyId,
                    $resourceType,
                    $resourceId,
                    $adjustedPrice
                );
                $adjustedPrice = $demandResult['price'];
                $factors['demand'] = $demandResult['factors'];
                $details['demand_level'] = $demandResult['demand_level'] ?? null;
            }

            // Enforce price bounds
            if ($this->config['min_price'] !== null) {
                $adjustedPrice = max($adjustedPrice, $this->config['min_price']);
            }
            if ($this->config['max_price'] !== null) {
                $adjustedPrice = min($adjustedPrice, $this->config['max_price']);
            }

            $reason = $this->buildReason($factors);

            return CalculatingEngineResult::success(
                $this->getEngineId(),
                $basePrice,
                $adjustedPrice,
                $reason,
                $factors,
                $details
            );
        } catch (\Exception $e) {
            return CalculatingEngineResult::failure(
                $this->getEngineId(),
                $context->getBasePrice(),
                "Dynamic pricing calculation failed: {$e->getMessage()}",
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Apply pricing rules
     */
    protected function applyPricingRules(
        string $companyId,
        float $basePrice,
        PricingContextContract $context
    ): array {
        $rules = PricingRule::getMatchingRules($companyId, $context->getContextData());

        $price = $basePrice;
        $appliedRules = [];
        $factors = [];

        foreach ($rules as $rule) {
            $rulePrice = $rule->applyRule($price, $context->getContextData());
            if ($rulePrice !== $price) {
                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'adjustment' => $rulePrice - $price,
                ];
                $price = $rulePrice;
            }
        }

        if (! empty($appliedRules)) {
            $factors['rules_count'] = count($appliedRules);
            $factors['price_change'] = $price - $basePrice;
        }

        return [
            'price' => $price,
            'factors' => $factors,
            'applied_rules' => $appliedRules,
        ];
    }

    /**
     * Apply seasonal rates
     */
    protected function applySeasonalRates(string $companyId, float $basePrice): array
    {
        $multiplier = SeasonalRate::getCombinedMultiplier($companyId, now());
        $price = $basePrice * $multiplier;

        $activeSeasons = SeasonalRate::getActiveSeasonsForDate($companyId, now());
        $seasonNames = array_map(fn ($season) => $season->season_name, $activeSeasons);

        return [
            'price' => $price,
            'factors' => [
                'multiplier' => $multiplier,
                'seasons' => $seasonNames,
            ],
            'applied_seasons' => $seasonNames,
        ];
    }

    /**
     * Apply occupancy-based pricing
     */
    protected function applyOccupancyPricing(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): array {
        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);

        if (! $occupancy) {
            return [
                'price' => $basePrice,
                'factors' => [],
                'occupancy_percentage' => null,
            ];
        }

        $percentage = $occupancy->occupancy_percentage ?? 0;

        // Calculate occupancy multiplier
        $multiplier = match (true) {
            $percentage < 30 => 0.85, // 15% discount for low occupancy
            $percentage < 50 => 0.92,
            $percentage < 70 => 1.0,
            $percentage < 85 => 1.08,
            $percentage < 95 => 1.15,
            default => 1.25, // 25% premium for critical occupancy
        };

        $price = $basePrice * $multiplier;

        return [
            'price' => $price,
            'factors' => [
                'multiplier' => $multiplier,
                'occupancy_percentage' => $percentage,
            ],
            'occupancy_percentage' => $percentage,
        ];
    }

    /**
     * Apply demand-based pricing
     */
    protected function applyDemandPricing(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): array {
        $result = $this->demandAlgorithm->calculate(
            $companyId,
            $resourceType,
            $resourceId,
            $basePrice
        );

        return [
            'price' => $result['adjusted_price'],
            'factors' => [
                'demand_factor' => $result['demand_factor'],
                'demand_level' => $result['demand_level'],
                'demand_score' => $result['demand_score'] ?? 0,
            ],
            'demand_level' => $result['demand_level'],
        ];
    }

    /**
     * Build human-readable reason string
     */
    protected function buildReason(array $factors): string
    {
        $reasons = [];

        if (isset($factors['rules']) && $factors['rules']) {
            $reasons[] = 'pricing rules';
        }
        if (isset($factors['seasonal']) && $factors['seasonal']) {
            $reasons[] = 'seasonal rates';
        }
        if (isset($factors['occupancy']) && $factors['occupancy']) {
            $reasons[] = 'occupancy levels';
        }
        if (isset($factors['demand']) && $factors['demand']) {
            $reasons[] = 'demand indicators';
        }

        return 'Dynamic pricing: ' . (empty($reasons) ? 'no adjustments' : implode(', ', $reasons));
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMetadata(): array
    {
        return [
            'description' => 'Adjusts prices based on demand, seasonality, occupancy, and pricing rules',
            'capabilities' => [
                'demand_based',
                'seasonal_rates',
                'occupancy_based',
                'pricing_rules',
                'revenue_optimization',
            ],
            'supported_resource_types' => $this->config['supported_resource_types'] ?? ['all'],
        ];
    }

    public function getPriority(): int
    {
        return 100; // Run after discounts but before tax
    }

    public function hasConflictWith(string $engineId): bool
    {
        // Dynamic pricing may conflict with discount engine if both increase/decrease prices
        return in_array($engineId, ['discount_engine', 'promotion_engine']);
    }

    public function getConflictResolution(string $engineId): string
    {
        return 'maximum'; // Use highest price when conflict with discount
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enable_rules' => true,
            'enable_seasonal' => true,
            'enable_occupancy' => true,
            'enable_demand' => true,
            'min_price' => null,
            'max_price' => null,
            'supported_resource_types' => [],
        ];
    }
}
