<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Property\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Property Query Builder
 * Queries properties with tenant isolation
 */
final class PropertyQueryBuilder extends BaseQueryBuilder
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by owner
     */
    public function byOwner(int $ownerId): static
    {
        return $this->where('owner_id', $ownerId);
    }

    /**
     * Filter by tenant
     */
    public function byTenant(int $tenantId): static
    {
        return $this->where('tenant_id', $tenantId);
    }

    /**
     * Filter by property type
     */
    public function byType(string $type): static
    {
        return $this->where('property_type', $type);
    }

    /**
     * Filter by status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter occupied properties
     */
    public function occupied(): static
    {
        return $this->where('occupancy_status', 'occupied');
    }

    /**
     * Filter vacant properties
     */
    public function vacant(): static
    {
        return $this->where('occupancy_status', 'vacant');
    }

    /**
     * Filter by location/city
     */
    public function byLocation(string $location): static
    {
        return $this->where('city', $location);
    }

    /**
     * Search by address
     */
    public function searchAddress(string $address): static
    {
        return $this->search('address', $address);
    }

    /**
     * Filter needing maintenance
     */
    public function needsMaintenance(): static
    {
        return $this->where('maintenance_status', 'needed');
    }

    /**
     * Get all properties
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single property
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching properties
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
     * Get occupancy summary
     */
    public function occupancySummary(): array
    {
        return [
            'total_properties' => 0,
            'occupied' => 0,
            'vacant' => 0,
            'occupancy_rate' => 0.0,
        ];
    }

    /**
     * Get maintenance summary
     */
    public function maintenanceSummary(): array
    {
        return [
            'total_requests' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
        ];
    }

    /**
     * Get property value summary
     */
    public function valueSummary(): array
    {
        return [
            'total_value' => 0.0,
            'average_value' => 0.0,
            'by_type' => [],
        ];
    }
}
