<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\HireRental\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageAssessment extends Model
{
    protected $table = 'hire_rental_damage_assessments';

    protected $fillable = [
        'rental_agreement_id',
        'assessment_type',
        'assessment_date',
        'damage_description',
        'damage_photos',
        'estimated_repair_cost',
        'actual_repair_cost',
        'assessed_by',
        'status',
        'metadata',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'damage_photos' => 'json',
        'estimated_repair_cost' => 'float',
        'actual_repair_cost' => 'float',
        'metadata' => 'json',
    ];

    public function rentalAgreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class);
    }
}
