<?php

namespace App\Domains\WorkCore\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PriceHistory extends Model
{
    protected $table = 'workcore_price_history';

    protected $fillable = [
        'public_id',
        'company_id',
        'resource_type',
        'resource_id',
        'original_price',
        'adjusted_price',
        'adjustment_amount',
        'adjustment_percentage',
        'reason',
        'rule_details',
        'factors',
        'effective_from',
        'effective_to',
        'status',
        'applied_by_user_id',
        'metadata',
    ];

    protected $casts = [
        'rule_details' => 'json',
        'factors' => 'json',
        'metadata' => 'json',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (! $model->public_id) {
                $model->public_id = Str::ulid();
            }

            if (! $model->adjustment_amount) {
                $model->adjustment_amount = $model->adjusted_price - $model->original_price;
            }

            if (! $model->adjustment_percentage) {
                $originalPrice = $model->original_price ?? 1;
                $model->adjustment_percentage = (($model->adjusted_price - $originalPrice) / $originalPrice) * 100;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'applied_by_user_id');
    }

    /**
     * Track a price change
     */
    public static function trackPrice(
        string $companyId,
        string $resourceType,
        int $resourceId,
        float $originalPrice,
        float $adjustedPrice,
        string $reason,
        array $ruleDetails = [],
        array $factors = [],
        ?int $appliedByUserId = null
    ): self {
        $history = new static();
        $history->company_id = $companyId;
        $history->resource_type = $resourceType;
        $history->resource_id = $resourceId;
        $history->original_price = $originalPrice;
        $history->adjusted_price = $adjustedPrice;
        $history->adjustment_amount = $adjustedPrice - $originalPrice;
        $history->adjustment_percentage = (($adjustedPrice - $originalPrice) / $originalPrice) * 100;
        $history->reason = $reason;
        $history->rule_details = $ruleDetails;
        $history->factors = $factors;
        $history->effective_from = now();
        $history->status = 'active';
        $history->applied_by_user_id = $appliedByUserId;

        $history->save();

        return $history;
    }

    /**
     * Get price change history for a resource
     */
    public static function getPriceChangeHistory(string $companyId, string $resourceType, int $resourceId, int $days = 30): array
    {
        return static::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($history) => [
                'date' => $history->created_at->toDateString(),
                'original_price' => $history->original_price,
                'adjusted_price' => $history->adjusted_price,
                'adjustment_percentage' => $history->adjustment_percentage,
                'reason' => $history->reason,
            ])->all();
    }

    /**
     * Calculate average price over a period
     */
    public static function getAveragePrice(string $companyId, string $resourceType, int $resourceId, int $days = 30): float
    {
        return static::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status', 'active')
            ->avg('adjusted_price') ?? 0;
    }

    /**
     * Get price change reasons summary
     */
    public static function getReasonSummary(string $companyId, string $resourceType, int $resourceId): array
    {
        return static::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->groupBy('reason')
            ->selectRaw('reason, COUNT(*) as count, AVG(adjustment_percentage) as avg_adjustment')
            ->get()
            ->keyBy('reason')
            ->map(fn ($item) => [
                'count' => $item->count,
                'average_adjustment' => $item->avg_adjustment,
            ])->all();
    }

    /**
     * Expire old price history
     */
    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
            'effective_to' => now(),
        ]);
    }

    /**
     * Scope: active prices
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: by reason
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope: recent changes
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
