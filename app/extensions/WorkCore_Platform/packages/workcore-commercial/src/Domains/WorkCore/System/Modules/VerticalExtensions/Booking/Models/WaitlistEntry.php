<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    protected $table = 'booking_waitlist_entries';

    protected $fillable = [
        'company_id',
        'customer_id',
        'resource_id',
        'desired_date',
        'desired_time',
        'customer_contact',
        'position_in_queue',
        'status',
        'added_date',
        'auto_filled_date',
        'metadata',
    ];

    protected $casts = [
        'desired_date' => 'date',
        'position_in_queue' => 'integer',
        'added_date' => 'datetime',
        'auto_filled_date' => 'datetime',
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
