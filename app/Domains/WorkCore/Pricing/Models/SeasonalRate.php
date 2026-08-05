<?php

namespace App\Domains\WorkCore\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SeasonalRate extends Model
{
    use SoftDeletes;

    protected $table = 'workcore_seasonal_rates';

    protected $fillable = [
        'public_id',
        'company_id',
        'season_name',
        'description',
        'start_date',
        'end_date',
        'multiplier',
        'special_dates',
        'is_active',
        'season_type',
        'metadata',
        'created_by_user_id',
    ];

    protected $casts = [
        'special_dates' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    /**
     * Check if this season is currently active
     */
    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->toDateString();

        return $today >= $this->start_date->toDateString()
            && $today <= $this->end_date->toDateString();
    }

    /**
     * Check if a specific date is in this season
     */
    public function isDateInSeason(Carbon $date): bool
    {
        return $date->greaterThanOrEqualTo($this->start_date)
            && $date->lessThanOrEqualTo($this->end_date);
    }

    /**
     * Get the multiplier for a specific date
     */
    public function getMultiplier(Carbon $date = null): float
    {
        if (! $date) {
            $date = now();
        }

        // Check special dates first
        if ($this->special_dates) {
            $dateStr = $date->toDateString();
            foreach ($this->special_dates as $specialDate) {
                if ($specialDate['date'] === $dateStr) {
                    return $specialDate['multiplier'] ?? $this->multiplier;
                }
            }
        }

        return (float) $this->multiplier;
    }

    /**
     * Apply seasonal multiplier to a price
     */
    public function applyMultiplier(float $basePrice, Carbon $date = null): float
    {
        $multiplier = $this->getMultiplier($date);

        return $basePrice * $multiplier;
    }

    /**
     * Get all active seasons for a company on a specific date
     */
    public static function getActiveSeasonsForDate(string $companyId, Carbon $date): array
    {
        return static::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get()
            ->all();
    }

    /**
     * Get the combined multiplier from all applicable seasons (takes highest)
     */
    public static function getCombinedMultiplier(string $companyId, Carbon $date): float
    {
        $seasons = static::getActiveSeasonsForDate($companyId, $date);

        if (empty($seasons)) {
            return 1.0;
        }

        $multipliers = array_map(
            fn ($season) => $season->getMultiplier($date),
            $seasons
        );

        return max($multipliers);
    }

    /**
     * Scope: active seasons only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: current seasons
     */
    public function scopeCurrent($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Scope: by season type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('season_type', $type);
    }
}
