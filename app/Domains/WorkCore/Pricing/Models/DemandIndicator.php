<?php

namespace App\Domains\WorkCore\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class DemandIndicator extends Model
{
    protected $table = 'workcore_demand_indicators';

    protected $fillable = [
        'public_id',
        'company_id',
        'resource_type',
        'resource_id',
        'booking_count',
        'search_count',
        'inquiry_count',
        'cancellation_rate',
        'booking_velocity',
        'demand_score',
        'demand_level',
        'recorded_at',
        'calculation_at',
        'factors',
        'metadata',
    ];

    protected $casts = [
        'factors' => 'json',
        'metadata' => 'json',
        'recorded_at' => 'datetime',
        'calculation_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (! $model->public_id) {
                $model->public_id = Str::ulid();
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

    /**
     * Record a booking event and update demand indicators
     */
    public function recordBooking(): self
    {
        $this->increment('booking_count');
        $this->calculateDemandScore();

        return $this;
    }

    /**
     * Record a search event and update demand indicators
     */
    public function recordSearch(): self
    {
        $this->increment('search_count');
        $this->calculateDemandScore();

        return $this;
    }

    /**
     * Record an inquiry event and update demand indicators
     */
    public function recordInquiry(): self
    {
        $this->increment('inquiry_count');
        $this->calculateDemandScore();

        return $this;
    }

    /**
     * Calculate demand score based on indicators
     */
    public function calculateDemandScore(): void
    {
        $score = 0;
        $factors = [];

        // Booking count factor (weight: 40%)
        $bookingFactor = min(($this->booking_count / 10) * 40, 40);
        $score += $bookingFactor;
        $factors['booking'] = $bookingFactor;

        // Search count factor (weight: 30%)
        $searchFactor = min(($this->search_count / 20) * 30, 30);
        $score += $searchFactor;
        $factors['search'] = $searchFactor;

        // Inquiry count factor (weight: 20%)
        $inquiryFactor = min(($this->inquiry_count / 15) * 20, 20);
        $score += $inquiryFactor;
        $factors['inquiry'] = $inquiryFactor;

        // Cancellation rate negative factor (weight: 10%)
        $cancellationFactor = (100 - $this->cancellation_rate) / 100 * 10;
        $score += $cancellationFactor;
        $factors['cancellation'] = $cancellationFactor;

        $this->demand_score = min(max($score, 0), 100);
        $this->factors = $factors;
        $this->calculation_at = now();

        // Set demand level
        $this->demand_level = match (true) {
            $this->demand_score < 25 => 'low',
            $this->demand_score < 50 => 'normal',
            $this->demand_score < 75 => 'high',
            default => 'critical',
        };

        $this->save();
    }

    /**
     * Get the latest demand indicator for a resource
     */
    public static function getLatest(string $companyId, string $resourceType, int $resourceId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Get demand trend over time
     */
    public static function getTrend(string $companyId, string $resourceType, int $resourceId, int $days = 30): array
    {
        $indicators = static::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();

        return $indicators->map(fn ($indicator) => [
            'date' => $indicator->recorded_at->toDateString(),
            'score' => $indicator->demand_score,
            'level' => $indicator->demand_level,
            'bookings' => $indicator->booking_count,
        ])->all();
    }

    /**
     * Scope: by demand level
     */
    public function scopeByDemandLevel($query, string $level)
    {
        return $query->where('demand_level', $level);
    }

    /**
     * Scope: high demand
     */
    public function scopeHighDemand($query)
    {
        return $query->where('demand_score', '>=', 75);
    }

    /**
     * Scope: low demand
     */
    public function scopeLowDemand($query)
    {
        return $query->where('demand_score', '<', 25);
    }
}
