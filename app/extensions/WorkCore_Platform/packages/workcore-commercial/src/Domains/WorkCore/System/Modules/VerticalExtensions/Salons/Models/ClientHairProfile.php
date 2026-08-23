<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Salons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientHairProfile extends Model
{
    protected $table = 'salons_client_hair_profiles';

    protected $fillable = [
        'company_id',
        'customer_id',
        'hair_type',
        'hair_texture',
        'hair_color',
        'color_history',
        'scalp_condition',
        'allergies',
        'preferences',
        'service_history',
        'preferred_stylist_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'color_history' => 'json',
        'allergies' => 'json',
        'preferences' => 'json',
        'service_history' => 'json',
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

    public function preferredStylist(): BelongsTo
    {
        return $this->belongsTo('App\Models\Worker', 'preferred_stylist_id');
    }
}
