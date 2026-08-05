<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'tier_id',
        'status',
        'started_at',
        'expires_at',
        'cancelled_at',
        'paused_at',
        'trial_ends_at',
        'is_trial',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paused_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'is_trial' => 'boolean',
    ];

    protected $table = 'subscriptions';

    /**
     * Get the membership tier
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'tier_id');
    }

    /**
     * Get all billing schedules for this subscription
     */
    public function billingSchedules(): HasMany
    {
        return $this->hasMany(BillingSchedule::class);
    }

    /**
     * Get all cycles for this subscription
     */
    public function cycles(): HasMany
    {
        return $this->hasMany(SubscriptionCycle::class);
    }

    /**
     * Get all usage limits
     */
    public function usageLimits(): HasMany
    {
        return $this->hasMany(UsageLimit::class);
    }

    /**
     * Get all invoices
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if subscription is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if subscription is in trial
     */
    public function isInTrial(): bool
    {
        return $this->is_trial && (!$this->trial_ends_at || $this->trial_ends_at->isFuture());
    }

    /**
     * Upgrade to a new tier with proration
     */
    public function upgrade(MembershipTier $newTier, string $reason = null): void
    {
        if ($newTier->price <= $this->tier->price) {
            throw new \InvalidArgumentException('Cannot upgrade to a cheaper tier');
        }

        // Calculate proration
        $today = now();
        $currentCycle = $this->cycles()->orderByDesc('id')->first();

        if ($currentCycle && $currentCycle->end_date > $today) {
            $daysUsedInCycle = $today->diffInDays($currentCycle->start_date);
            $totalDaysInCycle = $currentCycle->start_date->diffInDays($currentCycle->end_date);
            $daysRemaining = $totalDaysInCycle - $daysUsedInCycle;

            $proratedAmount = ($newTier->price / $totalDaysInCycle) * $daysRemaining;
            $oldProratedAmount = ($this->tier->price / $totalDaysInCycle) * $daysRemaining;
            $creditAmount = $proratedAmount - $oldProratedAmount;

            // Log the upgrade event
            event(new \WorkCore\Subscriptions\Domain\Events\SubscriptionUpgraded($this, $newTier, $creditAmount));
        }

        $this->update([
            'tier_id' => $newTier->id,
        ]);
    }

    /**
     * Downgrade to a new tier with proration
     */
    public function downgrade(MembershipTier $newTier, string $reason = null): void
    {
        if ($newTier->price >= $this->tier->price) {
            throw new \InvalidArgumentException('Cannot downgrade to a more expensive tier');
        }

        // Calculate proration and credits
        $today = now();
        $currentCycle = $this->cycles()->orderByDesc('id')->first();

        if ($currentCycle && $currentCycle->end_date > $today) {
            $daysUsedInCycle = $today->diffInDays($currentCycle->start_date);
            $totalDaysInCycle = $currentCycle->start_date->diffInDays($currentCycle->end_date);
            $daysRemaining = $totalDaysInCycle - $daysUsedInCycle;

            $proratedAmount = ($newTier->price / $totalDaysInCycle) * $daysRemaining;
            $oldProratedAmount = ($this->tier->price / $totalDaysInCycle) * $daysRemaining;
            $creditAmount = $oldProratedAmount - $proratedAmount;

            // Log the downgrade event
            event(new \WorkCore\Subscriptions\Domain\Events\SubscriptionDowngraded($this, $newTier, $creditAmount));
        }

        $this->update([
            'tier_id' => $newTier->id,
        ]);
    }

    /**
     * Pause the subscription
     */
    public function pause(string $reason = null): void
    {
        if (!$this->isActive()) {
            throw new \InvalidArgumentException('Cannot pause a non-active subscription');
        }

        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\SubscriptionPaused($this, $reason));
    }

    /**
     * Resume the subscription
     */
    public function resume(): void
    {
        if (!$this->isPaused()) {
            throw new \InvalidArgumentException('Cannot resume a non-paused subscription');
        }

        $this->update([
            'status' => 'active',
            'paused_at' => null,
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\SubscriptionResumed($this));
    }

    /**
     * Cancel the subscription
     */
    public function cancel(string $reason = null, bool $refund = false): void
    {
        if ($this->isCancelled()) {
            throw new \InvalidArgumentException('Subscription is already cancelled');
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\SubscriptionCancelled($this, $reason, $refund));
    }

    /**
     * Scope by tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
