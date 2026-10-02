<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotEcommerce\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommerceBooking extends Model
{
    protected $table = 'ext_chatbot_bookings';

    protected $guarded = ['id'];

    protected $casts = [
        'customer_details' => 'array',
        'metadata' => 'array',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(CommerceBookingSlot::class, 'slot_id');
    }
}
