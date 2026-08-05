<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLimit extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'feature_slug',
        'limit_value',
        'usage_count',
        'reset_date',
    ];

    protected $casts = [
        'reset_date' => 'datetime',
    ];

    protected $table = 'usage_limits';

    /**
     * Get the subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(int $amount = 1): int
    {
        $this->increment('usage_count', $amount);
        $this->refresh();

        if ($this->isExceeded()) {
            event(new \WorkCore\Subscriptions\Domain\Events\UsageLimitExceeded($this));
        }

        return $this->usage_count;
    }

    /**
     * Check if limit is exceeded
     */
    public function isExceeded(): bool
    {
        return $this->usage_count > $this->limit_value;
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentage(): float
    {
        if ($this->limit_value === 0) {
            return 0;
        }

        return round(($this->usage_count / $this->limit_value) * 100, 2);
    }

    /**
     * Get remaining usage
     */
    public function getRemainingUsage(): int
    {
        return max(0, $this->limit_value - $this->usage_count);
    }

    /**
     * Reset usage count
     */
    public function reset(): void
    {
        $this->update([
            'usage_count' => 0,
            'reset_date' => now(),
        ]);
    }

    /**
     * Check if usage should be reset
     */
    public function shouldReset(): bool
    {
        if (!$this->reset_date) {
            return false;
        }

        return $this->reset_date->isPast();
    }

    /**
     * Scope by feature
     */
    public function scopeByFeature($query, $featureSlug)
    {
        return $query->where('feature_slug', $featureSlug);
    }

    /**
     * Scope to exceeded limits
     */
    public function scopeExceeded($query)
    {
        return $query->whereRaw('usage_count > limit_value');
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
