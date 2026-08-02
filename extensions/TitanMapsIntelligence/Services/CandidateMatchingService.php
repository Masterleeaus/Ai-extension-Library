<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Services;

use App\Extensions\TitanMapsIntelligence\DTO\MatchResult;

final class CandidateMatchingService
{
    public function __construct(private readonly PlaceCanonicalizer $canonicalizer = new PlaceCanonicalizer()) {}

    public function score(array $candidate, array $existing): MatchResult
    {
        $breakdown = [];
        $matching = [];
        $conflicts = [];

        $this->compareExact($breakdown, $matching, $conflicts, 'provider_place_id',
            ($candidate['provider'] ?? '').':'.($candidate['provider_place_id'] ?? ''),
            ($existing['external_provider'] ?? '').':'.($existing['external_place_id'] ?? ''), 0.55);
        $this->compareExact($breakdown, $matching, $conflicts, 'phone',
            $this->canonicalizer->normalizePhone($candidate['phone'] ?? null),
            $this->canonicalizer->normalizePhone($existing['phone'] ?? null), 0.15);
        $this->compareExact($breakdown, $matching, $conflicts, 'domain',
            $this->canonicalizer->normalizeDomain($candidate['website'] ?? null),
            $this->canonicalizer->normalizeDomain($existing['website'] ?? null), 0.12);
        $this->compareExact($breakdown, $matching, $conflicts, 'email',
            $this->canonicalizer->normalizeEmail($candidate['email'] ?? $candidate['public_email'] ?? null),
            $this->canonicalizer->normalizeEmail($existing['email'] ?? $existing['public_email'] ?? null), 0.08);

        $addressA = $this->canonicalizer->normalizeText($candidate['address'] ?? '');
        $addressB = $this->canonicalizer->normalizeText($existing['address'] ?? '');
        $addressSimilarity = $this->similarity($addressA, $addressB);
        $breakdown['address'] = 0.04 * $addressSimilarity;
        if ($addressSimilarity >= 0.8) { $matching[] = 'address'; }

        $distance = $this->distanceKm($candidate, $existing);
        $proximity = $distance === null ? 0.0 : max(0.0, 1.0 - min($distance, 5.0) / 5.0);
        $breakdown['proximity'] = 0.02 * $proximity;
        if ($proximity >= 0.8) { $matching[] = 'proximity'; }

        $nameSimilarity = $this->similarity($this->canonicalizer->normalizeText($candidate['name'] ?? ''), $this->canonicalizer->normalizeText($existing['name'] ?? ''));
        $breakdown['name'] = 0.02 * $nameSimilarity;
        if ($nameSimilarity >= 0.8) { $matching[] = 'name'; }

        $categoriesA = array_map('strtolower', (array) ($candidate['categories'] ?? []));
        $categoriesB = array_map('strtolower', (array) ($existing['categories'] ?? []));
        $categoryMatch = count(array_intersect($categoriesA, $categoriesB)) > 0 ? 1.0 : 0.0;
        $breakdown['category'] = 0.02 * $categoryMatch;
        if ($categoryMatch === 1.0) { $matching[] = 'category'; }

        $score = min(1.0, array_sum($breakdown));
        $status = $score >= 0.86 ? 'confirmed' : ($score >= 0.62 ? 'ambiguous' : 'no_match');
        return new MatchResult(round($score, 6), $status, array_values(array_unique($matching)), array_values(array_unique($conflicts)), $breakdown);
    }

    private function compareExact(array &$breakdown, array &$matching, array &$conflicts, string $field, string $a, string $b, float $weight): void
    {
        if ($a === '' || $b === '' || str_ends_with($a, ':') || str_ends_with($b, ':')) {
            $breakdown[$field] = 0.0;
            return;
        }
        if (hash_equals($a, $b)) {
            $breakdown[$field] = $weight;
            $matching[] = $field;
        } else {
            $breakdown[$field] = 0.0;
            $conflicts[] = $field;
        }
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') { return 0.0; }
        similar_text($a, $b, $percent);
        return $percent / 100;
    }

    private function distanceKm(array $a, array $b): ?float
    {
        foreach (['latitude', 'longitude'] as $field) {
            if (! isset($a[$field], $b[$field])) { return null; }
        }
        $earth = 6371.0;
        $lat1 = deg2rad((float) $a['latitude']);
        $lat2 = deg2rad((float) $b['latitude']);
        $dLat = $lat2 - $lat1;
        $dLon = deg2rad((float) $b['longitude'] - (float) $a['longitude']);
        $h = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
        return $earth * 2 * atan2(sqrt($h), sqrt(1 - $h));
    }
}
