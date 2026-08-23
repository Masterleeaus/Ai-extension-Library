<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipTier extends Model
{
    protected $table = 'fitness_membership_tiers';

    protected $fillable = [
        'company_id',
        'tier_name',
        'tier_level',
        'monthly_price',
        'annual_price',
        'features',
        'class_access',
        'trainer_sessions_included',
        'gym_access_hours',
        'guest_passes_monthly',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'monthly_price' => 'float',
        'annual_price' => 'float',
        'features' => 'json',
        'class_access' => 'json',
        'trainer_sessions_included' => 'integer',
        'gym_access_hours' => 'json',
        'guest_passes_monthly' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany('App\Models\Membership');
    }
}
