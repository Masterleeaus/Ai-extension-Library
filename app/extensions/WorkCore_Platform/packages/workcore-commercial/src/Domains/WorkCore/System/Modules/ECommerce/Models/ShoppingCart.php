<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShoppingCart extends Model
{
    protected $table = 'shopping_carts';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'session_id',
        'items_json',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'coupon_code',
        'expires_at',
    ];

    protected $casts = [
        'items_json' => AsCollection::class,
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'cart_id');
    }

    public function addItem(string $productId, int $quantity, float $price, ?string $variantId = null, ?array $attributes = null): CartItem
    {
        $item = $this->items()->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->save();
        } else {
            $item = $this->items()->create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'price_at_time' => $price,
                'attributes' => $attributes,
            ]);
        }

        $this->calculateTotals();

        return $item;
    }

    public function removeItem(int $itemId): bool
    {
        $result = $this->items()->find($itemId)?->delete() ?? false;
        $this->calculateTotals();
        return $result;
    }

    public function updateQuantity(int $itemId, int $quantity): CartItem
    {
        $item = $this->items()->findOrFail($itemId);
        $item->update(['quantity' => max(1, $quantity)]);
        $this->calculateTotals();
        return $item;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum(\Illuminate\Database\Query\Expression::raw('quantity * price_at_time'));

        $this->subtotal = $subtotal ?? 0;
        $this->total = $this->subtotal + $this->tax + $this->shipping - $this->discount;

        $this->save();
    }

    public function applyDiscount(float $discountAmount): void
    {
        $this->discount = max(0, min($discountAmount, $this->subtotal));
        $this->total = $this->subtotal + $this->tax + $this->shipping - $this->discount;
        $this->save();
    }

    public function applyCoupon(string $couponCode): bool
    {
        $coupon = Coupon::where('code', $couponCode)
            ->where('tenant_id', $this->tenant_id)
            ->where('active', true)
            ->firstOrFail();

        if ($coupon->isValid()) {
            $this->coupon_code = $couponCode;
            $discount = $coupon->discount_type === 'percentage'
                ? $this->subtotal * ($coupon->discount_value / 100)
                : $coupon->discount_value;

            $this->applyDiscount($discount);
            return true;
        }

        return false;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->subtotal = 0;
        $this->tax = 0;
        $this->shipping = 0;
        $this->discount = 0;
        $this->total = 0;
        $this->coupon_code = null;
        $this->save();
    }
}
