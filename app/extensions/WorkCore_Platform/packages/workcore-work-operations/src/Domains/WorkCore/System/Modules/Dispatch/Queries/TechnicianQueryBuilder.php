<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Dispatch\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Technician Query Builder
 * Queries technician availability and qualifications
 */
final class TechnicianQueryBuilder extends BaseQueryBuilder
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by territory
     */
    public function byTerritory(int $territoryId): static
    {
        return $this->where('territory_id', $territoryId);
    }

    /**
     * Filter by skill/specialization
     */
    public function bySkill(string $skill): static
    {
        $this->filters[] = [
            'field' => 'skills',
            'value' => $skill,
            'operator' => 'CONTAINS',
        ];
        return $this;
    }

    /**
     * Filter by status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter available technicians
     */
    public function available(): static
    {
        return $this->where('status', 'available');
    }

    /**
     * Filter busy technicians
     */
    public function busy(): static
    {
        return $this->where('status', 'busy');
    }

    /**
     * Filter on-break technicians
     */
    public function onBreak(): static
    {
        return $this->where('status', 'break');
    }

    /**
     * Filter technicians with minimum certification level
     */
    public function qualified(array $requiredSkills): static
    {
        // Check technician has all required skills
        $this->filters[] = [
            'field' => 'skills',
            'value' => $requiredSkills,
            'operator' => 'CONTAINS_ALL',
        ];
        return $this;
    }

    /**
     * Filter active/employed technicians
     */
    public function active(): static
    {
        return $this->where('employment_status', 'active');
    }

    /**
     * Filter by certification/license
     */
    public function byCertification(string $certification): static
    {
        $this->filters[] = [
            'field' => 'certifications',
            'value' => $certification,
            'operator' => 'CONTAINS',
        ];
        return $this;
    }

    /**
     * Get all technicians
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single technician
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching technicians
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
     * Get technician workload summary
     */
    public function workloadSummary(): array
    {
        return [
            'scheduled_jobs' => 0,
            'in_progress_jobs' => 0,
            'total_hours_today' => 0.0,
            'available_hours' => 0.0,
        ];
    }

    /**
     * Get skills inventory
     */
    public function skillsInventory(): array
    {
        return [
            'plumbing' => 0,
            'electrical' => 0,
            'hvac' => 0,
            'gas' => 0,
            'other' => 0,
        ];
    }

    /**
     * Find best technician for job type and location
     */
    public function bestMatch(string $jobType, int $territoryId): ?array
    {
        return (new static($this->tenantContext))
            ->bySkill($jobType)
            ->byTerritory($territoryId)
            ->available()
            ->sortBy('workload', 'ASC') // Least busy first
            ->limit(1)
            ->first();
    }

    /**
     * Get technician rating/performance
     */
    public function performanceSummary(): array
    {
        return [
            'average_rating' => 0.0,
            'customer_satisfaction' => 0.0,
            'first_time_fix_rate' => 0.0,
            'on_time_rate' => 0.0,
        ];
    }
}
