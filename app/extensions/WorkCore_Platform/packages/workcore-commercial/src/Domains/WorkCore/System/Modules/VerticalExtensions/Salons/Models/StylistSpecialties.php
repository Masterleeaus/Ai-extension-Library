<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Salons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StylistSpecialties extends Model
{
    protected $table = 'salons_stylist_specialties';

    protected $fillable = [
        'worker_id',
        'company_id',
        'specialty_name',
        'certification_level',
        'years_of_experience',
        'service_categories',
        'hourly_rate',
        'is_available',
        'metadata',
    ];

    protected $casts = [
        'years_of_experience' => 'integer',
        'service_categories' => 'json',
        'hourly_rate' => 'float',
        'is_available' => 'boolean',
        'metadata' => 'json',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo('App\Models\Worker');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }
}
