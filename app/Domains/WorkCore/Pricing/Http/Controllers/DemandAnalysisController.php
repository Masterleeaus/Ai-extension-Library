<?php

namespace App\Domains\WorkCore\Pricing\Http\Controllers;

use App\Domains\WorkCore\Pricing\Services\DemandAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DemandAnalysisController
{
    protected DemandAnalysisService $demandAnalysisService;

    public function __construct(DemandAnalysisService $demandAnalysisService)
    {
        $this->demandAnalysisService = $demandAnalysisService;
    }

    /**
     * GET /api/workcore/pricing/demand-analysis
     */
    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'days' => 'integer|min:1|max:365',
        ]);

        $companyId = $request->user()->active_company_id;
        $analysis = $this->demandAnalysisService->analyzeDemand(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['days'] ?? 30
        );

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * GET /api/workcore/pricing/demand-elasticity
     */
    public function elasticity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
        ]);

        $companyId = $request->user()->active_company_id;
        $elasticity = $this->demandAnalysisService->calculateElasticity(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id']
        );

        return response()->json([
            'success' => true,
            'data' => $elasticity,
        ]);
    }

    /**
     * GET /api/workcore/pricing/demand-prediction
     */
    public function predict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
            'days' => 'integer|min:1|max:30',
        ]);

        $companyId = $request->user()->active_company_id;
        $prediction = $this->demandAnalysisService->predictDemand(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['days'] ?? 7
        );

        return response()->json([
            'success' => true,
            'data' => $prediction,
        ]);
    }

    /**
     * GET /api/workcore/pricing/demand-metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
        ]);

        $companyId = $request->user()->active_company_id;
        $metrics = $this->demandAnalysisService->getDemandMetrics(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id']
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /api/workcore/pricing/demand-insights
     */
    public function insights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|string',
            'resource_id' => 'required|integer',
        ]);

        $companyId = $request->user()->active_company_id;
        $insights = $this->demandAnalysisService->getInsights(
            $companyId,
            $validated['resource_type'],
            $validated['resource_id']
        );

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }
}
