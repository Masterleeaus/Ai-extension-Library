<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceVisit extends Model
{
    protected $table = 'field_services_service_visits';

    protected $fillable = [
        'job_site_id',
        'worker_id',
        'appointment_id',
        'status',
        'scheduled_date',
        'arrival_time',
        'completion_time',
        'duration_minutes',
        'customer_signature_url',
        'notes',
        'customer_notes',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'arrival_time' => 'datetime',
        'completion_time' => 'datetime',
        'duration_minutes' => 'integer',
        'metadata' => 'json',
    ];

    public function jobSite(): BelongsTo
    {
        return $this->belongsTo(JobSite::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo('App\Models\Worker');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo('App\Models\Appointment');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ServiceVisitPhoto::class);
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(ServiceChecklist::class);
    }
}
