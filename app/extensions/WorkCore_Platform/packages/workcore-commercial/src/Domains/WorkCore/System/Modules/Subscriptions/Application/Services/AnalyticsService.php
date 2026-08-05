<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Application\Services;

use Illuminate\Support\Carbon;
use WorkCore\Subscriptions\Domain\Subscription;
use WorkCore\Subscriptions\Domain\SubscriptionInvoice;

class AnalyticsService
{
    /**
     * Calculate Monthly Recurring Revenue (MRR)
     */
    public function calculateMRR(string $tenantId, ?Carbon $date = null): float
    {
        $date = $date ?? now();

        $activeSubscriptions = Subscription::forTenant($tenantId)
            ->active()
            ->where(function ($query) use ($date) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $date);
            })
            ->get();

        return $activeSubscriptions->sum(function ($subscription) {
            return $this->convertToMonthlyAmount($subscription->tier->price, $subscription->tier->billing_cycle);
        });
    }

    /**
     * Calculate Annual Recurring Revenue (ARR)
     */
    public function calculateARR(string $tenantId, ?Carbon $date = null): float
    {
        return $this->calculateMRR($tenantId, $date) * 12;
    }

    /**
     * Calculate churn rate
     */
    public function calculateChurn(string $tenantId, int $month = null, int $year = null): float
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $startingSubscriptions = Subscription::forTenant($tenantId)
            ->where('started_at', '<', $startDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('cancelled_at')
                    ->orWhere('cancelled_at', '>=', $startDate);
            })
            ->count();

        if ($startingSubscriptions === 0) {
            return 0;
        }

        $cancelledSubscriptions = Subscription::forTenant($tenantId)
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->count();

        return round(($cancelledSubscriptions / $startingSubscriptions) * 100, 2);
    }

    /**
     * Calculate Customer Lifetime Value (CLV)
     */
    public function calculateCLV(string $tenantId): float
    {
        $subscriptions = Subscription::forTenant($tenantId)->get();
        $totalValue = 0;

        foreach ($subscriptions as $subscription) {
            $months = $subscription->started_at->diffInMonths($subscription->cancelled_at ?? now());
            $monthlyValue = $this->convertToMonthlyAmount(
                $subscription->tier->price,
                $subscription->tier->billing_cycle
            );
            $totalValue += $monthlyValue * $months;
        }

        return round($totalValue / count($subscriptions), 2);
    }

    /**
     * Perform cohort analysis
     */
    public function cohortAnalysis(string $tenantId, int $cohortMonths = 12): array
    {
        $cohorts = [];

        for ($i = $cohortMonths - 1; $i >= 0; $i--) {
            $cohortStart = now()->subMonths($i)->startOfMonth();
            $cohortEnd = $cohortStart->copy()->endOfMonth();

            $subscriptionsInCohort = Subscription::forTenant($tenantId)
                ->whereBetween('started_at', [$cohortStart, $cohortEnd])
                ->count();

            $activeInCohort = Subscription::forTenant($tenantId)
                ->whereBetween('started_at', [$cohortStart, $cohortEnd])
                ->active()
                ->count();

            $cohorts[$cohortStart->format('Y-m')] = [
                'total' => $subscriptionsInCohort,
                'active' => $activeInCohort,
                'retention_rate' => $subscriptionsInCohort > 0
                    ? round(($activeInCohort / $subscriptionsInCohort) * 100, 2)
                    : 0,
            ];
        }

        return $cohorts;
    }

    /**
     * Get subscription growth metrics
     */
    public function getGrowthMetrics(string $tenantId, int $months = 12): array
    {
        $metrics = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $newSubscriptions = Subscription::forTenant($tenantId)
                ->whereBetween('started_at', [$start, $end])
                ->count();

            $cancelledSubscriptions = Subscription::forTenant($tenantId)
                ->whereBetween('cancelled_at', [$start, $end])
                ->count();

            $metrics[$start->format('Y-m')] = [
                'new' => $newSubscriptions,
                'cancelled' => $cancelledSubscriptions,
                'net_growth' => $newSubscriptions - $cancelledSubscriptions,
            ];
        }

        return $metrics;
    }

    /**
     * Get revenue metrics
     */
    public function getRevenueMetrics(string $tenantId, int $months = 12): array
    {
        $metrics = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $revenue = SubscriptionInvoice::forTenant($tenantId)
                ->paid()
                ->whereBetween('paid_at', [$start, $end])
                ->sum('total');

            $metrics[$start->format('Y-m')] = [
                'total_revenue' => round($revenue, 2),
            ];
        }

        return $metrics;
    }

    /**
     * Convert billing cycle to monthly amount
     */
    private function convertToMonthlyAmount(float $price, string $billingCycle): float
    {
        return match ($billingCycle) {
            'monthly' => $price,
            'yearly' => $price / 12,
            'quarterly' => $price / 3,
            default => $price,
        };
    }

    /**
     * Get subscription tier distribution
     */
    public function getTierDistribution(string $tenantId): array
    {
        return Subscription::forTenant($tenantId)
            ->active()
            ->with('tier')
            ->get()
            ->groupBy('tier.name')
            ->map(fn ($subscriptions) => $subscriptions->count())
            ->toArray();
    }

    /**
     * Get top performing tiers
     */
    public function getTopTiers(string $tenantId, int $limit = 5): array
    {
        $tiers = [];

        $subscriptions = Subscription::forTenant($tenantId)
            ->active()
            ->with('tier')
            ->get()
            ->groupBy('tier_id');

        foreach ($subscriptions as $tierId => $tierSubscriptions) {
            $tier = $tierSubscriptions->first()->tier;
            $tiers[] = [
                'tier_name' => $tier->name,
                'count' => $tierSubscriptions->count(),
                'monthly_revenue' => $this->convertToMonthlyAmount($tier->price, $tier->billing_cycle) * $tierSubscriptions->count(),
            ];
        }

        usort($tiers, fn ($a, $b) => $b['monthly_revenue'] <=> $a['monthly_revenue']);

        return array_slice($tiers, 0, $limit);
    }
}
