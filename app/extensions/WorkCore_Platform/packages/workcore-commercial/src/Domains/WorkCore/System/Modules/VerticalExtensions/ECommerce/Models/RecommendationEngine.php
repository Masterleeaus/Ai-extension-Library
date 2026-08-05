<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationEngine extends Model
{
    protected $table = 'ecommerce_recommendation_engines';

    protected $fillable = [
        'company_id',
        'customer_id',
        'product_id',
        'recommendation_type',
        'score',
        'reason',
        'algorithm_used',
        'is_clicked',
        'is_purchased',
        'metadata',
    ];

    protected $casts = [
        'score' => 'float',
        'is_clicked' => 'boolean',
        'is_purchased' => 'boolean',
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
