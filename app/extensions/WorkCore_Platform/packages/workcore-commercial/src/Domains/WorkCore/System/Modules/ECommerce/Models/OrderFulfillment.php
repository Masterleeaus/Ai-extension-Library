<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFulfillment extends Model
{
    protected $table = 'order_fulfillments';

    protected $fillable = [
        'order_id',
        'status',
        'shipping_method',
        'shipping_provider',
        'tracking_number',
        'tracking_url',
        'shipping_cost',
        'items',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'items' => AsCollection::class,
        'tracking_url' => AsCollection::class,
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function markAsShipped(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        $this->order->update(['status' => 'shipped']);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->order->update(['status' => 'delivered']);
    }

    public function getStatusLabel(): string
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'returned' => 'Returned',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }
}
