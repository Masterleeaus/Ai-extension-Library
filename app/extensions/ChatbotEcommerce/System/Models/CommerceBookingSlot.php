<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotEcommerce\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommerceBookingSlot extends Model
{
    protected $table = 'ext_chatbot_booking_slots';

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
        'metadata' => 'array',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(CommerceBooking::class, 'slot_id');
    }

    public function availableCapacity(): int
    {
        return max(0, (int) $this->capacity - (int) $this->reserved_capacity);
    }
}
