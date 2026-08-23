<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationReminder extends Model
{
    protected $table = 'booking_reservation_reminders';

    protected $fillable = [
        'company_id',
        'reservation_id',
        'customer_id',
        'reminder_type',
        'reminder_time_minutes_before',
        'scheduled_send_time',
        'sent_time',
        'communication_channel',
        'status',
        'is_sent',
        'metadata',
    ];

    protected $casts = [
        'reminder_time_minutes_before' => 'integer',
        'scheduled_send_time' => 'datetime',
        'sent_time' => 'datetime',
        'is_sent' => 'boolean',
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
