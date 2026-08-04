<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Expansion\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Customer Query Builder
 * Provides fluent interface for querying customers with tenant isolation
 *
 * Example:
 *   $customers = (new CustomerQueryBuilder($tenantContext))
 *       ->where('status', 'active')
 *       ->search('name', 'Acme')
 *       ->sortBy('created_at', 'DESC')
 *       ->limit(20)
 *       ->paginate();
 */
final class CustomerQueryBuilder extends BaseQueryBuilder
{
    private $repository; // Would be injected in real implementation

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by customer status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter by customer type (prospect, active, churned, etc)
     */
    public function byType(string $type): static
    {
        return $this->where('type', $type);
    }

    /**
     * Filter by industry
     */
    public function byIndustry(string $industry): static
    {
        return $this->where('industry', $industry);
    }

    /**
     * Filter by revenue range
     */
    public function byRevenueRange(float $min, float $max): static
    {
        return $this->where('annual_revenue', $min, '>=')
                    ->where('annual_revenue', $max, '<=');
    }

    /**
     * Filter by creation date range
     */
    public function createdBetween(string $startDate, string $endDate): static
    {
        return $this->whereBetween('created_at', $startDate, $endDate);
    }

    /**
     * Filter by assigned territory
     */
    public function byTerritory(int $territoryId): static
    {
        return $this->where('territory_id', $territoryId);
    }

    /**
     * Filter by assigned account manager
     */
    public function byAccountManager(int $userId): static
    {
        return $this->where('account_manager_id', $userId);
    }

    /**
     * Filter by customer segments
     */
    public function bySegment(string $segment): static
    {
        return $this->where('segment', $segment);
    }

    /**
     * Search by name or email
     */
    public function searchByNameOrEmail(string $query): static
    {
        // This would require more complex OR logic
        // For now, just search by name
        return $this->search('name', $query);
    }

    /**
     * Filter by tagging (has specific tag)
     */
    public function withTag(string $tag): static
    {
        // Would join to tags table
        $this->filters[] = [
            'field' => 'tags',
            'value' => $tag,
            'operator' => 'CONTAINS',
        ];
        return $this;
    }

    /**
     * Get all customers
     */
    public function get(): array
    {
        // Execution would happen here with proper database query
        // Returns array of CustomerDTO
        return [];
    }

    /**
     * Get single customer
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching customers
     */
    public function count(): int
    {
        // Execute count query
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
            'page' => $this->offset > 0 ? ($this->offset / ($this->limit ?? 20)) + 1 : 1,
            'pages' => (int)ceil($total / ($this->limit ?? 20)),
        ];
    }

    /**
     * Load related contacts for each customer (eager loading)
     */
    public function with(string $relation): static
    {
        // Would implement eager loading
        $this->filters[] = [
            'relation' => $relation,
            'eager' => true,
        ];
        return $this;
    }

    /**
     * Get customer summary statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_customers' => $this->count(),
            'total_revenue' => 0, // Sum of annual_revenue
            'by_status' => [], // Count grouped by status
            'by_industry' => [], // Count grouped by industry
            'by_segment' => [], // Count grouped by segment
        ];
    }
}
