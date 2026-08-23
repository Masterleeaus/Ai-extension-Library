<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningSchedule extends Model
{
    protected $table = 'hospitality_cleaning_schedules';

    protected $fillable = [
        'company_id',
        'room_inventory_id',
        'worker_id',
        'scheduled_date',
        'scheduled_time',
        'cleaning_type',
        'status',
        'notes',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
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

    public function worker(): BelongsTo
    {
        return $this->belongsTo('App\Models\Worker');
    }
}
