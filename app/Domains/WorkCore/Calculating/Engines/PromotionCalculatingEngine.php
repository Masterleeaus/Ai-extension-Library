<?php

namespace App\Domains\WorkCore\Calculating\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;

class PromotionCalculatingEngine implements CalculatingEngineContract
{
    protected bool $enabled = true;

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function getEngineId(): string
    {
        return 'promotion_engine';
    }

    public function getEngineName(): string
    {
        return 'Time-Limited Promotions Engine';
    }

    public function getEngineVersion(): string
    {
        return '1.0.0';
    }

    public function getEngineType(): string
    {
        return 'promotion';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && config('calculating-engines.engines.promotion_engine.enabled', false);
    }

    public function shouldApply(PricingContextContract $context): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Check if there are active promotions
        $resourceType = $context->get('resource_type');
        $promotionActive = $context->get('promotion_active', false);

        return $promotionActive && !empty($resourceType);
    }

    public function calculate(PricingContextContract $context): CalculatingEngineResult
    {
        try {
            $basePrice = $context->getCurrentPrice();
            $resourceType = $context->get('resource_type');
            $promotionCode = $context->get('promotion_code');
            $quantity = $context->get('quantity', 1);

            // Calculate promotion discount
            $discountAmount = $this->calculatePromotionDiscount(
                $basePrice,
                $quantity,
                $resourceType,
                $promotionCode
            );

            $adjustedPrice = max(0, $basePrice - $discountAmount);

            return CalculatingEngineResult::success(
                $this->getEngineId(),
                $basePrice,
                $adjustedPrice,
                sprintf('Promotion applied: $%.2f discount', $discountAmount),
                [
                    'promotion_discount' => $discountAmount,
                    'promotion_code' => $promotionCode,
                    'resource_type' => $resourceType,
                ],
                [
                    'promotion_id' => null, // Will be populated by implementation
                    'promotion_type' => 'percentage_or_fixed',
                ]
            );
        } catch (\Exception $e) {
            return CalculatingEngineResult::failure(
                $this->getEngineId(),
                $context->getCurrentPrice(),
                "Promotion calculation failed: {$e->getMessage()}",
                ['error' => $e->getMessage()]
            );
        }
    }

    protected function calculatePromotionDiscount(
        float $price,
        int $quantity,
        string $resourceType,
        ?string $promotionCode = null
    ): float {
        $discount = 0.0;

        // Apply quantity-based bulk discounts
        if ($quantity >= 10) {
            $discount = max($discount, $price * 0.10); // 10% bulk discount
        } elseif ($quantity >= 5) {
            $discount = max($discount, $price * 0.05); // 5% bulk discount
        }

        // Apply promotion code discount if provided
        if (!empty($promotionCode)) {
            // Default promotion code discounts
            $promotionRates = [
                'SUMMER20' => 0.20,     // 20% off
                'SPRING15' => 0.15,     // 15% off
                'WELCOME10' => 0.10,    // 10% off
                'BETA05' => 0.05,       // 5% off
            ];

            if (isset($promotionRates[$promotionCode])) {
                $discount = max($discount, $price * $promotionRates[$promotionCode]);
            }
        }

        return max(0.0, min($discount, $price)); // Cap at price, never negative
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMetadata(): array
    {
        return [
            'description' => 'Applies time-limited promotional discounts and bulk pricing',
            'capabilities' => [
                'percentage_discounts',
                'fixed_discounts',
                'bulk_pricing',
                'quantity_based_discounts',
                'promotion_codes',
            ],
            'supported_conditions' => [
                'resource_type',
                'quantity',
                'promotion_code',
                'date_range',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 30; // Run after loyalty
    }

    public function hasConflictWith(string $engineId): bool
    {
        return in_array($engineId, ['discount_engine', 'loyalty_engine']);
    }

    public function getConflictResolution(string $engineId): string
    {
        return 'maximum'; // Use best discount
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => false,
            'allow_stacking' => false,
            'max_discount_percentage' => 100,
            'bulk_discount_enabled' => true,
        ];
    }
}
