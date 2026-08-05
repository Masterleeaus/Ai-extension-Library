<?php

namespace Tests\Unit\Domains\WorkCore\Pricing\Models;

use Tests\TestCase;
use App\Domains\WorkCore\Pricing\Models\PricingRule;
use App\Models\Company;
use App\Models\User;

class PricingRuleTest extends TestCase
{
    protected Company $company;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_pricing_rule_can_be_created(): void
    {
        $rule = PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Test Rule',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 15]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $this->assertNotNull($rule->id);
        $this->assertEquals('Test Rule', $rule->name);
        $this->assertTrue($rule->is_active);
    }

    public function test_pricing_rule_evaluation_with_matching_condition(): void
    {
        $rule = PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'High Occupancy Rule',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 20]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $context = ['occupancy' => 85];
        $this->assertTrue($rule->evaluate($context));
    }

    public function test_pricing_rule_evaluation_with_non_matching_condition(): void
    {
        $rule = PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'High Occupancy Rule',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 20]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $context = ['occupancy' => 60];
        $this->assertFalse($rule->evaluate($context));
    }

    public function test_pricing_rule_applies_percentage_adjustment(): void
    {
        $rule = PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Percentage Increase Rule',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 20]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $context = ['occupancy' => 85];
        $adjustedPrice = $rule->applyRule(100, $context);

        $this->assertEquals(120, $adjustedPrice);
    }

    public function test_pricing_rule_applies_fixed_adjustment(): void
    {
        $rule = PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Fixed Increase Rule',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'fixed', 'value' => 10]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $context = ['occupancy' => 85];
        $adjustedPrice = $rule->applyRule(100, $context);

        $this->assertEquals(110, $adjustedPrice);
    }

    public function test_get_matching_rules(): void
    {
        PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Rule 1',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 20]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Rule 2',
            'conditions' => json_encode([['field' => 'demand', 'operator' => '>', 'value' => 70]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 15]]),
            'priority' => 50,
            'is_active' => true,
            'rule_type' => 'demand',
            'created_by_user_id' => $this->user->id,
        ]);

        $context = ['occupancy' => 85, 'demand' => 75];
        $matchingRules = PricingRule::getMatchingRules($this->company->id, $context);

        $this->assertCount(2, $matchingRules);
    }
}
