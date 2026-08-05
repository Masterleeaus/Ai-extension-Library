<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceVisitPhoto extends Model
{
    protected $table = 'field_services_service_visit_photos';

    protected $fillable = [
        'service_visit_id',
        'photo_type',
        'photo_url',
        'description',
        'latitude',
        'longitude',
        'timestamp',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'timestamp' => 'datetime',
    ];

    public function serviceVisit(): BelongsTo
    {
        return $this->belongsTo(ServiceVisit::class);
    }
}
