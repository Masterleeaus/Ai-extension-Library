<?php

namespace Tests\Feature\Domains\WorkCore\Pricing;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Domains\WorkCore\Pricing\Models\PricingRule;

class PricingRuleApiTest extends TestCase
{
    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->update(['active_company_id' => $this->company->id]);
    }

    public function test_get_pricing_rules(): void
    {
        PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Test Rule',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 15]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/workcore/pricing/rules');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('count', 1);
    }

    public function test_create_pricing_rule(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/workcore/pricing/rules', [
                'name' => 'New Pricing Rule',
                'description' => 'Test description',
                'rule_type' => 'demand',
                'priority' => 100,
                'is_active' => true,
                'conditions' => json_encode([['field' => 'demand', 'operator' => '>', 'value' => 70]]),
                'adjustments' => json_encode([['type' => 'percentage', 'value' => 20]]),
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'New Pricing Rule');

        $this->assertDatabaseHas('workcore_pricing_rules', [
            'name' => 'New Pricing Rule',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_update_pricing_rule(): void
    {
        $rule = PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 15]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/workcore/pricing/rules/{$rule->id}", [
                'name' => 'Updated Name',
                'priority' => 50,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('workcore_pricing_rules', [
            'id' => $rule->id,
            'name' => 'Updated Name',
            'priority' => 50,
        ]);
    }

    public function test_delete_pricing_rule(): void
    {
        $rule = PricingRule::create([
            'company_id' => $this->company->id,
            'name' => 'Rule to Delete',
            'conditions' => json_encode([['field' => 'occupancy', 'operator' => '>', 'value' => 80]]),
            'adjustments' => json_encode([['type' => 'percentage', 'value' => 15]]),
            'priority' => 100,
            'is_active' => true,
            'rule_type' => 'occupancy',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/workcore/pricing/rules/{$rule->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertSoftDeleted('workcore_pricing_rules', [
            'id' => $rule->id,
        ]);
    }
}
