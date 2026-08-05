<?php

namespace App\Domains\WorkCore\Pricing\Algorithms;

use App\Domains\WorkCore\Pricing\Models\DemandIndicator;
use App\Domains\WorkCore\Pricing\Models\PriceHistory;

class DemandPricingAlgorithm
{
    /**
     * Calculate price adjustment based on demand indicators
     */
    public function calculate(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $basePrice
    ): array {
        $demand = DemandIndicator::getLatest($companyId, $resourceType, $resourceId);

        if (! $demand) {
            return [
                'adjusted_price' => $basePrice,
                'demand_factor' => 1.0,
                'demand_level' => 'normal',
                'adjustment_amount' => 0,
                'adjustment_percentage' => 0,
            ];
        }

        $demandFactor = $this->getDemandFactor($demand);
        $adjustedPrice = $basePrice * $demandFactor;
        $adjustmentPercentage = (($adjustedPrice - $basePrice) / $basePrice) * 100;

        return [
            'adjusted_price' => $adjustedPrice,
            'demand_factor' => $demandFactor,
            'demand_level' => $demand->demand_level,
            'demand_score' => $demand->demand_score,
            'adjustment_amount' => $adjustedPrice - $basePrice,
            'adjustment_percentage' => $adjustmentPercentage,
            'factors' => $demand->factors,
        ];
    }

    /**
     * Get demand-based price factor (multiplier)
     */
    public function getDemandFactor(DemandIndicator $demand): float
    {
        $score = $demand->demand_score ?? 0;

        // Map demand score (0-100) to price multiplier (0.8-1.5)
        // Low demand: 0.8x (20% discount)
        // Normal demand: 1.0x (no change)
        // High demand: 1.2x (20% premium)
        // Critical demand: 1.5x (50% premium)

        return match (true) {
            $score < 25 => 0.8,
            $score < 50 => 0.9 + ($score - 25) / 25 * 0.1,
            $score < 75 => 1.0 + ($score - 50) / 25 * 0.2,
            default => 1.2 + ($score - 75) / 25 * 0.3,
        };
    }

    /**
     * Calculate price elasticity
     */
    public function calculateElasticity(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): float {
        $history = PriceHistory::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('reason', 'demand')
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($history->count() < 2) {
            return 1.0;
        }

        $priceChanges = [];
        $demandChanges = [];

        for ($i = 0; $i < $history->count() - 1; $i++) {
            $current = $history[$i];
            $previous = $history[$i + 1];

            $priceChange = (($current->adjusted_price - $previous->adjusted_price) / $previous->adjusted_price) * 100;
            $priceChanges[] = $priceChange;

            // Estimated demand change from booking metrics
            $demandChange = 0;
            if (isset($current->factors['booking']) && isset($previous->factors['booking'])) {
                $demandChange = (($current->factors['booking'] - $previous->factors['booking']) / $previous->factors['booking']) * 100;
            }
            $demandChanges[] = $demandChange;
        }

        if (empty($demandChanges) || abs(array_sum($demandChanges) / count($demandChanges)) < 0.01) {
            return 1.0;
        }

        $avgPriceChange = array_sum($priceChanges) / count($priceChanges);
        $avgDemandChange = array_sum($demandChanges) / count($demandChanges);

        return abs($avgDemandChange) > 0 ? abs($avgPriceChange / $avgDemandChange) : 1.0;
    }

    /**
     * Get insights about demand patterns
     */
    public function getInsights(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): array {
        $trend = DemandIndicator::getTrend($companyId, $resourceType, $resourceId, 7);

        if (empty($trend)) {
            return [
                'pattern' => 'insufficient_data',
                'trend' => 'stable',
                'recommendation' => 'Insufficient data for recommendations',
            ];
        }

        $scores = array_map(fn ($item) => $item['score'], $trend);
        $avgScore = array_sum($scores) / count($scores);
        $trend_direction = $scores[array_key_last($scores)] > $scores[0] ? 'increasing' : 'decreasing';

        return [
            'pattern' => $this->identifyPattern($scores),
            'trend' => $trend_direction,
            'average_demand_score' => $avgScore,
            'recommendation' => $this->generateRecommendation($avgScore, $trend_direction),
            'days_analyzed' => count($trend),
        ];
    }

    /**
     * Identify demand pattern from scores
     */
    private function identifyPattern(array $scores): string
    {
        $high = count(array_filter($scores, fn ($s) => $s >= 75));
        $low = count(array_filter($scores, fn ($s) => $s < 25));
        $total = count($scores);

        if ($high / $total > 0.5) {
            return 'consistently_high';
        }
        if ($low / $total > 0.5) {
            return 'consistently_low';
        }

        return 'variable';
    }

    /**
     * Generate pricing recommendation
     */
    private function generateRecommendation(float $avgScore, string $trend): string
    {
        if ($avgScore >= 75 && $trend === 'increasing') {
            return 'Increase prices - high and rising demand';
        }
        if ($avgScore >= 75 && $trend === 'decreasing') {
            return 'Maintain high prices - strong demand remains';
        }
        if ($avgScore < 25 && $trend === 'decreasing') {
            return 'Discount prices - low and declining demand';
        }
        if ($avgScore < 25 && $trend === 'increasing') {
            return 'Maintain low prices - low demand may be recovering';
        }

        return 'Maintain current pricing - balanced demand';
    }
}
