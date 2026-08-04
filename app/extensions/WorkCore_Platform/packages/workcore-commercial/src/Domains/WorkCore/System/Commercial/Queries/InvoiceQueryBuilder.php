<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Commercial\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Invoice Query Builder
 * Queries invoices with tenant isolation and filtering
 */
final class InvoiceQueryBuilder extends BaseQueryBuilder
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
     * Filter by invoice status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter unpaid invoices
     */
    public function unpaid(): static
    {
        return $this->where('payment_status', 'unpaid');
    }

    /**
     * Filter paid invoices
     */
    public function paid(): static
    {
        return $this->where('payment_status', 'paid');
    }

    /**
     * Filter overdue invoices
     */
    public function overdue(): static
    {
        $today = date('Y-m-d');
        return $this->where('due_date', $today, '<')
                    ->where('payment_status', 'unpaid');
    }

    /**
     * Filter by date range
     */
    public function betweenDates(string $startDate, string $endDate): static
    {
        return $this->whereBetween('invoice_date', $startDate, $endDate);
    }

    /**
     * Filter by amount range
     */
    public function byAmountRange(float $min, float $max): static
    {
        return $this->where('total_amount', $min, '>=')
                    ->where('total_amount', $max, '<=');
    }

    /**
     * Filter by invoice number
     */
    public function byInvoiceNumber(string $invoiceNumber): static
    {
        return $this->where('invoice_number', $invoiceNumber);
    }

    /**
     * Get all invoices
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single invoice
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching invoices
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
            'draft' => 0,
            'sent' => 0,
            'viewed' => 0,
            'partial' => 0,
            'paid' => 0,
            'overdue' => 0,
        ];
    }

    /**
     * Get total outstanding amount
     */
    public function totalOutstanding(): float
    {
        return 0.0;
    }

    /**
     * Get total collected
     */
    public function totalCollected(): float
    {
        return 0.0;
    }

    /**
     * Get aging report (invoices by age)
     */
    public function agingReport(): array
    {
        return [
            'current' => 0.0,          // Due within 30 days
            'thirty_plus' => 0.0,      // 30-60 days overdue
            'sixty_plus' => 0.0,       // 60-90 days overdue
            'ninety_plus' => 0.0,      // 90+ days overdue
            'total' => 0.0,
        ];
    }
}
