<?php

namespace WorkCore\Subscriptions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Subscriptions\Domain\MembershipTier;

class MembershipTierController
{
    /**
     * Create membership tier
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|uuid',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'features_json' => 'nullable|array',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'billing_cycle' => 'required|in:monthly,yearly,quarterly,custom',
            'cycle_days' => 'nullable|integer|min:1',
            'active' => 'boolean',
        ]);

        $tier = MembershipTier::create($validated);

        return response()->json($tier, 201);
    }

    /**
     * List membership tiers
     */
    public function index(Request $request): JsonResponse
    {
        $tiers = MembershipTier::forTenant($request->get('tenant_id'))
            ->when($request->get('active'), fn ($q) => $q->where('active', true))
            ->orderBy('price')
            ->get();

        return response()->json($tiers);
    }

    /**
     * Get membership tier
     */
    public function show(int $tierId): JsonResponse
    {
        $tier = MembershipTier::find($tierId);

        if (!$tier) {
            return response()->json(['message' => 'Tier not found'], 404);
        }

        return response()->json($tier);
    }

    /**
     * Update membership tier
     */
    public function update(Request $request, int $tierId): JsonResponse
    {
        $tier = MembershipTier::find($tierId);

        if (!$tier) {
            return response()->json(['message' => 'Tier not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'features_json' => 'sometimes|array',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'billing_cycle' => 'sometimes|in:monthly,yearly,quarterly,custom',
            'cycle_days' => 'sometimes|nullable|integer|min:1',
            'active' => 'sometimes|boolean',
        ]);

        $tier->update($validated);

        return response()->json($tier);
    }

    /**
     * Delete membership tier
     */
    public function destroy(int $tierId): JsonResponse
    {
        $tier = MembershipTier::find($tierId);

        if (!$tier) {
            return response()->json(['message' => 'Tier not found'], 404);
        }

        $tier->delete();

        return response()->json(['message' => 'Tier deleted successfully']);
    }
}
