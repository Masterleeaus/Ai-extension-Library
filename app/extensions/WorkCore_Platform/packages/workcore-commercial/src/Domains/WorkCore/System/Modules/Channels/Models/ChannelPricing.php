<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelPricing extends Model
{
    protected $table = 'channel_pricings';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'product_id',
        'channel_price',
        'currency',
        'local_price',
        'pricing_rules',
        'last_sync',
    ];

    protected $casts = [
        'channel_price' => 'decimal:2',
        'local_price' => 'decimal:2',
        'pricing_rules' => 'json',
        'last_sync' => 'datetime',
    ];

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    // Methods
    public function updatePrice(float $price, string $currency = 'USD'): void
    {
        $this->update([
            'channel_price' => $price,
            'currency' => $currency,
            'last_sync' => now(),
        ]);
    }

    public function applyMarkup(float $percentage): float
    {
        $markupPrice = $this->channel_price * (1 + ($percentage / 100));
        return round($markupPrice, 2);
    }

    public function applyDiscount(float $percentage): float
    {
        $discountPrice = $this->channel_price * (1 - ($percentage / 100));
        return round($discountPrice, 2);
    }

    public function hasChangedFromLocal(): bool
    {
        if (!$this->local_price) {
            return false;
        }
        return abs(floatval($this->channel_price) - floatval($this->local_price)) > 0.01;
    }

    public function getPricePercentageDifference(): float
    {
        if (!$this->local_price || $this->local_price == 0) {
            return 0;
        }
        $difference = floatval($this->channel_price) - floatval($this->local_price);
        $percentage = ($difference / floatval($this->local_price)) * 100;
        return round($percentage, 2);
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

    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopeChanged($query)
    {
        return $query->whereRaw('ABS(channel_price - COALESCE(local_price, 0)) > 0.01');
    }

    public function scopeOutOfSync($query, int $minutesThreshold = 60)
    {
        return $query->where('last_sync', '<', now()->subMinutes($minutesThreshold));
    }
}
