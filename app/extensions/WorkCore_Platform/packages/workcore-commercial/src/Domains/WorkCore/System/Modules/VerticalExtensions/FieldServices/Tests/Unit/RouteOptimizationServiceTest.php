<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Tests\Unit;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Services\RouteOptimizationService;
use PHPUnit\Framework\TestCase;

class RouteOptimizationServiceTest extends TestCase
{
    private RouteOptimizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RouteOptimizationService();
    }

    public function test_calculate_distance_between_coordinates()
    {
        // Sydney to Melbourne (approximately 714 km)
        $distance = $this->service->calculateDistance(-33.8688, 151.2093, -37.8136, 144.9631);

        // Allow for some variance in calculation
        $this->assertGreaterThan(700, $distance);
        $this->assertLessThan(730, $distance);
    }

    public function test_calculate_distance_same_location()
    {
        $distance = $this->service->calculateDistance(-33.8688, 151.2093, -33.8688, 151.2093);

        $this->assertEquals(0, $distance);
    }

    public function test_nearest_neighbor_with_single_location()
    {
        $locations = [
            [
                'id' => 1,
                'latitude' => -33.8688,
                'longitude' => 151.2093,
                'address' => 'Sydney',
            ],
        ];

        $route = $this->service->optimizeRoute([1]);

        $this->assertNotEmpty($route);
    }
}
