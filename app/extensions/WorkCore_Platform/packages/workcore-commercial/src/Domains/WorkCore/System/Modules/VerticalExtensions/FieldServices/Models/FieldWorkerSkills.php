<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldWorkerSkills extends Model
{
    protected $table = 'field_services_worker_skills';

    protected $fillable = [
        'worker_id',
        'company_id',
        'skill_category',
        'certification_name',
        'certification_number',
        'issue_date',
        'expiry_date',
        'is_verified',
        'verified_by',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
