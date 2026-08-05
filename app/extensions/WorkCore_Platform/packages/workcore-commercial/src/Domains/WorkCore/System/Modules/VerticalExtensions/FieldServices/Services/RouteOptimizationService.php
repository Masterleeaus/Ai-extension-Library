<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Services;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\JobSite;
use Illuminate\Support\Collection;

class RouteOptimizationService
{
    /**
     * Calculate optimal route for multiple job sites
     */
    public function optimizeRoute(array $jobSiteIds, ?array $startingLocation = null): array
    {
        $jobSites = JobSite::whereIn('id', $jobSiteIds)
            ->where('site_active', true)
            ->get();

        if ($jobSites->isEmpty()) {
            return [];
        }

        $locations = $jobSites->map(function (JobSite $site) {
            return [
                'id' => $site->id,
                'latitude' => $site->latitude,
                'longitude' => $site->longitude,
                'address' => $site->address,
            ];
        })->toArray();

        return $this->nearestNeighbor($locations, $startingLocation);
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }

    /**
     * Nearest neighbor algorithm for route optimization
     */
    private function nearestNeighbor(array $locations, ?array $startingLocation = null): array
    {
        if (empty($locations)) {
            return [];
        }

        $route = [];
        $visited = [];

        if ($startingLocation === null && ! empty($locations)) {
            $current = $locations[0];
            $route[] = $current;
            $visited[] = $current['id'];
        } else {
            $current = $startingLocation;
        }

        while (count($visited) < count($locations)) {
            $nearestDistance = PHP_FLOAT_MAX;
            $nearest = null;
            $nearestIndex = -1;

            foreach ($locations as $index => $location) {
                if (in_array($location['id'], $visited, true)) {
                    continue;
                }

                $distance = $this->calculateDistance(
                    $current['latitude'] ?? 0,
                    $current['longitude'] ?? 0,
                    $location['latitude'],
                    $location['longitude']
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearest = $location;
                    $nearestIndex = $index;
                }
            }

            if ($nearest !== null) {
                $route[] = $nearest;
                $visited[] = $nearest['id'];
                $current = $nearest;
            } else {
                break;
            }
        }

        return $route;
    }
}
