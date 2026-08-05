<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\HireRental\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalAgreement extends Model
{
    protected $table = 'hire_rental_rental_agreements';

    protected $fillable = [
        'company_id',
        'customer_id',
        'rental_item_id',
        'rental_start_date',
        'rental_end_date',
        'daily_rate',
        'total_rental_days',
        'total_rental_price',
        'security_deposit_amount',
        'insurance_amount',
        'terms_and_conditions',
        'customer_signature_url',
        'company_signature_url',
        'status',
        'document_url',
        'metadata',
    ];

    protected $casts = [
        'rental_start_date' => 'date',
        'rental_end_date' => 'date',
        'daily_rate' => 'float',
        'total_rental_days' => 'integer',
        'total_rental_price' => 'float',
        'security_deposit_amount' => 'float',
        'insurance_amount' => 'float',
        'terms_and_conditions' => 'json',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer');
    }
}
