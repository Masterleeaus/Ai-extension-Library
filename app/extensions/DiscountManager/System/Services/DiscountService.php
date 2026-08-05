<?php

declare(strict_types=1);

namespace App\Extensions\DiscountManager\System\Services;

use App\Extensions\DiscountManager\System\Enums\UserTypeEnum;
use App\Extensions\DiscountManager\System\Models\ConditionalDiscount;
use App\Helpers\Classes\Helper;
use App\Models\Coupon;
use App\Services\Payment\Enums\PaymentGatewayEnum;
use Exception;

class DiscountService
{
    /**
     * Apply discount coupon using the DiscountCalculatingEngine
     *
     * @throws Exception
     */
    public static function applyDiscountCoupon(): ?Coupon
    {
        // Maintain backward compatibility by delegating to the discount calculating engine
        try {
            $engine = app(\App\Domains\WorkCore\Calculating\Engines\DiscountCalculatingEngine::class);

            if (!$engine->isEnabled()) {
                return null;
            }

            // Get current context information
            $currentUrl = request()?->url();
            $userId = auth()->id();
            $hasActiveSubscription = !is_null(Helper::getCurrentActiveSubscription($userId));

            // Extract plan ID and gateway from URL
            $planIdFromUrl = null;
            $currentGateway = null;

            if ($currentUrl) {
                // Detect gateway from URL
                $activeGateways = PaymentGatewayEnum::activeGateways();
                foreach ($activeGateways as $gateway) {
                    if (str_contains($currentUrl, $gateway)) {
                        $currentGateway = $gateway;
                        break;
                    }
                }

                // Detect plan from URL
                if (preg_match('/\/(\d+)\//', $currentUrl, $matches)) {
                    $planIdFromUrl = $matches[1];
                }
            }

            // Find applicable discount using the engine's logic
            $activeDiscounts = ConditionalDiscount::where('active', true)
                ->where('scheduled', false)
                ->whereNotNull('coupon_id')
                ->orderBy('amount', 'desc')
                ->get();

            foreach ($activeDiscounts as $discount) {
                if (self::validateDiscountConditions(
                    $discount,
                    $userId,
                    $planIdFromUrl,
                    $currentGateway,
                    $hasActiveSubscription
                )) {
                    return $discount->coupon;
                }
            }

            return null;
        } catch (Exception $e) {
            // Fall back to direct validation if engine is unavailable
            return self::applyDiscountCouponDirect();
        }
    }

    /**
     * Direct discount application (fallback when engine is unavailable)
     *
     * @throws Exception
     */
    private static function applyDiscountCouponDirect(): ?Coupon
    {
        $activeDiscounts = ConditionalDiscount::where('active', true)
            ->where('scheduled', false)
            ->whereNotNull('coupon_id')
            ->orderBy('amount', 'desc')
            ->get();

        foreach ($activeDiscounts as $discount) {
            if (self::validateDiscountConditionsDirect($discount)) {
                return $discount->coupon;
            }
        }

        return null;
    }

    /**
     * Validate if the given discount meets all eligibility criteria.
     */
    private static function validateDiscountConditions(
        ?ConditionalDiscount $activeDiscount,
        ?int $userId,
        ?string $planId,
        ?string $gateway,
        bool $hasActiveSubscription = false
    ): bool {
        if (
            is_null($activeDiscount) ||
            !$activeDiscount->active ||
            is_null($activeDiscount->coupon) ||
            ($activeDiscount->hide_discount_for_subscribed_users && $hasActiveSubscription) ||
            (
                $activeDiscount->scheduled &&
                (now()->isBefore($activeDiscount->start_date) || now()->isAfter($activeDiscount->end_date))
            )
        ) {
            return false;
        }

        // Usage limit check
        $usageCount = $activeDiscount->coupon->usage_count ?? 0;
        $usageValid = $activeDiscount->total_usage_limit <= 0 || $usageCount < $activeDiscount->total_usage_limit;

        // Once per user check
        $oncePerUserValid = !$activeDiscount->allow_once_per_user
            || !$userId
            || !$activeDiscount->coupon?->usersUsed()->where('user_id', $userId)->exists();

        // Fail fast if usage limits or once-per-user checks fail
        if (!$usageValid || !$oncePerUserValid) {
            return false;
        }

        return self::checkDiscountConditionsFor($activeDiscount, $planId, $gateway, $hasActiveSubscription);
    }

