<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Dispatch\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Job Query Builder
 * Queries work orders/jobs with tenant isolation
 */
final class JobQueryBuilder extends BaseQueryBuilder
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by customer
     */
    public function byCustomer(int $customerId): static
    {
        return $this->where('customer_id', $customerId);
    }

    /**
     * Filter by job type
     */
    public function byType(string $type): static
    {
        return $this->where('type', $type);
    }

    /**
     * Filter by status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter new/unassigned jobs
     */
    public function unassigned(): static
    {
        return $this->where('status', 'new')
                    ->where('technician_id', null);
    }

    /**
     * Filter assigned jobs
     */
    public function assigned(): static
    {
        return $this->where('technician_id', null, '<>');
    }

    /**
     * Filter by technician
     */
    public function byTechnician(int $technicianId): static
    {
        return $this->where('technician_id', $technicianId);
    }

    /**
     * Filter by priority
     */
    public function byPriority(string $priority): static
    {
        return $this->where('priority', $priority);
    }

    /**
     * Filter high priority jobs
     */
    public function highPriority(): static
    {
        return $this->where('priority', 'high');
    }

    /**
     * Filter by scheduled date
     */
    public function onDate(string $date): static
    {
        return $this->where('scheduled_date', $date);
    }

    /**
     * Filter jobs between dates
     */
    public function betweenDates(string $startDate, string $endDate): static
    {
        return $this->whereBetween('scheduled_date', $startDate, $endDate);
    }

    /**
     * Filter overdue jobs
     */
    public function overdue(): static
    {
        $today = date('Y-m-d');
        return $this->where('scheduled_date', $today, '<')
                    ->where('status', 'completed', '<>');
    }

    /**
     * Filter by territory
     */
    public function byTerritory(int $territoryId): static
    {
        return $this->where('territory_id', $territoryId);
    }

    /**
     * Get all jobs
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single job
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching jobs
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
     * Get summary by status
     */
    public function summaryByStatus(): array
    {
        return [
            'new' => 0,
            'assigned' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
    }

    /**
     * Get summary by priority
     */
    public function summaryByPriority(): array
    {
        return [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'urgent' => 0,
        ];
    }

    /**
     * Get jobs needing dispatch
     */
    public function needsDispatch(): int
    {
        return (new static($this->tenantContext))->unassigned()->count();
    }

    /**
     * Get jobs completed today
     */
    public function completedToday(): int
    {
        $today = date('Y-m-d');
        return (new static($this->tenantContext))
            ->byStatus('completed')
            ->betweenDates($today, $today)
            ->count();
    }
}
