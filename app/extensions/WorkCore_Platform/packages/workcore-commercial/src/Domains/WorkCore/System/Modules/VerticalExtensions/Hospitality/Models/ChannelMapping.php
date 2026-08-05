<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMapping extends Model
{
    protected $table = 'hospitality_channel_mappings';

    protected $fillable = [
        'company_id',
        'room_inventory_id',
        'channel_name',
        'channel_property_id',
        'is_active',
        'sync_enabled',
        'last_sync_at',
        'credentials',
        'rate_strategy',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sync_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
        'credentials' => 'encrypted:json',
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
}
