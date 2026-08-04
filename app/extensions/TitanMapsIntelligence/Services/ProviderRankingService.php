<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Services;

use App\Extensions\TitanMapsIntelligence\DTO\RankedCandidate;

final class ProviderRankingService
{
    private const WEIGHTS = [
        'distance' => 0.30,
        'category' => 0.20,
        'rating' => 0.15,
        'review_count' => 0.10,
        'open_now' => 0.10,
        'contactability' => 0.10,
        'freshness' => 0.05,
    ];

    public function rank(array $candidate): RankedCandidate
    {
        $distanceKm = max(0.0, (float) ($candidate['distance_km'] ?? 50.0));
        $distance = max(0.0, 1.0 - min($distanceKm, 25.0) / 25.0);
        $category = max(0.0, min(1.0, (float) ($candidate['category_match'] ?? 0.0)));
        $rating = max(0.0, min(1.0, ((float) ($candidate['rating'] ?? 0.0)) / 5.0));
        $reviewCount = min(1.0, log10(max(1, (int) ($candidate['review_count'] ?? 0)) + 1) / 3.0);
        $openNow = ($candidate['open_now'] ?? false) === true ? 1.0 : 0.0;
        $contactability = ((string) ($candidate['phone'] ?? '') !== '' ? 0.5 : 0.0) + ((string) ($candidate['website'] ?? '') !== '' ? 0.5 : 0.0);
        $freshness = max(0.0, min(1.0, (float) ($candidate['data_freshness'] ?? 0.0)));

        $signals = compact('distance', 'category', 'rating', 'reviewCount', 'openNow', 'contactability', 'freshness');
        $map = [
            'distance' => $signals['distance'],
            'category' => $signals['category'],
            'rating' => $signals['rating'],
            'review_count' => $signals['reviewCount'],
            'open_now' => $signals['openNow'],
            'contactability' => $signals['contactability'],
            'freshness' => $signals['freshness'],
        ];
        $breakdown = [];
        foreach (self::WEIGHTS as $name => $weight) {
            $breakdown[$name] = round($map[$name] * $weight, 8);
        }
        return new RankedCandidate(round(array_sum($breakdown), 8), $breakdown);
    }
}
