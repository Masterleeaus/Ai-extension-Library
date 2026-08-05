<?php

namespace Tests\Feature\Domains\WorkCore\Pricing;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Domains\WorkCore\Pricing\Services\PricingService;
use App\Domains\WorkCore\Pricing\Models\PricingRule;
use App\Domains\WorkCore\Pricing\Models\DemandIndicator;
use App\Domains\WorkCore\Pricing\Models\OccupancyData;
use App\Domains\WorkCore\Pricing\Models\SeasonalRate;

class PriceCalculationTest extends TestCase
{
    protected User $user;

    protected Company $company;

    protected PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->pricingService = app(PricingService::class);
    }

    public function test_basic_price_calculation_with_rules(): void
    {
        PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'High Demand Rule',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 25]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $result = $this->pricingService->calculatePrice(
            $this->company->id,
            100.00,
            ['type' => 'property', 'id' => 1],
            ['occupancy' => 85]
        );

        $this->assertTrue($result['successful']);
        $this->assertEquals(125.00, $result['final_price']);
    }

    public function test_occupancy_based_pricing(): void
    {
        OccupancyData::recordOccupancy(
            $this->company->id,
            'property',
            1,
            90,
            100,
            10,
            5
        );

        DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 5,
            'search_count' => 20,
            'inquiry_count' => 10,
            'demand_score' => 60,
            'demand_level' => 'high',
            'recorded_at' => now(),
        ]);

        $result = $this->pricingService->calculatePrice(
            $this->company->id,
            100.00,
            ['type' => 'property', 'id' => 1]
        );

        $this->assertTrue($result['successful']);
        $this->assertGreaterThan(100, $result['final_price']);
    }

    public function test_seasonal_pricing(): void
    {
        SeasonalRate::create([
            'company_id' => $this->company->id,
            'season_name' => 'Summer Peak',
            'description' => 'Peak summer season',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'multiplier' => 1.5,
            'is_active' => true,
            'season_type' => 'peak',
            'created_by_user_id' => $this->user->id,
        ]);

        DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 5,
            'demand_score' => 50,
            'demand_level' => 'normal',
            'recorded_at' => now(),
        ]);

        $result = $this->pricingService->calculatePrice(
            $this->company->id,
            100.00,
            ['type' => 'property', 'id' => 1]
        );

        $this->assertTrue($result['successful']);
        // Should have seasonal multiplier applied
        $this->assertGreaterThan(100, $result['final_price']);
    }

    public function test_multiple_rules_with_priority(): void
    {
        // Rule 1: Low priority (runs first)
        PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Base Premium',
            'conditions' => json_encode([]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 10]]),
            'priority' => 50,
            'is_active' => true,
            'rule_type' => 'custom',
            'created_by_user_id' => $this->user->id,
        ]);

        // Rule 2: High priority (runs second)
        PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Occupancy Surge',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 20]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $result = $this->pricingService->calculatePrice(
            $this->company->id,
            100.00,
            ['type' => 'property', 'id' => 1],
            ['occupancy' => 85]
        );

        $this->assertTrue($result['successful']);
        // Price should reflect multiple rule applications
        $this->assertGreaterThan(100, $result['final_price']);
    }

    public function test_price_history_tracking(): void
    {
        DemandIndicator::create([
            'company_id' => $this->company->id,
            'resource_type' => 'property',
            'resource_id' => 1,
            'booking_count' => 8,
            'demand_score' => 75,
            'demand_level' => 'high',
            'recorded_at' => now(),
        ]);

        $result = $this->pricingService->calculatePrice(
            $this->company->id,
            100.00,
            ['type' => 'property', 'id' => 1]
        );

        $history = $this->pricingService->getPriceHistory(
            $this->company->id,
            'property',
            1,
            1
        );

        $this->assertNotEmpty($history);
    }
}
