<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelInventory extends Model
{
    protected $table = 'channel_inventory';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'product_id',
        'available_qty',
        'reserved_qty',
        'channel_qty',
        'last_sync',
        'sync_metadata',
    ];

    protected $casts = [
        'available_qty' => 'integer',
        'reserved_qty' => 'integer',
        'channel_qty' => 'integer',
        'last_sync' => 'datetime',
        'sync_metadata' => 'json',
    ];

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    // Methods
    public function updateAvailable(int $quantity): void
    {
        $this->update([
            'available_qty' => $quantity,
            'last_sync' => now(),
        ]);
    }

    public function reserve(int $quantity): bool
    {
        if ($this->available_qty >= $quantity) {
            $this->update([
                'reserved_qty' => $this->reserved_qty + $quantity,
                'available_qty' => $this->available_qty - $quantity,
            ]);
            return true;
        }
        return false;
    }

    public function release(int $quantity): void
    {
        $this->update([
            'reserved_qty' => max(0, $this->reserved_qty - $quantity),
            'available_qty' => $this->available_qty + $quantity,
        ]);
    }

    public function getNetStock(): int
    {
        return $this->available_qty + $this->reserved_qty;
    }

    public function getPendingStock(): int
    {
        return $this->reserved_qty;
    }

    public function preventOverselling(int $requiredQty): bool
    {
        return $this->available_qty >= $requiredQty;
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByChannel($query, int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeByProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeLowStock($query, int $threshold = 5)
    {
        return $query->where('available_qty', '<', $threshold);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('available_qty', '<=', 0);
    }
}
