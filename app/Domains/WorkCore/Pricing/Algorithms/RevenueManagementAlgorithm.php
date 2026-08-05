<?php

namespace App\Domains\WorkCore\Pricing\Algorithms;

use App\Domains\WorkCore\Pricing\Models\OccupancyData;
use App\Domains\WorkCore\Pricing\Models\DemandIndicator;
use App\Domains\WorkCore\Pricing\Models\PriceHistory;

class RevenueManagementAlgorithm
{
    /**
     * Optimize price based on occupancy and demand
     */
    public function optimize(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): array {
        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);
        $demand = DemandIndicator::getLatest($companyId, $resourceType, $resourceId);

        if (! $occupancy || ! $demand) {
            return [
                'optimal_price' => $basePrice,
                'expected_revenue' => 0,
                'occupancy_factor' => 1.0,
                'demand_factor' => 1.0,
            ];
        }

        $occupancyFactor = $this->calculateOccupancyFactor($occupancy);
        $demandFactor = $this->calculateDemandFactor($demand);
        $optimalPrice = $basePrice * $occupancyFactor * $demandFactor;

        return [
            'optimal_price' => $optimalPrice,
            'base_price' => $basePrice,
            'occupancy_factor' => $occupancyFactor,
            'demand_factor' => $demandFactor,
            'expected_revenue' => $this->calculateExpectedRevenue($occupancy, $optimalPrice),
            'occupancy_percentage' => $occupancy->occupancy_percentage,
            'demand_score' => $demand->demand_score,
        ];
    }

    /**
     * Calculate occupancy-based price factor
     */
    private function calculateOccancyFactor(OccupancyData $occupancy): float
    {
        $percentage = $occupancy->occupancy_percentage ?? 0;

        // Map occupancy to price multiplier
        // Low occupancy (< 30%): 0.8x (discount to fill)
        // Normal occupancy (30-70%): 1.0x
        // High occupancy (70-90%): 1.2x (premium)
        // Critical (90%+): 1.5x (maximize revenue)

        return match (true) {
            $percentage < 30 => 0.8,
            $percentage < 70 => 0.8 + ($percentage - 30) / 40 * 0.2,
            $percentage < 90 => 1.0 + ($percentage - 70) / 20 * 0.2,
            default => 1.2 + ($percentage - 90) / 10 * 0.3,
        };
    }

    /**
     * Calculate demand-based price factor
     */
    private function calculateDemandFactor(DemandIndicator $demand): float
    {
        $score = $demand->demand_score ?? 0;

        return match (true) {
            $score < 25 => 0.9,
            $score < 50 => 0.95,
            $score < 75 => 1.05,
            default => 1.15,
        };
    }

    /**
     * Calculate expected revenue
     */
    private function calculateExpectedRevenue(OccupancyData $occupancy, float $pricePerUnit): float
    {
        $bookedUnits = $occupancy->current_occupancy;

        return $bookedUnits * $pricePerUnit;
    }

    /**
     * Calculate optimal price to maximize revenue
     */
    public function calculateOptimalPrice(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): float {
        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);
        $demand = DemandIndicator::getLatest($companyId, $resourceType, $resourceId);

        if (! $occupancy || ! $demand) {
            return $basePrice;
        }

        // Use a simplified revenue optimization model
        // Revenue = Price * Occupancy * Demand
        // We want to maximize this by adjusting price

        $occupancyFactor = $this->calculateOccupancyFactor($occupancy);
        $demandFactor = $this->calculateDemandFactor($demand);

        return $basePrice * $occupancyFactor * $demandFactor;
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
        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);
        $demand = DemandIndicator::getLatest($companyId, $resourceType, $resourceId);

        if (! $occupancy || ! $demand) {
            return [
                'conservative' => $basePrice * 0.95,
                'moderate' => $basePrice,
                'aggressive' => $basePrice * 1.05,
            ];
        }

        $occupancyFactor = $this->calculateOccupancyFactor($occupancy);
        $demandFactor = $this->calculateDemandFactor($demand);

        return [
            'conservative' => $basePrice * max(0.85, $occupancyFactor - 0.1),
            'moderate' => $basePrice * $occupancyFactor,
            'aggressive' => $basePrice * $occupancyFactor * $demandFactor,
            'max_revenue' => $basePrice * $occupancyFactor * $demandFactor * 1.1,
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
        $historicalData = PriceHistory::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $occupancyTrend = OccupancyData::getTrend($companyId, $resourceType, $resourceId, $days);

        if (empty($historicalData) || empty($occupancyTrend)) {
            return [
                'forecast_available' => false,
                'reason' => 'Insufficient historical data',
            ];
        }

        $totalRevenue = $historicalData->sum(fn ($item) => $item->adjusted_price * ($item->factors['units_sold'] ?? 1));
        $avgDailyRevenue = $totalRevenue / count($occupancyTrend);

        // Calculate growth trend
        $recentRevenue = $historicalData->where('created_at', '>=', now()->subDays(7))->sum(fn ($item) => $item->adjusted_price);
        $olderRevenue = $historicalData->where('created_at', '<', now()->subDays(7))->sum(fn ($item) => $item->adjusted_price);

        $growthRate = $olderRevenue > 0 ? (($recentRevenue - $olderRevenue) / $olderRevenue) * 100 : 0;

        return [
            'forecast_available' => true,
            'historical_period_days' => $days,
            'total_historical_revenue' => $totalRevenue,
            'average_daily_revenue' => $avgDailyRevenue,
            'projected_30day_revenue' => $avgDailyRevenue * 30,
            'growth_rate_percentage' => $growthRate,
            'trend' => $growthRate > 5 ? 'increasing' : ($growthRate < -5 ? 'decreasing' : 'stable'),
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
        $occupancy = OccupancyData::getLatest($companyId, $resourceType, $resourceId);
        $demand = DemandIndicator::getLatest($companyId, $resourceType, $resourceId);

        $recommendations = [];

        if ($occupancy && $occupancy->occupancy_percentage < 30) {
            $recommendations[] = [
                'type' => 'occupancy_concern',
                'message' => 'Low occupancy detected. Consider discounting to increase bookings.',
                'priority' => 'high',
                'suggested_action' => 'Apply 15-20% discount',
            ];
        }

        if ($demand && $demand->demand_score >= 75 && $occupancy && $occupancy->occupancy_percentage > 80) {
            $recommendations[] = [
                'type' => 'revenue_maximization',
                'message' => 'High demand and high occupancy. Opportunity to increase prices.',
                'priority' => 'high',
                'suggested_action' => 'Increase prices by 20-30%',
            ];
        }

        if ($demand && $demand->cancellation_rate > 30) {
            $recommendations[] = [
                'type' => 'quality_concern',
                'message' => 'High cancellation rate detected. Review service quality and policies.',
                'priority' => 'medium',
            ];
        }

        if (! $recommendations) {
            $recommendations[] = [
                'type' => 'status_ok',
                'message' => 'Current pricing and occupancy are balanced. Monitor metrics.',
                'priority' => 'low',
            ];
        }

        return $recommendations;
    }
}
