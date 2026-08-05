<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Automotive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianSkills extends Model
{
    protected $table = 'automotive_technician_skills';

    protected $fillable = [
        'worker_id',
        'company_id',
        'skill_name',
        'certification_level',
        'certification_issuer',
        'issue_date',
        'expiry_date',
        'verified',
        'years_of_experience',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified' => 'boolean',
        'years_of_experience' => 'integer',
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
