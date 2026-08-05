<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipTier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'features_json',
        'price',
        'currency',
        'billing_cycle',
        'cycle_days',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
        'features_json' => 'array',
    ];

    protected $table = 'membership_tiers';

    /**
     * Get all subscriptions for this tier
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tier_id');
    }

    /**
     * Get pricing information
     */
    public function getPricing(): array
    {
        return [
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_cycle' => $this->billing_cycle,
            'cycle_days' => $this->cycle_days,
        ];
    }

    /**
     * Get features for this tier
     */
    public function getFeatures(): array
    {
        return $this->features_json ?? [];
    }

    /**
     * Calculate proration for mid-cycle changes
     */
    public function calculateProration(
        \DateTime $originalEndDate,
        \DateTime $changeDate,
        int $daysCycleOriginal,
        int $daysCycleNew
    ): float {
        $daysUsed = (int) $changeDate->diff(new \DateTime())->d;
        $daysRemaining = max(0, $daysCycleOriginal - $daysUsed);

        $proratedAmount = ($this->price / $daysCycleNew) * $daysRemaining;

        return round($proratedAmount, 2);
    }

    /**
     * Scope to active tiers
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope by tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
