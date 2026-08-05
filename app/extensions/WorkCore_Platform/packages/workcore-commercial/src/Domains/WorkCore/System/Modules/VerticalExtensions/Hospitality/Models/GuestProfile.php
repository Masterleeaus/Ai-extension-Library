<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestProfile extends Model
{
    protected $table = 'hospitality_guest_profiles';

    protected $fillable = [
        'company_id',
        'customer_id',
        'preferences',
        'allergies',
        'dietary_restrictions',
        'special_requests',
        'language_preference',
        'communication_preference',
        'loyalty_program_id',
        'guest_type',
        'metadata',
    ];

    protected $casts = [
        'preferences' => 'json',
        'allergies' => 'json',
        'dietary_restrictions' => 'json',
        'special_requests' => 'json',
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

    public function stays(): HasMany
    {
        return $this->hasMany(AccommodationStay::class, 'guest_id', 'customer_id');
    }
}
