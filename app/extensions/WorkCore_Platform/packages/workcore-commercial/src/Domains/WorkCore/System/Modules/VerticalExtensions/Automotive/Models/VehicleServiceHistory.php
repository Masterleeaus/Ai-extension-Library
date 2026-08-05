<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Automotive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleServiceHistory extends Model
{
    protected $table = 'automotive_vehicle_service_history';

    protected $fillable = [
        'company_id',
        'customer_id',
        'vehicle_vin',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'mileage',
        'last_service_date',
        'service_records',
        'maintenance_schedule',
        'metadata',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'mileage' => 'integer',
        'service_records' => 'json',
        'maintenance_schedule' => 'json',
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

    public function serviceDetails(): HasMany
    {
        return $this->hasMany(ServicePackage::class);
    }
}
