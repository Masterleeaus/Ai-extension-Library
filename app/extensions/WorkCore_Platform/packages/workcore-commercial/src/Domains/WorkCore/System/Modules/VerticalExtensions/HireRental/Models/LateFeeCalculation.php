<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\HireRental\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateFeeCalculation extends Model
{
    protected $table = 'hire_rental_late_fee_calculations';

    protected $fillable = [
        'rental_agreement_id',
        'return_date',
        'days_late',
        'daily_late_fee_rate',
        'total_late_fees',
        'max_late_fee_cap',
        'final_late_fee',
        'status',
        'metadata',
    ];

    protected $casts = [
        'return_date' => 'date',
        'days_late' => 'integer',
        'daily_late_fee_rate' => 'float',
        'total_late_fees' => 'float',
        'max_late_fee_cap' => 'float',
        'final_late_fee' => 'float',
        'metadata' => 'json',
    ];

    public function rentalAgreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class);
    }
}
