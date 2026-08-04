<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Workforce\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Certification Query Builder
 * Queries employee certifications and compliance
 */
final class CertificationQueryBuilder extends BaseQueryBuilder
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
     * Filter by certification type
     */
    public function byType(string $type): static
    {
        return $this->where('certification_type', $type);
    }

    /**
     * Filter by status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter current/valid certifications
     */
    public function current(): static
    {
        return $this->where('status', 'current');
    }

    /**
     * Filter expired certifications
     */
    public function expired(): static
    {
        return $this->where('status', 'expired');
    }

    /**
     * Filter expiring soon (30 days)
     */
    public function expiring(): static
    {
        $today = date('Y-m-d');
        $thirtyDays = date('Y-m-d', strtotime('+30 days'));
        return $this->where('status', 'current')
                    ->where('expiration_date', $today, '>=')
                    ->where('expiration_date', $thirtyDays, '<=');
    }

    /**
     * Filter by expiration date range
     */
    public function expiringBetween(string $startDate, string $endDate): static
    {
        return $this->whereBetween('expiration_date', $startDate, $endDate)
                    ->where('status', 'current');
    }

    /**
     * Get all certifications
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single certification
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching certifications
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
     * Get compliance summary
     */
    public function complianceSummary(): array
    {
        return [
            'total_employees' => 0,
            'fully_compliant' => 0,
            'non_compliant' => 0,
            'expiring_soon' => 0,
            'compliance_rate' => 0.0,
        ];
    }

    /**
     * Get expiring alerts
     */
    public function expiringAlerts(int $days = 30): array
    {
        return (new static($this->tenantContext))
            ->expiringBetween(date('Y-m-d'), date('Y-m-d', strtotime("+{$days} days")))
            ->sortBy('expiration_date', 'ASC')
            ->get();
    }

    /**
     * Check if employee is compliant
     */
    public function isCompliant(int $employeeId): bool
    {
        $expired = (new static($this->tenantContext))
            ->byEmployee($employeeId)
            ->expired()
            ->count();
        return $expired === 0;
    }
}
