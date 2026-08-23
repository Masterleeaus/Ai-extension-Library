<?php

namespace WorkCore\Subscriptions\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionCycle extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'cycle_number',
        'start_date',
        'end_date',
        'amount_paid',
        'prorated_amount',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount_paid' => 'decimal:2',
        'prorated_amount' => 'decimal:2',
    ];

    protected $table = 'subscription_cycles';

    /**
     * Get the subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the duration in days
     */
    public function getDurationInDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get the days used in cycle
     */
    public function getDaysUsed(): int
    {
        $now = now()->toDateString();

        if ($now > $this->end_date->toDateString()) {
            return $this->getDurationInDays();
        }

        if ($now < $this->start_date->toDateString()) {
            return 0;
        }

        return (int) $this->start_date->diffInDays($now);
    }

    /**
     * Get the days remaining
     */
    public function getDaysRemaining(): int
    {
        return max(0, $this->getDurationInDays() - $this->getDaysUsed());
    }

    /**
     * Check if cycle is active
     */
    public function isActive(): bool
    {
        $now = now()->toDateString();

        return $now >= $this->start_date->toDateString() && $now <= $this->end_date->toDateString();
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to active cycles
     */
    public function scopeActive($query)
    {
        return $query->whereRaw('? BETWEEN start_date AND end_date', [now()->toDateString()]);
    }
}
