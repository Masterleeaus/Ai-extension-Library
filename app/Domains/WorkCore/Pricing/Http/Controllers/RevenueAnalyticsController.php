<?php

namespace App\Domains\WorkCore\Pricing\Http\Controllers;

use App\Domains\WorkCore\Pricing\Services\RevenueOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RevenueAnalyticsController
{
    protected RevenueOptimizationService $revenueService;

    public function __construct(RevenueOptimizationService $revenueService)
    {
        $this->revenueService = $revenueService;
    }

    /**
     * GET /api/workcore/pricing/revenue/optimize
     */
    public function optimize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'base_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->user()->active_company_id;
        $optimization = $this->revenueService->optimizeRevenue(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['base_price']
        );

        return response()->json([
            'success' => true,
            'data' => $optimization,
        ]);
    }

    /**
     * GET /api/workcore/pricing/revenue/suggestions
     */
    public function suggestPrices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'base_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->user()->active_company_id;
        $suggestions = $this->revenueService->suggestPrices(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['base_price']
        );

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * GET /api/workcore/pricing/revenue/forecast
     */
    public function forecast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'days' => 'integer|min:1|max:365',
        ]);

        $companyId = $request->user()->active_company_id;
        $forecast = $this->revenueService->calculateRevenueForecast(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['days'] ?? 30
        );

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }

    /**
     * GET /api/workcore/pricing/revenue/analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'days' => 'integer|min:1|max:365',
        ]);

        $companyId = $request->user()->active_company_id;
        $analytics = $this->revenueService->getRevenueAnalytics(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['days'] ?? 30
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * GET /api/workcore/pricing/revenue/compare-strategies
     */
    public function compareStrategies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'base_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->user()->active_company_id;
        $comparison = $this->revenueService->compareStrategies(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['base_price']
        );

        return response()->json([
            'success' => true,
            'data' => $comparison,
        ]);
    }

    /**
     * GET /api/workcore/pricing/revenue/report
     */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'days' => 'integer|min:1|max:365',
        ]);

        $companyId = $request->user()->active_company_id;
        $report = $this->revenueService->getRevenueReport(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['days'] ?? 30
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * GET /api/workcore/pricing/revenue/recommendations
     */
    public function recommendations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
        ]);

        $companyId = $request->user()->active_company_id;
        $recommendations = $this->revenueService->getOptimizationRecommendations(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id']
        );

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }
}
