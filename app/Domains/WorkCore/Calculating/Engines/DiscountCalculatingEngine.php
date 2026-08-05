<?php

namespace App\Domains\WorkCore\Calculating\Engines;

use App\Domains\WorkCore\Calculating\Contracts\CalculatingEngineContract;
use App\Domains\WorkCore\Calculating\Contracts\PricingContextContract;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineResult;
use App\Extensions\DiscountManager\System\Models\ConditionalDiscount;
use App\Extensions\DiscountManager\System\Enums\UserTypeEnum;
use App\Helpers\Classes\Helper;
use App\Services\Payment\Enums\PaymentGatewayEnum;

class DiscountCalculatingEngine implements CalculatingEngineContract
{
    protected bool $enabled = true;

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function getEngineId(): string
    {
        return 'discount_engine';
    }

    public function getEngineName(): string
    {
        return 'Discount Engine';
    }

    public function getEngineVersion(): string
    {
        return '1.0.0';
    }

    public function getEngineType(): string
    {
        return 'discount';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && config('discount-manager.enabled', true);
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function shouldApply(PricingContextContract $context): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Check if discounts are available
        return ConditionalDiscount::where('active', true)
            ->where('scheduled', false)
            ->whereNotNull('coupon_id')
            ->exists();
    }

