<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Services;

final class RevenueOptimizationService
{
    /** @param array<string,mixed> $analytics @return array<string,mixed> */
    public function recommendation(array $analytics): array
    {
        $averageChange = (float) ($analytics['average_price_change_percent'] ?? 0);
        $marketIndex = (float) ($analytics['market_price_index'] ?? 1);
        $impact = (int) ($analytics['projected_revenue_impact_minor'] ?? 0);
        $direction = match (true) {
            $marketIndex > 1.10 && $averageChange < 5 => 'consider_increase',
            $marketIndex < 0.90 && $averageChange > 0 => 'review_competitiveness',
            $impact < 0 => 'review_negative_impact',
            default => 'hold',
        };
        return [
            'direction' => $direction,
            'explainable' => true,
            'source_metrics' => [
                'average_price_change_percent' => $averageChange,
                'market_price_index' => $marketIndex,
                'projected_revenue_impact_minor' => $impact,
            ],
        ];
    }
}
