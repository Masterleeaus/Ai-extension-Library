<?php

namespace App\Domains\WorkCore\Calculating\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;

class LoyaltyCalculatingEngine implements CalculatingEngineContract
{
    protected bool $enabled = true;

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function getEngineId(): string
    {
        return 'loyalty_engine';
    }

    public function getEngineName(): string
    {
        return 'Loyalty Points & Rewards Engine';
    }

    public function getEngineVersion(): string
    {
        return '1.0.0';
    }

    public function getEngineType(): string
    {
        return 'loyalty';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && config('calculating-engines.engines.loyalty_engine.enabled', false);
    }

    public function shouldApply(PricingContextContract $context): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $userId = $context->get('user_id');
        $hasLoyaltyProgram = $context->get('has_loyalty_program', false);

        return !empty($userId) && $hasLoyaltyProgram;
    }

    public function calculate(PricingContextContract $context): CalculatingEngineResult
    {
        try {
            $basePrice = $context->getCurrentPrice();
            $userId = $context->get('user_id');
            $loyaltyTier = $context->get('loyalty_tier', 'standard');

            // Calculate loyalty discount
            $discountAmount = $this->calculateLoyaltyDiscount(
                $basePrice,
                $userId,
                $loyaltyTier
            );

            $adjustedPrice = max(0, $basePrice - $discountAmount);

            return CalculatingEngineResult::success(
                $this->getEngineId(),
                $basePrice,
                $adjustedPrice,
                sprintf('Loyalty discount applied: $%.2f (%s tier)', $discountAmount, $loyaltyTier),
                [
                    'loyalty_discount' => $discountAmount,
                    'loyalty_tier' => $loyaltyTier,
                    'points_earned' => intval($basePrice),
                ],
                [
                    'tier_multiplier' => $this->getTierMultiplier($loyaltyTier),
                ]
            );
        } catch (\Exception $e) {
            return CalculatingEngineResult::failure(
                $this->getEngineId(),
                $context->getCurrentPrice(),
                "Loyalty calculation failed: {$e->getMessage()}",
                ['error' => $e->getMessage()]
            );
        }
    }

    protected function calculateLoyaltyDiscount(float $price, int $userId, string $tier): float
    {
        // TODO: Implement loyalty discount calculation
        // This should:
        // - Fetch user's loyalty points balance
        // - Check tier-specific discount rates
        // - Apply points redemption if enabled
        // - Track points earned on this purchase
        return 0.0;
    }

    protected function getTierMultiplier(string $tier): float
    {
        return match ($tier) {
            'bronze' => 1.0,
            'silver' => 1.25,
            'gold' => 1.5,
            'platinum' => 2.0,
            default => 1.0,
        };
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMetadata(): array
    {
        return [
            'description' => 'Applies loyalty tier discounts and points-based rewards',
            'capabilities' => [
                'tier_based_discounts',
                'points_redemption',
                'points_earning',
                'tier_progression',
            ],
            'supported_conditions' => [
                'user_id',
                'loyalty_tier',
                'points_balance',
                'member_since',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 25; // Run after discounts
    }

    public function hasConflictWith(string $engineId): bool
    {
        // Loyalty conflicts with discount and promotion engines
        return in_array($engineId, ['discount_engine', 'promotion_engine']);
    }

    public function getConflictResolution(string $engineId): string
    {
        return 'maximum'; // Use the best discount for customer
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => false,
            'allow_points_redemption' => true,
            'points_multiplier' => 1.0,
            'min_points_for_redemption' => 100,
        ];
    }
}
