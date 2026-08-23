<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomInventory extends Model
{
    protected $table = 'hospitality_room_inventories';

    protected $fillable = [
        'company_id',
        'room_number',
        'room_type',
        'floor',
        'capacity',
        'amenities',
        'features',
        'base_price',
        'tax_rate',
        'cleaning_cost',
        'is_available',
        'status',
        'metadata',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'amenities' => 'json',
        'features' => 'json',
        'base_price' => 'float',
        'tax_rate' => 'float',
        'cleaning_cost' => 'float',
        'is_available' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(AccommodationStay::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CleaningSchedule::class);
    }
}
