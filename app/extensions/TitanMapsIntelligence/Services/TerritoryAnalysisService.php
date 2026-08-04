<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Services;

final class TerritoryAnalysisService
{
    public function analyse(array $places): array
    {
        $categories = [];
        $bands = ['0-5km' => 0, '5-15km' => 0, '15km+' => 0];
        $sources = [];
        $confidence = 0.0;

        foreach ($places as $place) {
            $category = (string) ($place['primary_category'] ?? 'unclassified');
            $categories[$category] = ($categories[$category] ?? 0) + 1;
            $distance = max(0.0, (float) ($place['distance_km'] ?? 0.0));
            if ($distance <= 5) { $bands['0-5km']++; }
            elseif ($distance <= 15) { $bands['5-15km']++; }
            else { $bands['15km+']++; }
            $provider = (string) ($place['provider'] ?? 'unknown');
            $sources[$provider] = ($sources[$provider] ?? 0) + 1;
            $confidence += max(0.0, min(1.0, (float) ($place['confidence'] ?? 0.0)));
        }
        ksort($categories);
        ksort($sources);
        $count = count($places);
        return [
            'total_places' => $count,
            'categories' => $categories,
            'distance_bands' => $bands,
            'source_coverage' => $sources,
            'average_confidence' => $count === 0 ? 0.0 : $confidence / $count,
            'interpretation' => null,
        ];
    }
}
