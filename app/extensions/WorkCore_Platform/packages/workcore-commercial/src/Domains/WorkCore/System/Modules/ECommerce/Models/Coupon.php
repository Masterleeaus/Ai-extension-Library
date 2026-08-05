<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class Coupon extends Model
{
    protected $table = 'coupons';

    protected $fillable = [
        'tenant_id',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_value',
        'usage_limit',
        'usage_count',
        'per_customer_limit',
        'active',
        'valid_from',
        'valid_until',
        'applicable_products',
        'applicable_categories',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'usage_count' => 'integer',
        'usage_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'applicable_products' => AsCollection::class,
        'applicable_categories' => AsCollection::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canBeUsedByCustomer(string $customerId, int $previousUsage = 0): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->per_customer_limit && $previousUsage >= $this->per_customer_limit) {
            return false;
        }

        return true;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getDiscountAmount(float $orderTotal): float
    {
        if ($this->min_order_value && $orderTotal < $this->min_order_value) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return $orderTotal * ($this->discount_value / 100);
        }

        return (float) $this->discount_value;
    }
}
