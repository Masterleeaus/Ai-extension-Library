<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Subscriptions\Application\Services\AccessControlService;
use WorkCore\Subscriptions\Domain\Subscription;

class UsageController
{
    public function __construct(private AccessControlService $accessControlService)
    {
    }

    /**
     * Get usage limits for subscription
     */
    public function show(Request $request, int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $usage = $this->accessControlService->getAllUsageLimits($subscription);

        return response()->json($usage);
    }

    /**
     * Get usage for specific feature
     */
    public function getFeatureUsage(Request $request, int $subscriptionId, string $featureSlug): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $usage = $this->accessControlService->getUsageStatus($subscription, $featureSlug);

        if (!$usage) {
            return response()->json(['message' => 'Feature usage not found'], 404);
        }

        return response()->json($usage);
    }

    /**
     * Increment feature usage
     */
    public function increment(Request $request, int $subscriptionId, string $featureSlug): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'integer|min:1|max:1000',
        ]);

        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $success = $this->accessControlService->incrementUsage(
            $subscription,
            $featureSlug,
            $validated['amount'] ?? 1
        );

        if (!$success) {
            return response()->json(['message' => 'Usage limit exceeded or feature not found'], 422);
        }

        $usage = $this->accessControlService->getUsageStatus($subscription, $featureSlug);

        return response()->json($usage);
    }
}
