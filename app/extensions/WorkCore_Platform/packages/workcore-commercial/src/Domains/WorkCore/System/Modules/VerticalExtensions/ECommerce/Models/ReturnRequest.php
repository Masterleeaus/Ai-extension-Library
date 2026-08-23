<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    protected $table = 'ecommerce_return_requests';

    protected $fillable = [
        'company_id',
        'order_id',
        'customer_id',
        'return_reason',
        'return_date',
        'expected_return_date',
        'actual_return_date',
        'refund_amount',
        'refund_status',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'return_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
        'refund_amount' => 'float',
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
