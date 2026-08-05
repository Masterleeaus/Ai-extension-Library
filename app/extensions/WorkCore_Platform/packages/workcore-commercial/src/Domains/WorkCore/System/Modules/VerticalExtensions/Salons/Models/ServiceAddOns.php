<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Salons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceAddOns extends Model
{
    protected $table = 'salons_service_add_ons';

    protected $fillable = [
        'company_id',
        'add_on_name',
        'service_category',
        'description',
        'price',
        'duration_minutes',
        'product_required',
        'product_cost',
        'is_active',
        'order_priority',
        'metadata',
    ];

    protected $casts = [
        'price' => 'float',
        'duration_minutes' => 'integer',
        'product_cost' => 'float',
        'is_active' => 'boolean',
        'order_priority' => 'integer',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }
}
