<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Services;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelPricing};
use Illuminate\Support\Facades\Log;

class PricingSyncService
{
    public function syncPrices(Channel $channel): array
    {
        $connector = $channel->getConnector();
        return $connector->syncPricing();
    }

    public function applyChannelRules(Channel $channel, string $productId, float $basePrice, array $rules = []): float
    {
        $pricing = ChannelPricing::where('channel_id', $channel->id)
            ->where('product_id', $productId)
            ->first();

        if (!$pricing) {
            return $basePrice;
        }

        $finalPrice = $basePrice;

        // Apply markup percentage if configured
        if (isset($rules['markup_percentage'])) {
            $finalPrice = $pricing->applyMarkup($rules['markup_percentage']);
        }

        // Apply discount percentage if configured
        if (isset($rules['discount_percentage'])) {
            $finalPrice = $pricing->applyDiscount($rules['discount_percentage']);
        }

        // Apply fixed markup if configured
        if (isset($rules['markup_fixed'])) {
            $finalPrice += $rules['markup_fixed'];
        }

        // Apply minimum price
        if (isset($rules['minimum_price'])) {
            $finalPrice = max($finalPrice, $rules['minimum_price']);
        }

        // Apply maximum price
        if (isset($rules['maximum_price'])) {
            $finalPrice = min($finalPrice, $rules['maximum_price']);
        }

        return round($finalPrice, 2);
    }

    public function pushPriceUpdates(Channel $channel, string $productId, float $price, string $currency = 'USD'): array
    {
        try {
            $pricing = ChannelPricing::firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'product_id' => $productId,
                ],
                [
                    'tenant_id' => $channel->tenant_id,
                    'currency' => $currency,
                ]
            );

            $pricing->updatePrice($price, $currency);

            return [
                'status' => 'success',
                'message' => 'Price updated successfully',
                'price' => $price,
                'currency' => $currency,
            ];
        } catch (\Exception $e) {
            Log::error('Push price update failed', [
                'channel_id' => $channel->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getPriceHistory(int $channelId, string $productId = null): array
    {
        $query = ChannelPricing::where('channel_id', $channelId);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $items = $query->get()
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'channel_price' => $item->channel_price,
                'local_price' => $item->local_price,
                'currency' => $item->currency,
                'percentage_diff' => $item->getPricePercentageDifference(),
                'last_sync' => $item->last_sync,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ])
            ->toArray();

        return [
            'channel_id' => $channelId,
            'product_id' => $productId,
            'items_count' => count($items),
            'items' => $items,
        ];
    }

    public function getPriceAnomalies(int $channelId, float $percentageThreshold = 10): array
    {
        $anomalies = ChannelPricing::where('channel_id', $channelId)
            ->changed()
            ->get()
            ->filter(function ($item) use ($percentageThreshold) {
                $diff = abs($item->getPricePercentageDifference());
                return $diff > $percentageThreshold;
            })
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'channel_price' => $item->channel_price,
                'local_price' => $item->local_price,
                'percentage_diff' => $item->getPricePercentageDifference(),
            ])
            ->values()
            ->toArray();

        return [
            'channel_id' => $channelId,
            'threshold_percentage' => $percentageThreshold,
            'anomalies_count' => count($anomalies),
            'anomalies' => $anomalies,
        ];
    }

    public function syncPricingForTenant(int $tenantId): array
    {
        $channels = Channel::where('tenant_id', $tenantId)
            ->enabled()
            ->get();

        $results = [
            'tenant_id' => $tenantId,
            'channels_processed' => 0,
            'total_prices_synced' => 0,
            'total_errors' => 0,
            'details' => [],
        ];

        foreach ($channels as $channel) {
            try {
                $syncResult = $this->syncPrices($channel);
                $results['channels_processed']++;
                $results['total_prices_synced'] += $syncResult['synced'] ?? 0;
                $results['total_errors'] += $syncResult['errors'] ?? 0;
                $results['details'][$channel->id] = $syncResult;
            } catch (\Exception $e) {
                Log::error('Failed to sync prices for channel', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage(),
                ]);
                $results['details'][$channel->id] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function compareChannelPrices(int $tenantId, string $productId): array
    {
        $channels = Channel::where('tenant_id', $tenantId)
            ->enabled()
            ->get();

        $pricingData = [];

        foreach ($channels as $channel) {
            $pricing = ChannelPricing::where('channel_id', $channel->id)
                ->where('product_id', $productId)
                ->first();

            if ($pricing) {
                $pricingData[$channel->type] = [
                    'channel_id' => $channel->id,
                    'price' => $pricing->channel_price,
                    'currency' => $pricing->currency,
                    'local_price' => $pricing->local_price,
                ];
            }
        }

        // Find lowest and highest prices
        $prices = array_column($pricingData, 'price');
        $lowestPrice = min($prices);
        $highestPrice = max($prices);

        return [
            'product_id' => $productId,
            'channels_count' => count($pricingData),
            'lowest_price' => $lowestPrice,
            'highest_price' => $highestPrice,
            'price_range' => $highestPrice - $lowestPrice,
            'pricing_data' => $pricingData,
        ];
    }
}
