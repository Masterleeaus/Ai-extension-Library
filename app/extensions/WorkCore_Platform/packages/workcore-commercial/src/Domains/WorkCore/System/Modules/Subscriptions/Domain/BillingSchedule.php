<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingSchedule extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'next_billing_date',
        'amount',
        'status',
        'retry_count',
        'last_retry_at',
        'error_message',
        'invoice_id',
    ];

    protected $casts = [
        'next_billing_date' => 'datetime',
        'last_retry_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected $table = 'billing_schedules';

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
            'status' => 'processed',
            'invoice_id' => $transactionId,
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\BillingCycleProcessed($this));
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\BillingCycleFailed($this, $errorMessage));
    }

    /**
     * Retry the payment
     */
    public function retry(): bool
    {
        if ($this->retry_count >= 3) {
            return false;
        }

        $this->update([
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);

        return true;
    }

    /**
     * Generate invoice for this billing schedule
     */
    public function generateInvoice(): SubscriptionInvoice
    {
        $invoice = SubscriptionInvoice::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant_id,
            'subscription_id' => $this->subscription_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $this->amount,
            'tax' => 0,
            'total' => $this->amount,
            'due_date' => $this->next_billing_date->addDays(30)->toDateString(),
            'status' => 'issued',
        ]);

        $this->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Y') . '-' . str_pad($this->id ?? 0, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to pending billings
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('next_billing_date', '<=', now());
    }

    /**
     * Scope to failed billings
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed')
            ->where('retry_count', '<', 3);
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
