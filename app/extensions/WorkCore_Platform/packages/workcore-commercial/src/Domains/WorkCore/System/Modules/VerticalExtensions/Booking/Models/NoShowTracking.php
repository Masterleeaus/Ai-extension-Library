<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoShowTracking extends Model
{
    protected $table = 'booking_no_show_tracking';

    protected $fillable = [
        'company_id',
        'customer_id',
        'reservation_id',
        'expected_datetime',
        'no_show_datetime',
        'cancellation_status',
        'no_show_reason',
        'no_show_count_customer',
        'penalty_amount',
        'penalty_applied',
        'metadata',
    ];

    protected $casts = [
        'expected_datetime' => 'datetime',
        'no_show_datetime' => 'datetime',
        'no_show_count_customer' => 'integer',
        'penalty_amount' => 'float',
        'penalty_applied' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer');
    }
}
