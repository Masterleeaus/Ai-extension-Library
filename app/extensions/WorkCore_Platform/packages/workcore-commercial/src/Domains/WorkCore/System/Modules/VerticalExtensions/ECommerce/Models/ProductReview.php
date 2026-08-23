<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $table = 'ecommerce_product_reviews';

    protected $fillable = [
        'company_id',
        'product_id',
        'customer_id',
        'rating',
        'title',
        'comment',
        'verified_purchase',
        'helpful_count',
        'unhelpful_count',
        'status',
        'moderated_by',
        'moderation_notes',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
        'unhelpful_count' => 'integer',
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
