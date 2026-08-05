<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Automotive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyTracking extends Model
{
    protected $table = 'automotive_warranty_tracking';

    protected $fillable = [
        'company_id',
        'service_record_id',
        'warranty_type',
        'warranty_period_months',
        'start_date',
        'end_date',
        'parts_covered',
        'labor_covered',
        'warranty_amount',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'warranty_period_months' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'parts_covered' => 'json',
        'labor_covered' => 'boolean',
        'warranty_amount' => 'float',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }
}
