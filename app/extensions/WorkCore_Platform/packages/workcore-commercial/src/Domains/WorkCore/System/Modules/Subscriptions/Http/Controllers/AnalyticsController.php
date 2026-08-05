<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use WorkCore\Subscriptions\Application\Services\AnalyticsService;

class AnalyticsController
{
    public function __construct(private AnalyticsService $analyticsService)
    {
    }

    /**
     * Get MRR metrics
     */
    public function getMRR(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : null;

        $mrr = $this->analyticsService->calculateMRR($tenantId, $date);
        $arr = $this->analyticsService->calculateARR($tenantId, $date);

        return response()->json([
            'mrr' => round($mrr, 2),
            'arr' => round($arr, 2),
            'date' => $date ?? now(),
        ]);
    }

    /**
     * Get churn rate
     */
    public function getChurn(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $churn = $this->analyticsService->calculateChurn($tenantId, $month, $year);

        return response()->json([
            'churn_rate' => $churn,
            'period' => "{$year}-{$month}",
        ]);
    }

    /**
     * Get customer lifetime value
     */
    public function getCLV(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        $clv = $this->analyticsService->calculateCLV($tenantId);

        return response()->json([
            'customer_lifetime_value' => $clv,
        ]);
    }

    /**
     * Get cohort analysis
     */
    public function getCohortAnalysis(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $months = $request->get('months', 12);

        $cohorts = $this->analyticsService->cohortAnalysis($tenantId, $months);

        return response()->json($cohorts);
    }

    /**
     * Get growth metrics
     */
    public function getGrowth(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $months = $request->get('months', 12);

        $metrics = $this->analyticsService->getGrowthMetrics($tenantId, $months);

        return response()->json($metrics);
    }

    /**
     * Get revenue metrics
     */
    public function getRevenue(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $months = $request->get('months', 12);

        $metrics = $this->analyticsService->getRevenueMetrics($tenantId, $months);

        return response()->json($metrics);
    }

    /**
     * Get tier distribution
     */
    public function getTierDistribution(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        $distribution = $this->analyticsService->getTierDistribution($tenantId);

        return response()->json($distribution);
    }

    /**
     * Get top tiers
     */
    public function getTopTiers(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');
        $limit = $request->get('limit', 5);

        $tiers = $this->analyticsService->getTopTiers($tenantId, $limit);

        return response()->json($tiers);
    }
}
