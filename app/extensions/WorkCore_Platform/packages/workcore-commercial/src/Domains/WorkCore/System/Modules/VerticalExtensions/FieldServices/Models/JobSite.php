<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSite extends Model
{
    protected $table = 'field_services_job_sites';

    protected $fillable = [
        'company_id',
        'customer_id',
        'address',
        'latitude',
        'longitude',
        'site_type',
        'access_instructions',
        'hazard_notes',
        'parking_instructions',
        'gate_code',
        'contact_name',
        'contact_phone',
        'site_active',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'site_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function serviceVisits(): HasMany
    {
        return $this->hasMany(ServiceVisit::class);
    }
}
