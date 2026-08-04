<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Workforce\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Attendance Query Builder
 * Queries attendance records for employees
 */
final class AttendanceQueryBuilder extends BaseQueryBuilder
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
     * Filter by date range
     */
    public function betweenDates(string $startDate, string $endDate): static
    {
        return $this->whereBetween('attendance_date', $startDate, $endDate);
    }

    /**
     * Filter by attendance status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter present
     */
    public function present(): static
    {
        return $this->where('status', 'present');
    }

    /**
     * Filter absent
     */
    public function absent(): static
    {
        return $this->where('status', 'absent');
    }

    /**
     * Filter late arrivals
     */
    public function late(): static
    {
        return $this->where('arrival_time', 'start_time', '>')
                    ->where('status', 'present');
    }

    /**
     * Filter early departures
     */
    public function early(): static
    {
        return $this->where('departure_time', 'end_time', '<')
                    ->where('status', 'present');
    }

    /**
     * Filter overtime records
     */
    public function overtime(): static
    {
        return $this->where('hours_worked', 8, '>');
    }

    /**
     * Filter by department
     */
    public function byDepartment(string $department): static
    {
        return $this->where('department', $department);
    }

    /**
     * Get all attendance records
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single record
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching records
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
     * Get attendance summary by status
     */
    public function summaryByStatus(): array
    {
        return [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'early' => 0,
            'on_leave' => 0,
        ];
    }

    /**
     * Get overtime summary
     */
    public function overtimeSummary(): array
    {
        return [
            'total_overtime_hours' => 0.0,
            'employees_with_overtime' => 0,
            'overtime_cost' => 0.0,
        ];
    }

    /**
     * Get attendance rate for period
     */
    public function attendanceRate(): float
    {
        return 0.0; // percentage
    }

    /**
     * Check if employee has shift today
     */
    public function hasScheduleToday(int $employeeId): bool
    {
        $today = date('Y-m-d');
        return (new static($this->tenantContext))
            ->byEmployee($employeeId)
            ->betweenDates($today, $today)
            ->count() > 0;
    }
}
