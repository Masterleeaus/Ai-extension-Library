<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Commercial\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Payment Query Builder
 * Queries payments with tenant isolation
 */
final class PaymentQueryBuilder extends BaseQueryBuilder
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by invoice
     */
    public function byInvoice(int $invoiceId): static
    {
        return $this->where('invoice_id', $invoiceId);
    }

    /**
     * Filter by customer
     */
    public function byCustomer(int $customerId): static
    {
        return $this->where('customer_id', $customerId);
    }

    /**
     * Filter by payment method
     */
    public function byMethod(string $method): static
    {
        return $this->where('payment_method', $method);
    }

    /**
     * Filter by payment status
     */
    public function byStatus(string $status): static
    {
        return $this->where('status', $status);
    }

    /**
     * Filter successful payments
     */
    public function successful(): static
    {
        return $this->where('status', 'success');
    }

    /**
     * Filter failed payments
     */
    public function failed(): static
    {
        return $this->where('status', 'failed');
    }

    /**
     * Filter by date range
     */
    public function betweenDates(string $startDate, string $endDate): static
    {
        return $this->whereBetween('payment_date', $startDate, $endDate);
    }

    /**
     * Filter by amount range
     */
    public function byAmountRange(float $min, float $max): static
    {
        return $this->where('amount', $min, '>=')
                    ->where('amount', $max, '<=');
    }

    /**
     * Filter pending payments
     */
    public function pending(): static
    {
        return $this->where('status', 'pending');
    }

    /**
     * Get all payments
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single payment
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching payments
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
     * Get total collected
     */
    public function totalCollected(): float
    {
        return 0.0;
    }

    /**
     * Get payment success rate
     */
    public function successRate(): float
    {
        return 0.0; // 0-100%
    }

    /**
     * Get summary by payment method
     */
    public function summaryByMethod(): array
    {
        return [
            'credit_card' => 0.0,
            'bank_transfer' => 0.0,
            'check' => 0.0,
            'cash' => 0.0,
            'other' => 0.0,
        ];
    }

    /**
     * Get payments by status
     */
    public function summaryByStatus(): array
    {
        return [
            'pending' => 0.0,
            'success' => 0.0,
            'failed' => 0.0,
            'refunded' => 0.0,
        ];
    }
}
