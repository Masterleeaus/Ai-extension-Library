<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Query;

use App\Domains\WorkCore\System\Context\TenantContextSnapshot;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Base Query Builder for all WorkCore entities
 * Enforces tenant isolation and authorization on all queries
 */
abstract class BaseQueryBuilder
{
    protected array $filters = [];
    protected array $sortBy = [];
    protected ?int $limit = null;
    protected int $offset = 0;
    protected bool $includeSoftDeleted = false;

    public function __construct(
        protected TenantContext $tenantContext,
    ) {
        $this->validateTenant();
    }

    /**
     * Validate tenant context is set
     */
    protected function validateTenant(): void
    {
        if (!$this->tenantContext->hasTenant()) {
            throw new \RuntimeException('Tenant context not resolved');
        }
    }

    /**
     * Get current tenant context
     */
    protected function getTenant(): TenantContextSnapshot
    {
        return $this->tenantContext->snapshot();
    }

    /**
     * Add filter to query
     */
    public function where(string $field, mixed $value, string $operator = '='): static
    {
        $this->filters[] = [
            'field' => $field,
            'value' => $value,
            'operator' => $operator,
        ];
        return $this;
    }

    /**
     * Add IN filter
     */
    public function whereIn(string $field, array $values): static
    {
        if (empty($values)) {
            return $this;
        }
        $this->filters[] = [
            'field' => $field,
            'value' => $values,
            'operator' => 'IN',
        ];
        return $this;
    }

    /**
     * Add date range filter
     */
    public function whereBetween(string $field, string $startDate, string $endDate): static
    {
        $this->filters[] = [
            'field' => $field,
            'value' => [$startDate, $endDate],
            'operator' => 'BETWEEN',
        ];
        return $this;
    }

    /**
     * Add text search filter (LIKE)
     */
    public function search(string $field, string $query): static
    {
        $this->filters[] = [
            'field' => $field,
            'value' => "%{$query}%",
            'operator' => 'LIKE',
        ];
        return $this;
    }

    /**
     * Sort by field
     */
    public function sortBy(string $field, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException('Invalid sort direction: ' . $direction);
        }
        $this->sortBy[$field] = $direction;
        return $this;
    }

    /**
     * Set limit
     */
    public function limit(int $limit): static
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be > 0');
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set offset
     */
    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset must be >= 0');
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Include soft-deleted records
     */
    public function withTrashed(): static
    {
        $this->includeSoftDeleted = true;
        return $this;
    }

    /**
     * Only soft-deleted records
     */
    public function onlyTrashed(): static
    {
        $this->includeSoftDeleted = true;
        $this->filters[] = [
            'field' => 'deleted_at',
            'value' => null,
            'operator' => 'IS NOT',
        ];
        return $this;
    }

    /**
     * Get filter array for query execution
     */
    protected function getFilters(): array
    {
        // Always add tenant_id filter
        $filters = [[
            'field' => 'company_id',
            'value' => $this->getTenant()->companyId,
            'operator' => '=',
        ]];

        // Add soft delete filter if needed
        if (!$this->includeSoftDeleted) {
            $filters[] = [
                'field' => 'deleted_at',
                'value' => null,
                'operator' => 'IS',
            ];
        }

        return array_merge($filters, $this->filters);
    }

    /**
     * Execute query and return results
     */
    abstract public function get(): array;

    /**
     * Execute query and return single result
     */
    abstract public function first(): ?array;

    /**
     * Count total matching records
     */
    abstract public function count(): int;

    /**
     * Get paginated results
     */
    abstract public function paginate(): array;
}