    /**
     * Validate discount conditions directly (legacy fallback)
     */
    private static function validateDiscountConditionsDirect(?ConditionalDiscount $activeDiscount): bool
    {
        $currentUrl = request()?->url();
        $hasActiveSubscription = !is_null(Helper::getCurrentActiveSubscription(auth()->id()));

        if (
            is_null($activeDiscount) ||
            !$activeDiscount->active ||
            is_null($activeDiscount->coupon) ||
            ($activeDiscount->hide_discount_for_subscribed_users && $hasActiveSubscription) ||
            (
                $activeDiscount->scheduled &&
                (now()->isBefore($activeDiscount->start_date) || now()->isAfter($activeDiscount->end_date))
            )
        ) {
            return false;
        }

        // Usage limit check
        $usageCount = $activeDiscount->coupon->usage_count ?? 0;
        $usageValid = $activeDiscount->total_usage_limit <= 0 || $usageCount < $activeDiscount->total_usage_limit;

        // Once per user check
        $userId = auth()->id();
        $oncePerUserValid = !$activeDiscount->allow_once_per_user
            || !$userId
            || !$activeDiscount->coupon?->usersUsed()->where('user_id', $userId)->exists();

        if (!$usageValid || !$oncePerUserValid) {
            return false;
        }

        // Detect current gateway from URL
        $activeGateways = PaymentGatewayEnum::activeGateways();
        $currentGateway = null;
        foreach ($activeGateways as $gateway) {
            if (str_contains($currentUrl, $gateway)) {
                $currentGateway = $gateway;
                break;
            }
        }

        // Detect plan from URL
        $planIdFromUrl = null;
        if (preg_match('/\/(\d+)\//', $currentUrl, $matches)) {
            $planIdFromUrl = $matches[1];
        }

        return self::checkDiscountConditionsFor($activeDiscount, $planIdFromUrl, $currentGateway, $hasActiveSubscription);
    }

    public static function checkDiscountConditionsFor(
        ?ConditionalDiscount $activeDiscount,
        ?string $planId,
        ?string $gateway,
        $hasActiveSubscription = null
    ): bool {
        $user = auth()->user();
        if (is_null($hasActiveSubscription)) {
            $hasActiveSubscription = !is_null(Helper::getCurrentActiveSubscription($user->id ?? null));
        }

        if (
            is_null($activeDiscount) ||
            is_null($activeDiscount->coupon) ||
            !$activeDiscount->active ||
            ($activeDiscount->hide_discount_for_subscribed_users && $hasActiveSubscription) ||
            (
                $activeDiscount->scheduled &&
                (now()->isBefore($activeDiscount->start_date) || now()->isAfter($activeDiscount->end_date))
            )
        ) {
            return false;
        }

        // Normalize gateway input: split comma-separated string into array
        $currentGateways = is_string($gateway) ? array_filter(explode(',', $gateway)) : (array) $gateway;

        // Gateway condition
        $discountGateways = array_filter(explode(',', $activeDiscount->payment_gateway ?? ''));
        $gatewayValid = !empty($discountGateways) && !empty(array_intersect($currentGateways, $discountGateways));

        // User type condition
        $userTypes = array_filter(explode(',', $activeDiscount->user_type ?? ''));
        if (empty($userTypes)) {
            $userValid = false;
        } else {
            $hasInactive = in_array(UserTypeEnum::INACTIVE->value, $userTypes, true);
            $hasNew = in_array(UserTypeEnum::NEW->value, $userTypes, true);

            $noSubscription = $hasInactive && (!$hasActiveSubscription);
            $isNewUser = $hasNew && $user?->created_at?->gte(now()->subDays(7));

            $userValid = $noSubscription || $isNewUser;
        }

        // Plan condition
        $pricingPlansIds = array_filter(explode(',', $activeDiscount->pricing_plans ?? ''));
        $planValid = !empty($pricingPlansIds) && in_array($planId, $pricingPlansIds, true);

        // Combine based on discount's condition
        return match ($activeDiscount->condition ?? 'and') {
            'or' => $gatewayValid || $userValid || $planValid,
            'and' => $gatewayValid && $userValid && $planValid,
            default => false,
        };
    }
}
