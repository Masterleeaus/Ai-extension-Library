<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Services;

use App\Extensions\TitanMapsIntelligence\DTO\ProviderUsage;
use App\Extensions\TitanMapsIntelligence\Exceptions\MapsIntelligenceException;

final class ProviderUsageService
{
    /** @param list<ProviderUsage> $records */
    public function summarise(array $records): array
    {
        if ($records === []) {
            return ['request_count' => 0, 'result_count' => 0, 'billable_units' => 0.0, 'estimated_cost' => 0.0, 'currency' => null];
        }

        $currency = $records[0]->currency;
        $summary = ['request_count' => 0, 'result_count' => 0, 'billable_units' => 0.0, 'estimated_cost' => 0.0, 'currency' => $currency];
        foreach ($records as $record) {
            if ($record->currency !== $currency) {
                throw MapsIntelligenceException::fromCode('MAPS_USAGE_CURRENCY_MISMATCH', 'Provider usage records must share one currency before aggregation.');
            }
            $summary['request_count'] += $record->requestCount;
            $summary['result_count'] += $record->resultCount;
            $summary['billable_units'] += $record->billableUnits;
            $summary['estimated_cost'] += $record->estimatedCost;
        }

        return $summary;
    }
}
