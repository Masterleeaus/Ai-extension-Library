<?php

namespace WorkCore\Subscriptions\Application\Services;

use WorkCore\Subscriptions\Domain\Subscription;
use WorkCore\Subscriptions\Domain\UsageLimit;

class AccessControlService
{
    /**
     * Check if subscription has feature access
     */
    public function hasFeatureAccess(Subscription $subscription, string $featureSlug): bool
    {
        if (!$subscription->isActive()) {
            return false;
        }

        $features = $subscription->tier->getFeatures();

        return collect($features)->contains(fn ($feature) => ($feature['slug'] ?? null) === $featureSlug);
    }

    /**
     * Check usage limit
     */
    public function checkUsageLimit(Subscription $subscription, string $featureSlug): bool
    {
        $usageLimit = UsageLimit::where('subscription_id', $subscription->id)
            ->where('feature_slug', $featureSlug)
            ->first();

        if (!$usageLimit) {
            return false;
        }

        return !$usageLimit->isExceeded();
    }

    /**
     * Increment feature usage
     */
    public function incrementUsage(Subscription $subscription, string $featureSlug, int $amount = 1): bool
    {
        $usageLimit = UsageLimit::where('subscription_id', $subscription->id)
            ->where('feature_slug', $featureSlug)
            ->first();

        if (!$usageLimit) {
            return false;
        }

        if ($this->checkUsageLimit($subscription, $featureSlug)) {
            $usageLimit->incrementUsage($amount);

            return true;
        }

        return false;
    }

    /**
     * Get usage status for a feature
     */
    public function getUsageStatus(Subscription $subscription, string $featureSlug): ?array
    {
        $usageLimit = UsageLimit::where('subscription_id', $subscription->id)
            ->where('feature_slug', $featureSlug)
            ->first();

        if (!$usageLimit) {
            return null;
        }

        return [
            'limit' => $usageLimit->limit_value,
            'used' => $usageLimit->usage_count,
            'remaining' => $usageLimit->getRemainingUsage(),
            'percentage' => $usageLimit->getUsagePercentage(),
            'exceeded' => $usageLimit->isExceeded(),
        ];
    }

    /**
     * Get all usage limits for subscription
     */
    public function getAllUsageLimits(Subscription $subscription): array
    {
        $limits = [];
        $usageLimits = UsageLimit::where('subscription_id', $subscription->id)->get();

        foreach ($usageLimits as $limit) {
            $limits[$limit->feature_slug] = [
                'limit' => $limit->limit_value,
                'used' => $limit->usage_count,
                'remaining' => $limit->getRemainingUsage(),
                'percentage' => $limit->getUsagePercentage(),
                'exceeded' => $limit->isExceeded(),
                'reset_date' => $limit->reset_date,
            ];
        }

        return $limits;
    }

    /**
     * Check concurrent user limit
     */
    public function checkConcurrentUsers(Subscription $subscription, int $currentUsers): bool
    {
        $features = $subscription->tier->getFeatures();
        $concurrentLimit = collect($features)
            ->where('slug', 'concurrent_users')
            ->first()['limit'] ?? null;

        if ($concurrentLimit === null) {
            return true; // No limit
        }

        return $currentUsers <= $concurrentLimit;
    }

    /**
     * Grant temporary feature access
     */
    public function grantTemporaryAccess(
        Subscription $subscription,
        string $featureSlug,
        int $durationDays = 7
    ): void {
        // Store temporary access grant
        // This would typically be stored in a separate table
        // For now, we'll dispatch an event
        event(new \WorkCore\Subscriptions\Domain\Events\TemporaryAccessGranted(
            $subscription,
            $featureSlug,
            $durationDays
        ));
    }

    /**
     * Revoke temporary feature access
     */
    public function revokeTemporaryAccess(Subscription $subscription, string $featureSlug): void
    {
        event(new \WorkCore\Subscriptions\Domain\Events\TemporaryAccessRevoked(
            $subscription,
            $featureSlug
        ));
    }

    /**
     * Reset usage for a feature
     */
    public function resetUsage(Subscription $subscription, string $featureSlug): void
    {
        $usageLimit = UsageLimit::where('subscription_id', $subscription->id)
            ->where('feature_slug', $featureSlug)
            ->first();

        if ($usageLimit) {
            $usageLimit->reset();
        }
    }

    /**
     * Get subscription access summary
     */
    public function getAccessSummary(Subscription $subscription): array
    {
        return [
            'status' => $subscription->status,
            'is_active' => $subscription->isActive(),
            'tier' => $subscription->tier->name,
            'features' => $subscription->tier->getFeatures(),
            'usage_limits' => $this->getAllUsageLimits($subscription),
        ];
    }
}
