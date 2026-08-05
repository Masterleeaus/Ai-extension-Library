<?php

declare(strict_types=1);


declare(straight_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityRules extends Model
{
    protected $table = 'booking_availability_rules';

    protected $fillable = [
        'company_id',
        'resource_id',
        'rule_type',
        'capacity_limit',
        'buffer_time_minutes',
        'prep_time_minutes',
        'blocked_dates',
        'blocked_times',
        'advance_booking_days',
        'max_booking_duration',
        'min_booking_duration',
        'rules',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'capacity_limit' => 'integer',
        'buffer_time_minutes' => 'integer',
        'prep_time_minutes' => 'integer',
        'blocked_dates' => 'json',
        'blocked_times' => 'json',
        'advance_booking_days' => 'integer',
        'max_booking_duration' => 'integer',
        'min_booking_duration' => 'integer',
        'rules' => 'json',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }
}
