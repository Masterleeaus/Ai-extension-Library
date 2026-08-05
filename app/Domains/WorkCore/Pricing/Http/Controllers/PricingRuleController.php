<?php

namespace App\Domains\WorkCore\Pricing\Http\Controllers;

use App\Domains\WorkCore\Pricing\Models\PricingRule;
use App\Domains\WorkCore\Pricing\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PricingRuleController
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * GET /api/workcore/pricing/rules
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->active_company_id;
        $filters = [
            'active_only' => $request->boolean('active_only', false),
            'rule_type' => $request->string('rule_type')->whenNotEmpty(),
            'ordered_by_priority' => true,
        ];

        $rules = $this->pricingService->getPricingRules($companyId, $filters);

        return response()->json([
            'success' => true,
            'data' => $rules,
            'count' => count($rules),
        ]);
    }

    /**
     * GET /api/workcore/pricing/rules/{id}
     */
    public function show(int $id): JsonResponse
    {
        $rule = PricingRule::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $rule,
        ]);
    }

    /**
     * POST /api/workcore/pricing/rules
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->active_company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|string|in:demand,seasonal,occupancy,time_based,custom',
            'conditions' => 'required|json',
            'adjustments' => 'required|json',
            'priority' => 'integer|min:1|max:1000',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|datetime',
            'ends_at' => 'nullable|datetime',
            'min_adjustment' => 'nullable|numeric',
            'max_adjustment' => 'nullable|numeric',
        ]);

        $rule = $this->pricingService->createPricingRule(
            $companyId,
            $validated,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => $rule,
            'message' => 'Pricing rule created successfully',
        ], 201);
    }

    /**
     * PUT /api/workcore/pricing/rules/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rule = PricingRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'json',
            'adjustments' => 'json',
            'priority' => 'integer|min:1|max:1000',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|datetime',
            'ends_at' => 'nullable|datetime',
            'min_adjustment' => 'nullable|numeric',
            'max_adjustment' => 'nullable|numeric',
        ]);

        $updated = $this->pricingService->updatePricingRule($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $updated,
            'message' => 'Pricing rule updated successfully',
        ]);
    }

    /**
     * DELETE /api/workcore/pricing/rules/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $this->pricingService->deletePricingRule($id);

        return response()->json([
            'success' => true,
            'message' => 'Pricing rule deleted successfully',
        ]);
    }
}
