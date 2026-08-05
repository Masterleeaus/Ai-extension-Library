<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\HireRental\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceOption extends Model
{
    protected $table = 'hire_rental_insurance_options';

    protected $fillable = [
        'company_id',
        'insurance_name',
        'coverage_type',
        'daily_premium',
        'max_coverage_amount',
        'deductible_amount',
        'coverage_details',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'daily_premium' => 'float',
        'max_coverage_amount' => 'float',
        'deductible_amount' => 'float',
        'coverage_details' => 'json',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }
}
