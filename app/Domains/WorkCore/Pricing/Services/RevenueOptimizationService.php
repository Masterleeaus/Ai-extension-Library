<?php

namespace App\Domains\WorkCore\Pricing\Services;

use App\Domains\WorkCore\Pricing\Models\PriceHistory;
use App\Domains\WorkCore\Pricing\Models\OccupancyData;
use App\Domains\WorkCore\Pricing\Models\DemandIndicator;
use App\Domains\WorkCore\Pricing\Algorithms\RevenueManagementAlgorithm;

class RevenueOptimizationService
{
    protected RevenueManagementAlgorithm $algorithm;

    public function __construct(RevenueManagementAlgorithm $algorithm = null)
    {
        $this->algorithm = $algorithm ?? new RevenueManagementAlgorithm();
    }

    /**
     * Optimize revenue by suggesting optimal pricing
     */
    public function optimizeRevenue(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): array {
        $optimized = $this->algorithm->optimize(
            $companyId,
            $resourceType,
            $resourceId,
            $basePrice
        );

        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);
        $demand = DemandIndicator::getLatest($companyId, $resourceType, $resourceId);

        $booked = $occupancy?->current_occupancy ?? 0;
        $capacity = $occupancy?->capacity ?? 1;

        return [
            'current_price' => $basePrice,
            'optimal_price' => $optimized['optimal_price'],
            'expected_revenue' => $optimized['expected_revenue'],
            'occupancy_factor' => $optimized['occupancy_factor'],
            'demand_factor' => $optimized['demand_factor'],
            'occupancy_percentage' => $optimized['occupancy_percentage'] ?? 0,
            'demand_score' => $optimized['demand_score'] ?? 0,
            'potential_revenue_at_optimal' => ($booked * $optimized['optimal_price']) ?? 0,
            'potential_additional_revenue' => (($booked * $optimized['optimal_price']) - ($booked * $basePrice)) ?? 0,
        ];
    }

    /**
     * Suggest prices for different scenarios
     */
    public function suggestPrices(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): array {
        $suggestions = $this->algorithm->suggestPrices(
            $companyId,
            $resourceType,
            $resourceId,
            $basePrice
        );

        return [
            'base_price' => $basePrice,
            'conservative_price' => $suggestions['conservative'],
            'moderate_price' => $suggestions['moderate'],
            'aggressive_price' => $suggestions['aggressive'],
            'max_revenue_price' => $suggestions['max_revenue'] ?? $suggestions['aggressive'],
            'recommendations' => $this->algorithm->getOptimizationRecommendations(
                $companyId,
                $resourceType,
                $resourceId
            ),
        ];
    }

    /**
     * Calculate revenue forecast
     */
    public function calculateRevenueForecast(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $days = 30
    ): array {
        return $this->algorithm->calculateRevenueForecast(
            $companyId,
            $resourceType,
            $resourceId,
            $days
        );
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $days = 30
    ): array {
        $priceHistory = PriceHistory::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($priceHistory->isEmpty()) {
            return [
                'data_available' => false,
                'reason' => 'No price history available',
            ];
        }

        $prices = $priceHistory->pluck('adjusted_price')->toArray();
        $adjustments = $priceHistory->pluck('adjustment_percentage')->toArray();
        $reasons = $priceHistory->pluck('reason')->toArray();

        return [
            'data_available' => true,
            'period_days' => $days,
            'total_price_changes' => count($priceHistory),
            'average_price' => array_sum($prices) / count($prices),
            'min_price' => min($prices),
            'max_price' => max($prices),
            'price_range' => max($prices) - min($prices),
            'average_adjustment_percentage' => array_sum($adjustments) / count($adjustments),
            'total_adjustments_up' => count(array_filter($adjustments, fn ($a) => $a > 0)),
            'total_adjustments_down' => count(array_filter($adjustments, fn ($a) => $a < 0)),
            'reasons_breakdown' => array_count_values($reasons),
        ];
    }

    /**
     * Compare revenue with different pricing strategies
     */
    public function compareStrategies(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): array {
        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);

        if (! $occupancy) {
            return [
                'comparison_available' => false,
                'reason' => 'No occupancy data available',
            ];
        }

        $booked = $occupancy->current_occupancy;
        $capacity = $occupancy->capacity;

        // Strategy 1: Current pricing
        $currentRevenue = $booked * $basePrice;

        // Strategy 2: Discount to increase occupancy
        $discountPrice = $basePrice * 0.85;
        $estimatedOccupancyIncrease = min($capacity, $booked + ($capacity - $booked) * 0.3);
        $discountRevenue = $estimatedOccupancyIncrease * $discountPrice;

        // Strategy 3: Premium pricing
        $premiumPrice = $basePrice * 1.15;
        $estimatedOccupancyDecrease = max(0, $booked * 0.9);
        $premiumRevenue = $estimatedOccupancyDecrease * $premiumPrice;

        // Strategy 4: Dynamic pricing (optimal)
        $optimized = $this->optimizeRevenue($companyId, $resourceType, $resourceId, $basePrice);

        return [
            'comparison_available' => true,
            'current_occupancy' => $booked,
            'capacity' => $capacity,
            'strategies' => [
                'current' => [
                    'price' => $basePrice,
                    'estimated_occupancy' => $booked,
                    'estimated_revenue' => $currentRevenue,
                ],
                'discount' => [
                    'price' => $discountPrice,
                    'estimated_occupancy' => $estimatedOccupancyIncrease,
                    'estimated_revenue' => $discountRevenue,
                    'revenue_change' => $discountRevenue - $currentRevenue,
                    'revenue_change_percentage' => (($discountRevenue - $currentRevenue) / $currentRevenue) * 100,
                ],
                'premium' => [
                    'price' => $premiumPrice,
                    'estimated_occupancy' => $estimatedOccupancyDecrease,
                    'estimated_revenue' => $premiumRevenue,
                    'revenue_change' => $premiumRevenue - $currentRevenue,
                    'revenue_change_percentage' => (($premiumRevenue - $currentRevenue) / $currentRevenue) * 100,
                ],
                'dynamic' => [
                    'price' => $optimized['optimal_price'],
                    'estimated_occupancy' => $booked,
                    'estimated_revenue' => $optimized['potential_revenue_at_optimal'],
                    'revenue_change' => $optimized['potential_additional_revenue'],
                    'revenue_change_percentage' => $booked > 0
                        ? (($optimized['potential_additional_revenue']) / $currentRevenue) * 100
                        : 0,
                ],
            ],
            'recommendation' => $this->getRevenueRecommendation(
                $currentRevenue,
                $discountRevenue,
                $premiumRevenue,
                $optimized['potential_revenue_at_optimal'] ?? $currentRevenue
            ),
        ];
    }

    /**
     * Get revenue optimization recommendations
     */
    public function getOptimizationRecommendations(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): array {
        return $this->algorithm->getOptimizationRecommendations(
            $companyId,
            $resourceType,
            $resourceId
        );
    }

    /**
     * Get revenue report
     */
    public function getRevenueReport(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $days = 30
    ): array {
        $analytics = $this->getRevenueAnalytics($companyId, $resourceType, $resourceId, $days);
        $forecast = $this->calculateRevenueForecast($companyId, $resourceType, $resourceId, $days);
        $recommendations = $this->getOptimizationRecommendations($companyId, $resourceType, $resourceId);

        return [
            'period_days' => $days,
            'analytics' => $analytics,
            'forecast' => $forecast,
            'recommendations' => $recommendations,
            'report_generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate revenue recommendation
     */
    protected function getRevenueRecommendation(
        float $current,
        float $discount,
        float $premium,
        float $dynamic
    ): string {
        $strategies = [
            'current' => $current,
            'discount' => $discount,
            'premium' => $premium,
            'dynamic' => $dynamic,
        ];

        $best = array_keys($strategies, max($strategies))[0];

        return match ($best) {
            'discount' => 'Recommend discount strategy - volume increase outweighs lower price',
            'premium' => 'Recommend premium strategy - higher margins offset lower volume',
            'dynamic' => 'Recommend dynamic pricing - optimal balance of price and occupancy',
            default => 'Current pricing is optimal',
        };
    }
}
