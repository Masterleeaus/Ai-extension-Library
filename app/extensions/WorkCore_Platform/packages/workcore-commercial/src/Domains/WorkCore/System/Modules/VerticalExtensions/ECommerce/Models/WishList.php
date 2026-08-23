<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishList extends Model
{
    protected $table = 'ecommerce_wish_lists';

    protected $fillable = [
        'company_id',
        'customer_id',
        'product_id',
        'list_name',
        'is_public',
        'price_at_save',
        'current_price',
        'added_date',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'price_at_save' => 'float',
        'current_price' => 'float',
        'added_date' => 'date',
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
