<?php

namespace App\Domains\WorkCore\Pricing\Services;

use App\Domains\WorkCore\Calculating\Services\CalculatingEngineService;
use App\Domains\WorkCore\Calculating\Services\PricingContext;
use App\Domains\WorkCore\Pricing\Models\PriceHistory;
use App\Domains\WorkCore\Pricing\Models\PricingRule;
use App\Domains\WorkCore\Pricing\Models\SeasonalRate;
use App\Domains\WorkCore\Pricing\Models\DemandIndicator;
use App\Domains\WorkCore\Pricing\Models\OccupancyData;

class PricingService
{
    protected CalculatingEngineService $calculatingEngine;

    public function __construct(CalculatingEngineService $calculatingEngine)
    {
        $this->calculatingEngine = $calculatingEngine;
    }

    /**
     * Calculate price for a resource with all applicable engines
     */
    public function calculatePrice(
        string $companyId,
        float $basePrice,
        array $resource = [],
        array $contextData = [],
        array $options = []
    ): array {
        $context = new PricingContext($companyId, $basePrice, $resource, $contextData);

        $result = $this->calculatingEngine->calculateWithDefaults($context);

        // Track price history
        if ($result->isSuccessful() && $result->getFinalPrice() !== $basePrice) {
            PriceHistory::trackPrice(
                $companyId,
                $resource['type'] ?? 'unknown',
                $resource['id'] ?? 0,
                $basePrice,
                $result->getFinalPrice(),
                'dynamic_pricing',
                [],
                [],
                $options['applied_by_user_id'] ?? null
            );
        }

        return $result->toArray();
    }

    /**
     * Get adjusted price for a resource
     */
    public function getAdjustedPrice(
        string $companyId,
        float $basePrice,
        string $resourceType,
        int $resourceId
    ): float {
        $result = $this->calculatePrice(
            $companyId,
            $basePrice,
            [
                'type' => $resourceType,
                'id' => $resourceId,
            ]
        );

        return $result['final_price'] ?? $basePrice;
    }

    /**
     * Apply pricing rules to base price
     */
    public function applyRules(
        string $companyId,
        float $basePrice,
        array $context = []
    ): float {
        $rules = PricingRule::getMatchingRules($companyId, $context);
        $price = $basePrice;

        foreach ($rules as $rule) {
            $price = $rule->applyRule($price, $context);
        }

        return $price;
    }

    /**
     * Get all pricing rules for a company
     */
    public function getPricingRules(string $companyId, array $filters = []): array
    {
        $query = PricingRule::where('company_id', $companyId);

        if ($filters['active_only'] ?? false) {
            $query->where('is_active', true);
        }

        if (isset($filters['rule_type'])) {
            $query->where('rule_type', $filters['rule_type']);
        }

        if ($filters['ordered_by_priority'] ?? false) {
            $query->orderBy('priority', 'asc');
        }

        return $query->get()->toArray();
    }

    /**
     * Create a new pricing rule
     */
    public function createPricingRule(
        string $companyId,
        array $data,
        int $createdByUserId
    ): PricingRule {
        $data['company_id'] = $companyId;
        $data['created_by_user_id'] = $createdByUserId;

        return PricingRule::create($data);
    }

    /**
     * Update a pricing rule
     */
    public function updatePricingRule(int $ruleId, array $data): PricingRule
    {
        $rule = PricingRule::findOrFail($ruleId);
        $rule->update($data);

        return $rule;
    }

    /**
     * Delete a pricing rule
     */
    public function deletePricingRule(int $ruleId): void
    {
        PricingRule::findOrFail($ruleId)->delete();
    }

    /**
     * Get seasonal rates
     */
    public function getSeasonalRates(string $companyId, array $filters = []): array
    {
        $query = SeasonalRate::where('company_id', $companyId);

        if ($filters['active_only'] ?? false) {
            $query->where('is_active', true);
        }

        if (isset($filters['season_type'])) {
            $query->where('season_type', $filters['season_type']);
        }

        return $query->get()->toArray();
    }

    /**
     * Create a seasonal rate
     */
    public function createSeasonalRate(
        string $companyId,
        array $data,
        int $createdByUserId
    ): SeasonalRate {
        $data['company_id'] = $companyId;
        $data['created_by_user_id'] = $createdByUserId;

        return SeasonalRate::create($data);
    }

    /**
     * Get demand indicators
     */
    public function getDemandIndicators(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): array {
        return DemandIndicator::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->toArray();
    }

    /**
     * Record demand signal
     */
    public function recordDemandSignal(
        string $companyId,
        string $resourceType,
        int $resourceId,
        string $signalType, // booking, search, inquiry
        int $count = 1
    ): DemandIndicator {
        $indicator = DemandIndicator::getLatest($companyId, $resourceType, $resourceId)
            ?? new DemandIndicator([
                'company_id' => $companyId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'booking_count' => 0,
                'search_count' => 0,
                'inquiry_count' => 0,
                'recorded_at' => now(),
            ]);

        // Update based on signal type
        match ($signalType) {
            'booking' => $indicator->recordBooking(),
            'search' => $indicator->recordSearch(),
            'inquiry' => $indicator->recordInquiry(),
            default => null,
        };

        return $indicator;
    }

    /**
     * Get occupancy data
     */
    public function getOccupancyData(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): ?array {
        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);

        return $occupancy?->toArray();
    }

    /**
     * Record occupancy snapshot
     */
    public function recordOccupancy(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $currentOccupancy,
        int $capacity,
        int $reservedUnits = 0,
        int $pendingBookings = 0
    ): OccupancyData {
        return OccupancyData::recordOccupancy(
            $companyId,
            $resourceType,
            $resourceId,
            $currentOccupancy,
            $capacity,
            $reservedUnits,
            $pendingBookings
        );
    }

    /**
     * Get price history
     */
    public function getPriceHistory(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $days = 30
    ): array {
        return PriceHistory::getPriceChangeHistory(
            $companyId,
            $resourceType,
            $resourceId,
            $days
        );
    }

    /**
     * Get price statistics
     */
    public function getPriceStatistics(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $days = 30
    ): array {
        $average = PriceHistory::getAveragePrice(
            $companyId,
            $resourceType,
            $resourceId,
            $days
        );

        $history = PriceHistory::getPriceChangeHistory(
            $companyId,
            $resourceType,
            $resourceId,
            $days
        );

        if (empty($history)) {
            return [
                'average_price' => $average,
                'min_price' => null,
                'max_price' => null,
                'price_range' => 0,
                'total_adjustments' => 0,
            ];
        }

        $prices = array_map(fn ($item) => $item['adjusted_price'], $history);
        $minPrice = min($prices);
        $maxPrice = max($prices);

        return [
            'average_price' => $average,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'price_range' => $maxPrice - $minPrice,
            'total_adjustments' => count($history),
            'adjustments_by_reason' => PriceHistory::getReasonSummary(
                $companyId,
                $resourceType,
                $resourceId
            ),
        ];
    }

    /**
     * Get calculating engine service
     */
    public function getCalculatingEngine(): CalculatingEngineService
    {
        return $this->calculatingEngine;
    }
}
