<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Salons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgram extends Model
{
    protected $table = 'salons_loyalty_programs';

    protected $fillable = [
        'company_id',
        'customer_id',
        'program_name',
        'tier_level',
        'points_balance',
        'points_earned',
        'points_redeemed',
        'rewards_claimed',
        'enrollment_date',
        'last_activity_date',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'points_earned' => 'integer',
        'points_redeemed' => 'integer',
        'enrollment_date' => 'date',
        'last_activity_date' => 'date',
        'is_active' => 'boolean',
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
}
