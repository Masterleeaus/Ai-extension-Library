<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'tenant_id',
        'customer_id',
        'customer_email',
        'cart_id',
        'status',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'shipping_address',
        'billing_address',
        'payment_method',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_address' => AsCollection::class,
        'billing_address' => AsCollection::class,
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function fulfillment(): HasOne
    {
        return $this->hasOne(OrderFulfillment::class, 'order_id');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(ShoppingCart::class, 'cart_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('Ymd');
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function calculateTax(): float
    {
        return (float) ($this->subtotal * 0.10); // Default 10% tax
    }

    public function calculateShipping(): float
    {
        return (float) ($this->subtotal > 100 ? 0 : 15); // Free shipping over $100
    }

    public function process(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if ($this->payment_status !== 'paid') {
            return false;
        }

        $this->update(['status' => 'confirmed']);

        return true;
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['shipped', 'delivered'])) {
            return false;
        }

        $this->update(['status' => 'cancelled']);

        return true;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function getStatusLabel(): string
    {
        $labels = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }
}
