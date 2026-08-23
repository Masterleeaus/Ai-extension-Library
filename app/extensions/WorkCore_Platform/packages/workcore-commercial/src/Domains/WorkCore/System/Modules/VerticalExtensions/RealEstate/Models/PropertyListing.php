<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyListing extends Model
{
    protected $table = 'real_estate_property_listings';

    protected $fillable = [
        'company_id',
        'property_address',
        'property_type',
        'bedrooms',
        'bathrooms',
        'floor_area',
        'lot_size',
        'description',
        'features',
        'rental_price',
        'sale_price',
        'status',
        'listing_date',
        'expiry_date',
        'latitude',
        'longitude',
        'images',
        'metadata',
    ];

    protected $casts = [
        'bedrooms' => 'integer',
        'bathrooms' => 'decimal:1',
        'floor_area' => 'float',
        'lot_size' => 'float',
        'features' => 'json',
        'rental_price' => 'float',
        'sale_price' => 'float',
        'listing_date' => 'date',
        'expiry_date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'images' => 'json',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(TenantScreening::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(LeaseAgreement::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }
}
