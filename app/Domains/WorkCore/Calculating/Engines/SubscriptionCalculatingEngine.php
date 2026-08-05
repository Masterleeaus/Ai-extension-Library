<?php

namespace App\Domains\WorkCore\Calculating\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;

class SubscriptionCalculatingEngine implements CalculatingEngineContract
{
    protected bool $enabled = true;

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function getEngineId(): string
    {
        return 'subscription_engine';
    }

    public function getEngineName(): string
    {
        return 'Subscription Plan & Billing Engine';
    }

    public function getEngineVersion(): string
    {
        return '1.0.0';
    }

    public function getEngineType(): string
    {
        return 'subscription';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && config('calculating-engines.engines.subscription_engine.enabled', false);
    }

    public function shouldApply(PricingContextContract $context): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $subscriptionActive = $context->get('subscription_active', false);
        $subscriptionPlan = $context->get('subscription_plan');

        return $subscriptionActive && !empty($subscriptionPlan);
    }

    public function calculate(PricingContextContract $context): CalculatingEngineResult
    {
        try {
            $basePrice = $context->getCurrentPrice();
            $userId = $context->get('user_id');
            $subscriptionPlan = $context->get('subscription_plan');
            $billingCycle = $context->get('billing_cycle', 'monthly');

            // Calculate subscription rate
            $subscriptionDiscount = $this->calculateSubscriptionDiscount(
                $basePrice,
                $subscriptionPlan,
                $billingCycle
            );

            $adjustedPrice = max(0, $basePrice - $subscriptionDiscount);

            return CalculatingEngineResult::success(
                $this->getEngineId(),
                $basePrice,
                $adjustedPrice,
                sprintf('Subscription rate applied: $%.2f discount', $subscriptionDiscount),
                [
                    'subscription_discount' => $subscriptionDiscount,
                    'subscription_plan' => $subscriptionPlan,
                    'billing_cycle' => $billingCycle,
                ],
                [
                    'plan_id' => null,
                    'cycle_savings' => null,
                ]
            );
        } catch (\Exception $e) {
            return CalculatingEngineResult::failure(
                $this->getEngineId(),
                $context->getCurrentPrice(),
                "Subscription calculation failed: {$e->getMessage()}",
                ['error' => $e->getMessage()]
            );
        }
    }

    protected function calculateSubscriptionDiscount(
        float $price,
        string $plan,
        string $billingCycle
    ): float {
        // Billing cycle-based discounts
        $cycleDiscounts = [
            'monthly' => 0.0,        // No discount for monthly
            'quarterly' => 0.05,     // 5% discount for quarterly (3 months)
            'semi_annual' => 0.10,   // 10% discount for semi-annual (6 months)
            'annual' => 0.15,        // 15% discount for annual (12 months)
            'biennial' => 0.20,      // 20% discount for 2-year commitment
        ];

        // Plan-specific multipliers
        $planMultipliers = [
            'starter' => 1.0,        // Base price for starter
            'professional' => 1.5,   // 50% more than starter
            'enterprise' => 2.5,     // 2.5x starter price
            'custom' => 1.0,         // Custom pricing
        ];

        $cycleDiscount = $cycleDiscounts[$billingCycle] ?? 0.0;
        return max(0.0, $price * $cycleDiscount);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMetadata(): array
    {
        return [
            'description' => 'Applies subscription plan pricing and recurring billing rates',
            'capabilities' => [
                'plan_based_pricing',
                'cycle_based_discounts',
                'proration_calculation',
                'usage_tracking',
                'recurring_billing',
            ],
            'supported_conditions' => [
                'subscription_plan',
                'billing_cycle',
                'user_id',
                'subscription_active',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 15; // Run before loyalty and promotions
    }

    public function hasConflictWith(string $engineId): bool
    {
        return false;
    }

    public function getConflictResolution(string $engineId): string
    {
        return 'maximum';
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => false,
            'allow_cycle_discount' => true,
            'annual_discount_percentage' => 15,
            'proration_enabled' => true,
        ];
    }
}
