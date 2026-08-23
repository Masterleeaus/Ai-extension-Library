<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationStay extends Model
{
    protected $table = 'hospitality_accommodation_stays';

    protected $fillable = [
        'company_id',
        'room_inventory_id',
        'guest_id',
        'check_in_date',
        'check_out_date',
        'guest_count',
        'price_per_night',
        'total_price',
        'tax_amount',
        'status',
        'channel_source',
        'reservation_id',
        'metadata',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'guest_count' => 'integer',
        'price_per_night' => 'float',
        'total_price' => 'float',
        'tax_amount' => 'float',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(RoomInventory::class, 'room_inventory_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer', 'guest_id');
    }
}