    public function calculate(PricingContextContract $context): CalculatingEngineResult
    {
        try {
            $basePrice = $context->getBasePrice();

            // Extract discount context from pricing context
            $userId = $context->get('user_id');
            $planId = $context->get('plan_id');
            $gateway = $context->get('payment_gateway');
            $url = $context->get('url', request()?->url());
            $hasActiveSubscription = $context->get('subscription_active', false);

            // Find applicable discount
            $discount = $this->findApplicableDiscount(
                $userId,
                $planId,
                $gateway,
                $hasActiveSubscription
            );

            if (!$discount) {
                return CalculatingEngineResult::success(
                    $this->getEngineId(),
                    $basePrice,
                    $basePrice,
                    'No applicable discount found',
                    [],
                    []
                );
            }

            // Calculate discount amount
            $coupon = $discount->coupon;
            if (!$coupon) {
                return CalculatingEngineResult::success(
                    $this->getEngineId(),
                    $basePrice,
                    $basePrice,
                    'Coupon not found',
                    [],
                    []
                );
            }

            $discountAmount = $this->calculateDiscountAmount(
                $basePrice,
                $coupon,
                $discount->type
            );

            $adjustedPrice = max(0, $basePrice - $discountAmount);

            $factors = [
                'discount_type' => $discount->type,
                'discount_amount' => $discountAmount,
                'coupon_code' => $coupon->code,
                'coupon_discount_value' => $coupon->discount,
            ];

            $details = [
                'coupon_id' => $coupon->id,
                'discount_id' => $discount->id,
                'discount_reason' => 'Conditional discount applied',
                'conditions_met' => [
                    'user_type' => true,
                    'payment_gateway' => true,
                    'pricing_plan' => true,
                ],
            ];

            $reason = sprintf(
                'Discount applied: %s (Coupon: %s, Amount: $%.2f)',
                $discount->title,
                $coupon->code,
                $discountAmount
            );

            $result = CalculatingEngineResult::success(
                $this->getEngineId(),
                $basePrice,
                $adjustedPrice,
                $reason,
                $factors,
                $details
            );

            $result->setAppliedConditions([
                'discount_id' => $discount->id,
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
            ]);

            return $result;
        } catch (\Exception $e) {
            return CalculatingEngineResult::failure(
                $this->getEngineId(),
                $context->getBasePrice(),
                "Discount calculation failed: {$e->getMessage()}",
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Find the best applicable discount
     */
    protected function findApplicableDiscount(
        ?int $userId,
        ?string $planId,
        ?string $gateway,
        bool $hasActiveSubscription = false
    ): ?ConditionalDiscount {
        $activeDiscounts = ConditionalDiscount::where('active', true)
            ->where('scheduled', false)
            ->whereNotNull('coupon_id')
            ->orderBy('amount', 'desc')
            ->get();

        foreach ($activeDiscounts as $discount) {
            if ($this->validateDiscountConditions(
                $discount,
                $userId,
                $planId,
                $gateway,
                $hasActiveSubscription
            )) {
                return $discount;
            }
        }

        return null;
    }

    /**
     * Validate if discount meets all eligibility criteria
     */
    protected function validateDiscountConditions(
        ?ConditionalDiscount $discount,
        ?int $userId,
        ?string $planId,
        ?string $gateway,
        bool $hasActiveSubscription = false
    ): bool {
        if (!$discount || !$discount->active || !$discount->coupon) {
            return false;
        }

        // Hide discount for subscribed users
        if ($discount->hide_discount_for_subscribed_users && $hasActiveSubscription) {
            return false;
        }

        // Scheduled discount time validation
        if ($discount->scheduled) {
            if (now()->isBefore($discount->start_date) || now()->isAfter($discount->end_date)) {
                return false;
            }
        }

        // Usage limit check
        $usageCount = $discount->coupon->usage_count ?? 0;
        $usageValid = $discount->total_usage_limit <= 0 || $usageCount < $discount->total_usage_limit;

        if (!$usageValid) {
            return false;
        }

        // Once per user check
        $oncePerUserValid = !$discount->allow_once_per_user
            || !$userId
            || !$discount->coupon?->usersUsed()->where('user_id', $userId)->exists();

        if (!$oncePerUserValid) {
            return false;
        }

        // Check all discount conditions
        return $this->checkDiscountConditionsFor(
            $discount,
            $planId,
            $gateway,
            $hasActiveSubscription
        );
    }

    /**
     * Check specific discount conditions
     */
    protected function checkDiscountConditionsFor(
        ?ConditionalDiscount $discount,
        ?string $planId,
        ?string $gateway,
        bool $hasActiveSubscription = false
    ): bool {
        if (!$discount || !$discount->coupon || !$discount->active) {
            return false;
        }

        if ($discount->hide_discount_for_subscribed_users && $hasActiveSubscription) {
            return false;
        }

        if ($discount->scheduled) {
            if (now()->isBefore($discount->start_date) || now()->isAfter($discount->end_date)) {
                return false;
            }
        }

        // Normalize gateway input
        $currentGateways = is_string($gateway)
            ? array_filter(explode(',', $gateway))
            : (array) $gateway;

        // Gateway condition
        $discountGateways = array_filter(explode(',', $discount->payment_gateway ?? ''));
        $gatewayValid = !empty($discountGateways)
            && !empty(array_intersect($currentGateways, $discountGateways));

        // User type condition
        $userTypes = array_filter(explode(',', $discount->user_type ?? ''));
        if (empty($userTypes)) {
            $userValid = false;
        } else {
            $hasInactive = in_array(UserTypeEnum::INACTIVE->value, $userTypes, true);
            $hasNew = in_array(UserTypeEnum::NEW->value, $userTypes, true);

            $noSubscription = $hasInactive && !$hasActiveSubscription;
            $isNewUser = $hasNew && auth()->user()?->created_at?->gte(now()->subDays(7));

            $userValid = $noSubscription || $isNewUser;
        }

        // Plan condition
        $pricingPlansIds = array_filter(explode(',', $discount->pricing_plans ?? ''));
        $planValid = !empty($pricingPlansIds) && in_array($planId, $pricingPlansIds, true);

        // Combine based on discount's condition logic
        return match ($discount->condition ?? 'and') {
            'or' => $gatewayValid || $userValid || $planValid,
            'and' => $gatewayValid && $userValid && $planValid,
            default => false,
        };
    }

    /**
     * Calculate the discount amount based on type (percentage or fixed)
     */
    protected function calculateDiscountAmount(float $basePrice, $coupon, string $discountType): float
    {
        $discountValue = $coupon->discount ?? 0;

        // Check if coupon is fixed price or percentage
        if ($coupon->is_offer_fixed_price) {
            // Fixed discount
            return min($discountValue, $basePrice);
        }

        // Percentage discount
        return ($basePrice * $discountValue) / 100;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getMetadata(): array
    {
        return [
            'description' => 'Applies conditional discounts and coupon-based pricing adjustments',
            'capabilities' => [
                'conditional_discounts',
                'coupon_validation',
                'usage_limits',
                'per_user_restrictions',
                'scheduled_discounts',
            ],
            'supported_conditions' => [
                'payment_gateway',
                'user_type',
                'pricing_plan',
                'subscription_status',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 20; // Run after dynamic pricing, before tax
    }

    public function hasConflictWith(string $engineId): bool
    {
        // Discount may conflict with promotion or loyalty engines
        return in_array($engineId, ['promotion_engine', 'loyalty_engine']);
    }

    public function getConflictResolution(string $engineId): string
    {
        return 'maximum'; // Use the discount that results in lower price (higher discount)
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'allow_multiple_discounts' => false,
            'max_discount_percentage' => 100,
            'min_discount_amount' => 0,
        ];
    }
}
