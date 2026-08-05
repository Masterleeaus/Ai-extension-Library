<?php

namespace Tests\Unit\Domains\WorkCore\Pricing\Models;

use Tests\TestCase;
use App\Domains\WorkCore\Pricing\Models\DemandIndicator;
use App\Models\Company;

class DemandIndicatorTest extends TestCase
{
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    public function test_demand_indicator_can_be_created(): void
    {
        $indicator = DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 5,
            'search_count' => 20,
            'inquiry_count' => 10,
            'cancellation_rate' => 5.0,
            'booking_velocity' => 1.5,
            'demand_score' => 60,
            'demand_level' => 'high',
            'recorded_at' => now(),
        ]);

        $this->assertNotNull($indicator->id);
        $this->assertEquals('high', $indicator->demand_level);
    }

    public function test_record_booking_increments_count(): void
    {
        $indicator = DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 0,
            'search_count' => 0,
            'inquiry_count' => 0,
            'recorded_at' => now(),
        ]);

        $indicator->recordBooking();

        $this->assertEquals(1, $indicator->booking_count);
    }

    public function test_calculate_demand_score(): void
    {
        $indicator = DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 10,
            'search_count' => 20,
            'inquiry_count' => 15,
            'cancellation_rate' => 0,
            'booking_velocity' => 2,
            'recorded_at' => now(),
        ]);

        $indicator->calculateDemandScore();

        $this->assertGreaterThan(0, $indicator->demand_score);
        $this->assertLessOrEqual(100, $indicator->demand_score);
    }

    public function test_get_latest_demand_indicator(): void
    {
        DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 5,
            'demand_score' => 50,
            'demand_level' => 'normal',
            'recorded_at' => now()->subHour(),
        ]);

        DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 8,
            'demand_score' => 70,
            'demand_level' => 'high',
            'recorded_at' => now(),
        ]);

        $latest = DemandIndicator::getLatest(
            $this->company->id,
            'property',
            1
        );

        $this->assertEquals(8, $latest->booking_count);
        $this->assertEquals(70, $latest->demand_score);
    }

    public function test_get_demand_trend(): void
    {
        for ($i = 0; $i < 5; $i++) {
            DemandIndicator::create([
                'company_id' => $this->company->id,
                'resource_type' => 'property',
                'resource_id' => 1,
                'booking_count' => 5 + $i,
                'demand_score' => 50 + ($i * 5),
                'demand_level' => 'normal',
                'recorded_at' => now()->subDays(4 - $i),
            ]);
        }

        $trend = DemandIndicator::getTrend(
            $this->company->id,
            'property',
            1,
            7
        );

        $this->assertEquals(5, count($trend));
        $this->assertEquals(50, $trend[0]['score']);
        $this->assertEquals(70, $trend[4]['score']);
    }

    public function test_high_demand_scope(): void
    {
        DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 5,
            'demand_score' => 30,
            'demand_level' => 'low',
            'recorded_at' => now(),
        ]);

        DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 2,
            'booking_count' => 10,
            'demand_score' => 80,
            'demand_level' => 'high',
            'recorded_at' => now(),
        ]);

        $highDemand = DemandIndicator::highDemand()->get();

        $this->assertCount(1, $highDemand);
        $this->assertEquals(80, $highDemand[0]->demand_score);
    }
}
