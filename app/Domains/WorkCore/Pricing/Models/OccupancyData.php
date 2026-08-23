<?php

namespace App\Domains\WorkCore\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class OccupancyData extends Model
{
    protected $table = 'workcore_occupancy_data';

    protected $fillable = [
        'public_id',
        'company_id',
        'resource_type',
        'resource_id',
        'current_occupancy',
        'capacity',
        'occupancy_percentage',
        'available_units',
        'reserved_units',
        'pending_bookings',
        'recorded_at',
        'occupancy_level',
        'forecast',
        'metadata',
    ];

    protected $casts = [
        'forecast' => 'json',
        'metadata' => 'json',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (! $model->public_id) {
                $model->public_id = Str::ulid();
            }

            // Calculate derived fields
            if ($model->capacity > 0) {
                $model->occupancy_percentage = ($model->current_occupancy / $model->capacity) * 100;
                $model->available_units = $model->capacity - $model->current_occupancy;
            }

            // Set occupancy level
            $percentage = $model->occupancy_percentage ?? 0;
            $model->occupancy_level = match (true) {
                $percentage < 30 => 'low',
                $percentage < 70 => 'normal',
                $percentage < 90 => 'high',
                default => 'critical',
            };
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
     * Record occupancy snapshot
     */
    public static function recordOccupancy(
        string $companyId,
        string $resourceType,
        int $resourceId,
        int $currentOccupancy,
        int $capacity,
        int $reservedUnits = 0,
        int $pendingBookings = 0
    ): self {
        $occupancy = new static();
        $occupancy->company_id = $companyId;
        $occupancy->resource_type = $resourceType;
        $occupancy->resource_id = $resourceId;
        $occupancy->current_occupancy = $currentOccupancy;
        $occupancy->capacity = $capacity;
        $occupancy->reserved_units = $reservedUnits;
        $occupancy->pending_bookings = $pendingBookings;
        $occupancy->recorded_at = now();

        $occupancy->save();

        return $occupancy;
    }

    /**
     * Get current occupancy percentage
     */
    public function getOccupancyPercentage(): float
    {
        if ($this->capacity === 0) {
            return 0;
        }

        return ($this->current_occupancy / $this->capacity) * 100;
    }

    /**
     * Get occupancy status
     */
    public function getOccupancyStatus(): string
    {
        return $this->occupancy_level;
    }

    /**
     * Check if occupancy is at critical level
     */
    public function isCritical(): bool
    {
        return $this->occupancy_level === 'critical';
    }

    /**
     * Get the latest occupancy for a resource
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
     * Get occupancy trend over time
     */
    public static function getTrend(string $companyId, string $resourceType, int $resourceId, int $days = 30): array
    {
        $data = static::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();

        return $data->map(fn ($occupancy) => [
            'date' => $occupancy->recorded_at->toDateString(),
            'current_occupancy' => $occupancy->current_occupancy,
            'capacity' => $occupancy->capacity,
            'occupancy_percentage' => $occupancy->occupancy_percentage,
            'level' => $occupancy->occupancy_level,
        ])->all();
    }

    /**
     * Get average occupancy over a period
     */
    public static function getAverageOccupancy(string $companyId, string $resourceType, int $resourceId, int $days = 30): float
    {
        return static::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->avg('occupancy_percentage') ?? 0;
    }

    /**
     * Scope: by occupancy level
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('occupancy_level', $level);
    }

    /**
     * Scope: high occupancy
     */
    public function scopeHighOccupancy($query)
    {
        return $query->where('occupancy_percentage', '>=', 70);
    }

    /**
     * Scope: low occupancy
     */
    public function scopeLowOccupancy($query)
    {
        return $query->where('occupancy_percentage', '<', 30);
    }

    /**
     * Scope: critical occupancy
     */
    public function scopeCritical($query)
    {
        return $query->where('occupancy_level', 'critical');
    }
}
