<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Workforce\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Performance Query Builder
 * Queries employee performance reviews and KPIs
 */
final class PerformanceQueryBuilder extends BaseQueryBuilder
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by employee
     */
    public function byEmployee(int $employeeId): static
    {
        return $this->where('employee_id', $employeeId);
    }

    /**
     * Filter by review period/year
     */
    public function byYear(int $year): static
    {
        return $this->where('review_year', $year);
    }

    /**
     * Filter by review date range
     */
    public function reviewedBetween(string $startDate, string $endDate): static
    {
        return $this->whereBetween('review_date', $startDate, $endDate);
    }

    /**
     * Filter by rating
     */
    public function byRating(float $rating): static
    {
        return $this->where('overall_rating', $rating);
    }

    /**
     * Filter high performers (rating >= 4)
     */
    public function highPerformers(): static
    {
        return $this->where('overall_rating', 4, '>=');
    }

    /**
     * Filter low performers (rating <= 2)
     */
    public function lowPerformers(): static
    {
        return $this->where('overall_rating', 2, '<=');
    }

    /**
     * Filter by reviewer/manager
     */
    public function byReviewer(int $reviewerId): static
    {
        return $this->where('reviewer_id', $reviewerId);
    }

    /**
     * Filter by department
     */
    public function byDepartment(string $department): static
    {
        return $this->where('department', $department);
    }

    /**
     * Get all performance reviews
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single review
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching reviews
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * Get paginated results
     */
    public function paginate(): array
    {
        $total = $this->count();
        $items = $this->get();

        return [
            'data' => $items,
            'total' => $total,
            'limit' => $this->limit ?? 20,
            'offset' => $this->offset,
        ];
    }

    /**
     * Get average rating by department
     */
    public function departmentAverages(): array
    {
        return [];
    }

    /**
     * Get KPI summary
     */
    public function kpiSummary(): array
    {
        return [
            'quality' => 0.0,
            'productivity' => 0.0,
            'customer_satisfaction' => 0.0,
            'timeliness' => 0.0,
            'teamwork' => 0.0,
        ];
    }

    /**
     * Get performance trend for employee
     */
    public function performanceTrend(int $employeeId, int $years = 3): array
    {
        return [];
    }

    /**
     * Check if review due
     */
    public function isDue(int $employeeId): bool
    {
        $lastReview = (new static($this->tenantContext))
            ->byEmployee($employeeId)
            ->sortBy('review_date', 'DESC')
            ->limit(1)
            ->first();

        if (!$lastReview) {
            return true; // No review yet
        }

        // Check if last review was > 12 months ago
        $lastReviewDate = strtotime($lastReview['review_date']);
        return (time() - $lastReviewDate) > (365 * 24 * 60 * 60);
    }
}
