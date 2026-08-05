<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use WorkCore\Subscriptions\Domain\MembershipTier;
use WorkCore\Subscriptions\Domain\Subscription;
use WorkCore\Subscriptions\Domain\SubscriptionCycle;
use WorkCore\Subscriptions\Domain\UsageLimit;

class SubscriptionService
{
    /**
     * Create a new subscription
     */
    public function createSubscription(
        string $tenantId,
        string $customerId,
        MembershipTier $tier,
        bool $isTrialPeriod = false,
        ?\DateTime $trialEndsAt = null
    ): Subscription {
        $subscription = Subscription::create([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'tier_id' => $tier->id,
            'status' => $isTrialPeriod ? 'trial' : 'active',
            'started_at' => now(),
            'is_trial' => $isTrialPeriod,
            'trial_ends_at' => $trialEndsAt,
        ]);

        // Create initial cycle
        $this->createCycle($subscription, $tier);

        // Initialize usage limits from tier features
        $this->initializeUsageLimits($subscription, $tier);

        event(new \WorkCore\Subscriptions\Domain\Events\SubscriptionCreated($subscription, [
            'is_trial' => $isTrialPeriod,
        ]));

        return $subscription;
    }

    /**
     * Get subscription by ID
     */
    public function getSubscription(string $tenantId, int $subscriptionId): ?Subscription
    {
        return Subscription::forTenant($tenantId)->find($subscriptionId);
    }

    /**
     * Get customer subscriptions
     */
    public function getCustomerSubscriptions(string $tenantId, string $customerId): Collection
    {
        return Subscription::forTenant($tenantId)
            ->forCustomer($customerId)
            ->with('tier')
            ->get();
    }

    /**
     * Upgrade subscription
     */
    public function upgradeSubscription(Subscription $subscription, MembershipTier $newTier): void
    {
        $subscription->upgrade($newTier);
    }

    /**
     * Downgrade subscription
     */
    public function downgradeSubscription(Subscription $subscription, MembershipTier $newTier): void
    {
        $subscription->downgrade($newTier);
    }

    /**
     * Pause subscription
     */
    public function pauseSubscription(Subscription $subscription, string $reason = null): void
    {
        $subscription->pause($reason);
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(Subscription $subscription): void
    {
        $subscription->resume();
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(
        Subscription $subscription,
        string $reason = null,
        bool $refund = false
    ): void {
        $subscription->cancel($reason, $refund);
    }

    /**
     * Create a subscription cycle
     */
    private function createCycle(Subscription $subscription, MembershipTier $tier): SubscriptionCycle
    {
        $cycleNumber = $subscription->cycles()->count() + 1;
        $now = now();

        $endDate = match ($tier->billing_cycle) {
            'monthly' => $now->copy()->addMonth(),
            'yearly' => $now->copy()->addYear(),
            'quarterly' => $now->copy()->addMonths(3),
            'custom' => $now->copy()->addDays($tier->cycle_days ?? 30),
            default => $now->copy()->addMonth(),
        };

        return SubscriptionCycle::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'cycle_number' => $cycleNumber,
            'start_date' => $now->toDateString(),
            'end_date' => $endDate->toDateString(),
            'amount_paid' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Initialize usage limits for subscription
     */
    private function initializeUsageLimits(Subscription $subscription, MembershipTier $tier): void
    {
        $features = $tier->getFeatures();

        foreach ($features as $feature) {
            if (isset($feature['slug'], $feature['limit'])) {
                UsageLimit::create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'feature_slug' => $feature['slug'],
                    'limit_value' => $feature['limit'],
                    'usage_count' => 0,
                    'reset_date' => now()->addMonth(),
                ]);
            }
        }
    }

    /**
     * Renew subscription cycle
     */
    public function renewSubscriptionCycle(Subscription $subscription): SubscriptionCycle
    {
        $lastCycle = $subscription->cycles()->orderByDesc('id')->first();

        if (!$lastCycle || !$lastCycle->end_date->isPast()) {
            throw new \InvalidArgumentException('Cannot renew subscription cycle that is still active');
        }

        return $this->createCycle($subscription, $subscription->tier);
    }

    /**
     * Check if subscription has feature access
     */
    public function hasFeatureAccess(Subscription $subscription, string $featureSlug): bool
    {
        if (!$subscription->isActive()) {
            return false;
        }

        $features = $subscription->tier->getFeatures();

        return collect($features)->contains(fn ($feature) => $feature['slug'] === $featureSlug);
    }

    /**
     * Get subscription status info
     */
    public function getSubscriptionStatus(Subscription $subscription): array
    {
        return [
            'status' => $subscription->status,
            'is_active' => $subscription->isActive(),
            'is_trial' => $subscription->isInTrial(),
            'tier' => $subscription->tier->name,
            'started_at' => $subscription->started_at,
            'expires_at' => $subscription->expires_at,
            'trial_ends_at' => $subscription->trial_ends_at,
        ];
    }
}
