<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Subscriptions\Application\Services\SubscriptionService;
use WorkCore\Subscriptions\Domain\MembershipTier;
use WorkCore\Subscriptions\Domain\Subscription;

class SubscriptionController
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    /**
     * Create a new subscription
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|uuid',
            'customer_id' => 'required|uuid',
            'tier_id' => 'required|exists:membership_tiers,id',
            'is_trial' => 'boolean',
            'trial_ends_at' => 'nullable|date',
        ]);

        $tier = MembershipTier::find($validated['tier_id']);
        $subscription = $this->subscriptionService->createSubscription(
            $validated['tenant_id'],
            $validated['customer_id'],
            $tier,
            $validated['is_trial'] ?? false,
            isset($validated['trial_ends_at']) ? new \DateTime($validated['trial_ends_at']) : null
        );

        return response()->json($subscription, 201);
    }

    /**
     * Get subscription details
     */
    public function show(Request $request, int $subscriptionId): JsonResponse
    {
        $subscription = $this->subscriptionService->getSubscription(
            $request->get('tenant_id'),
            $subscriptionId
        );

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        return response()->json($subscription);
    }

    /**
     * Get customer subscriptions
     */
    public function getCustomerSubscriptions(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getCustomerSubscriptions(
            $request->get('tenant_id'),
            $request->get('customer_id')
        );

        return response()->json($subscriptions);
    }

    /**
     * Upgrade subscription
     */
    public function upgrade(Request $request, int $subscriptionId): JsonResponse
    {
        $validated = $request->validate([
            'new_tier_id' => 'required|exists:membership_tiers,id',
        ]);

        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $newTier = MembershipTier::find($validated['new_tier_id']);

        try {
            $this->subscriptionService->upgradeSubscription($subscription, $newTier);

            return response()->json(['message' => 'Subscription upgraded successfully', 'subscription' => $subscription]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Downgrade subscription
     */
    public function downgrade(Request $request, int $subscriptionId): JsonResponse
    {
        $validated = $request->validate([
            'new_tier_id' => 'required|exists:membership_tiers,id',
        ]);

        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $newTier = MembershipTier::find($validated['new_tier_id']);

        try {
            $this->subscriptionService->downgradeSubscription($subscription, $newTier);

            return response()->json(['message' => 'Subscription downgraded successfully', 'subscription' => $subscription]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Pause subscription
     */
    public function pause(Request $request, int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        try {
            $this->subscriptionService->pauseSubscription($subscription, $request->get('reason'));

            return response()->json(['message' => 'Subscription paused successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Resume subscription
     */
    public function resume(int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        try {
            $this->subscriptionService->resumeSubscription($subscription);

            return response()->json(['message' => 'Subscription resumed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        try {
            $this->subscriptionService->cancelSubscription(
                $subscription,
                $request->get('reason'),
                $request->boolean('refund', false)
            );

            return response()->json(['message' => 'Subscription cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get subscription status
     */
    public function status(int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        return response()->json($this->subscriptionService->getSubscriptionStatus($subscription));
    }
}
