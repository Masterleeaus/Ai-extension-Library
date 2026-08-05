<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantScreening extends Model
{
    protected $table = 'real_estate_tenant_screenings';

    protected $fillable = [
        'property_listing_id',
        'applicant_id',
        'application_date',
        'status',
        'background_check',
        'credit_report',
        'references',
        'income_verification',
        'employment_verification',
        'screening_score',
        'approved_by',
        'approval_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'application_date' => 'date',
        'background_check' => 'json',
        'credit_report' => 'json',
        'references' => 'json',
        'income_verification' => 'json',
        'employment_verification' => 'json',
        'screening_score' => 'float',
        'approval_date' => 'date',
        'metadata' => 'json',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer', 'applicant_id');
    }
}
