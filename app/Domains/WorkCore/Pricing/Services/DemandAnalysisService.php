<?php

namespace App\Domains\WorkCore\Pricing\Services;

use App\Domains\WorkCore\Pricing\Models\DemandIndicator;
use App\Domains\WorkCore\Pricing\Algorithms\DemandPricingAlgorithm;

class DemandAnalysisService
{
    protected DemandPricingAlgorithm $algorithm;

    public function __construct(DemandPricingAlgorithm $algorithm = null)
    {
        $this->algorithm = $algorithm ?? new DemandPricingAlgorithm();
    }

    /**
     * Analyze demand for a resource
     */
    public function analyzeDemand(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $days = 30
    ): array {
        $trend = DemandIndicator::getTrend($companyId, $resourceType, $resourceId, $days);

        if (empty($trend)) {
            return [
                'available' => false,
                'reason' => 'No demand data available',
            ];
        }

        $scores = array_map(fn ($item) => $item['score'], $trend);
        $bookings = array_map(fn ($item) => $item['bookings'], $trend);

        $avgScore = array_sum($scores) / count($scores);
        $avgBookings = array_sum($bookings) / count($bookings);
        $maxScore = max($scores);
        $minScore = min($scores);

        return [
            'available' => true,
            'period_days' => $days,
            'average_demand_score' => $avgScore,
            'max_demand_score' => $maxScore,
            'min_demand_score' => $minScore,
            'demand_volatility' => $this->calculateVolatility($scores),
            'average_bookings' => $avgBookings,
            'total_bookings' => array_sum($bookings),
            'booking_trend' => $this->calculateTrend($bookings),
            'demand_level' => $this->getDemandLevel($avgScore),
            'trend_details' => $trend,
        ];
    }

    /**
     * Calculate elasticity for price optimization
     */
    public function calculateElasticity(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): array {
        $elasticity = $this->algorithm->calculateElasticity(
            $companyId,
            $resourceType,
            $resourceId
        );

        return [
            'price_elasticity' => $elasticity,
            'elasticity_type' => $this->classifyElasticity($elasticity),
            'interpretation' => $this->getElasticityInterpretation($elasticity),
        ];
    }

    /**
     * Predict future demand
     */
    public function predictDemand(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $days = 7
    ): array {
        $trend = DemandIndicator::getTrend($companyId, $resourceType, $resourceId, 30);

        if (count($trend) < 5) {
            return [
                'prediction_available' => false,
                'reason' => 'Insufficient historical data',
            ];
        }

        $scores = array_map(fn ($item) => $item['score'], $trend);
        $lastScore = end($scores);

        // Simple moving average prediction
        $movingAvg = array_sum(array_slice($scores, -7)) / min(7, count($scores));
        $trend_direction = $scores[count($scores) - 1] > $scores[0] ? 'increasing' : 'decreasing';

        // Predict next 7 days
        $predictions = [];
        $currentScore = $movingAvg;

        for ($i = 1; $i <= $days; $i++) {
            // Apply trend modifier
            $modifier = $trend_direction === 'increasing' ? 1.02 : 0.98;
            $currentScore = min(100, max(0, $currentScore * $modifier));
            $predictions[] = [
                'day' => $i,
                'predicted_score' => round($currentScore, 2),
                'expected_level' => $this->getDemandLevel($currentScore),
            ];
        }

        return [
            'prediction_available' => true,
            'based_on_days' => count($trend),
            'current_score' => $lastScore,
            'trend' => $trend_direction,
            'predictions' => $predictions,
            'confidence' => $this->getConfidenceLevel(count($trend)),
        ];
    }

    /**
     * Get demand metrics
     */
    public function getDemandMetrics(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): array {
        $current = DemandIndicator::getLatest($companyId, $resourceType, $resourceId);

        if (! $current) {
            return [
                'available' => false,
                'reason' => 'No current demand data',
            ];
        }

        return [
            'available' => true,
            'recorded_at' => $current->recorded_at,
            'booking_count' => $current->booking_count,
            'search_count' => $current->search_count,
            'inquiry_count' => $current->inquiry_count,
            'cancellation_rate' => $current->cancellation_rate,
            'booking_velocity' => $current->booking_velocity,
            'demand_score' => $current->demand_score,
            'demand_level' => $current->demand_level,
            'factors' => $current->factors,
        ];
    }

    /**
     * Get demand insights and recommendations
     */
    public function getInsights(
        string $companyId,
        string $resourceType,
        int $resourceId
    ): array {
        return $this->algorithm->getInsights($companyId, $resourceType, $resourceId);
    }

    /**
     * Calculate volatility of demand
     */
    protected function calculateVolatility(array $scores): float
    {
        if (count($scores) < 2) {
            return 0;
        }

        $mean = array_sum($scores) / count($scores);
        $variance = 0;

        foreach ($scores as $score) {
            $variance += pow($score - $mean, 2);
        }

        $variance /= count($scores);

        return sqrt($variance);
    }

    /**
     * Calculate trend direction and strength
     */
    protected function calculateTrend(array $values): string
    {
        if (count($values) < 2) {
            return 'insufficient_data';
        }

        $firstHalf = array_sum(array_slice($values, 0, (int)(count($values) / 2))) /
                     (int)(count($values) / 2);
        $secondHalf = array_sum(array_slice($values, (int)(count($values) / 2))) /
                      (int)(count($values) / 2);

        $percentChange = (($secondHalf - $firstHalf) / $firstHalf) * 100;

        return match (true) {
            $percentChange > 15 => 'strongly_increasing',
            $percentChange > 5 => 'increasing',
            $percentChange > -5 => 'stable',
            $percentChange > -15 => 'decreasing',
            default => 'strongly_decreasing',
        };
    }

    /**
     * Get demand level classification
     */
    protected function getDemandLevel(float $score): string
    {
        return match (true) {
            $score < 25 => 'low',
            $score < 50 => 'normal',
            $score < 75 => 'high',
            default => 'critical',
        };
    }

    /**
     * Classify elasticity
     */
    protected function classifyElasticity(float $elasticity): string
    {
        return match (true) {
            $elasticity < 0.5 => 'inelastic',
            $elasticity < 1.0 => 'somewhat_inelastic',
            $elasticity < 1.5 => 'unit_elastic',
            default => 'elastic',
        };
    }

    /**
     * Get elasticity interpretation
     */
    protected function getElasticityInterpretation(float $elasticity): string
    {
        return match (true) {
            $elasticity < 0.5 => 'Price changes have minimal impact on demand. Can increase prices without significant loss.',
            $elasticity < 1.0 => 'Demand is relatively price-insensitive. Small price increases should be acceptable.',
            $elasticity < 1.5 => 'Balanced elasticity. Price and demand move proportionally.',
            default => 'Demand is very price-sensitive. Price increases may significantly reduce bookings.',
        };
    }

    /**
     * Get confidence level for predictions
     */
    protected function getConfidenceLevel(int $dataPoints): string
    {
        return match (true) {
            $dataPoints < 10 => 'low',
            $dataPoints < 20 => 'medium',
            default => 'high',
        };
    }
}
