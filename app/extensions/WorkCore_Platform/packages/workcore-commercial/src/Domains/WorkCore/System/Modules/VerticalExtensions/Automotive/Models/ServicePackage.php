<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Automotive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackage extends Model
{
    protected $table = 'automotive_service_packages';

    protected $fillable = [
        'company_id',
        'package_name',
        'package_type',
        'service_items',
        'base_price',
        'estimated_duration_hours',
        'warranty_period',
        'parts_included',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'service_items' => 'json',
        'base_price' => 'float',
        'estimated_duration_hours' => 'float',
        'parts_included' => 'json',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }
}
