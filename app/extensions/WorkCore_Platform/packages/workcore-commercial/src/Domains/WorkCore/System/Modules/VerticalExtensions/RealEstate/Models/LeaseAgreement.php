<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseAgreement extends Model
{
    protected $table = 'real_estate_lease_agreements';

    protected $fillable = [
        'property_listing_id',
        'tenant_id',
        'lease_start_date',
        'lease_end_date',
        'monthly_rent',
        'security_deposit',
        'lease_terms',
        'tenant_signature_url',
        'landlord_signature_url',
        'status',
        'document_url',
        'metadata',
    ];

    protected $casts = [
        'lease_start_date' => 'date',
        'lease_end_date' => 'date',
        'monthly_rent' => 'float',
        'security_deposit' => 'float',
        'lease_terms' => 'json',
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
