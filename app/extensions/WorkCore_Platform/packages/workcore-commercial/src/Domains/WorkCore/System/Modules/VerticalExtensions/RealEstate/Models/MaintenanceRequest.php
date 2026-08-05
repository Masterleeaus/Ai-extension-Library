<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    protected $table = 'real_estate_maintenance_requests';

    protected $fillable = [
        'property_listing_id',
        'tenant_id',
        'request_type',
        'description',
        'severity',
        'status',
        'requested_date',
        'completed_date',
        'assigned_to',
        'estimated_cost',
        'actual_cost',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'completed_date' => 'date',
        'estimated_cost' => 'float',
        'actual_cost' => 'float',
        'metadata' => 'json',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer', 'tenant_id');
    }
}
