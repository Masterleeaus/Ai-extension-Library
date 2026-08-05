<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'id',
        'tenant_id',
        'subscription_id',
        'cycle_id',
        'invoice_number',
        'amount',
        'tax',
        'total',
        'due_date',
        'paid_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'id' => 'string',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'subscription_invoices';

    /**
     * Get the subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(string $transactionId = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\InvoicePaid($this, $transactionId));
    }

    /**
     * Mark as overdue
     */
    public function markAsOverdue(): void
    {
        if ($this->status !== 'paid' && now()->isAfter($this->due_date)) {
            $this->update(['status' => 'overdue']);
        }
    }

    /**
     * Cancel the invoice
     */
    public function cancel(string $reason = null): void
    {
        if ($this->status === 'paid') {
            throw new \InvalidArgumentException('Cannot cancel a paid invoice');
        }

        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
        ]);
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && now()->isAfter($this->due_date);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->where('due_date', '<', now()->toDateString());
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
