<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Services;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelInventory, ChannelMapping};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventorySyncService
{
    public function syncInventory(Channel $channel): array
    {
        $connector = $channel->getConnector();
        $result = $connector->syncInventory();

        if ($result['status'] === 'completed') {
            $channel->update(['last_sync_at' => now()]);
        }

        return $result;
    }

    public function preventOverselling(int $productId, int $requiredQty, int $channelId = null): bool
    {
        if ($channelId) {
            $inventory = ChannelInventory::where('product_id', $productId)
                ->where('channel_id', $channelId)
                ->first();

            return $inventory && $inventory->preventOverselling($requiredQty);
        }

        // Check across all channels for global inventory
        $totalAvailable = ChannelInventory::where('product_id', $productId)
            ->sum('available_qty');

        return $totalAvailable >= $requiredQty;
    }

    public function pushInventoryUpdate(Channel $channel, string $productId, int $quantity): array
    {
        try {
            $connector = $channel->getConnector();

            // Update channel inventory
            $inventory = ChannelInventory::firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'product_id' => $productId,
                ],
                [
                    'tenant_id' => $channel->tenant_id,
                    'available_qty' => 0,
                ]
            );

            $inventory->updateAvailable($quantity);

            return [
                'status' => 'success',
                'message' => 'Inventory updated successfully',
                'quantity' => $quantity,
            ];
        } catch (\Exception $e) {
            Log::error('Push inventory update failed', [
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

    public function pullInventoryState(Channel $channel): array
    {
        $inventory = ChannelInventory::where('channel_id', $channel->id)
            ->get()
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'available_qty' => $item->available_qty,
                'reserved_qty' => $item->reserved_qty,
                'total_qty' => $item->getNetStock(),
                'last_sync' => $item->last_sync,
            ])
            ->toArray();

        return [
            'channel_id' => $channel->id,
            'tenant_id' => $channel->tenant_id,
            'inventory_count' => count($inventory),
            'inventory' => $inventory,
            'last_sync_at' => $channel->last_sync_at,
        ];
    }

    public function reserveInventory(Channel $channel, string $productId, int $quantity): bool
    {
        return DB::transaction(function () use ($channel, $productId, $quantity) {
            $inventory = ChannelInventory::where('channel_id', $channel->id)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$inventory || !$inventory->preventOverselling($quantity)) {
                return false;
            }

            return $inventory->reserve($quantity);
        });
    }

    public function releaseInventory(Channel $channel, string $productId, int $quantity): void
    {
        $inventory = ChannelInventory::where('channel_id', $channel->id)
            ->where('product_id', $productId)
            ->first();

        if ($inventory) {
            $inventory->release($quantity);
        }
    }

    public function getInventoryStatus(int $channelId, string $productId = null): array
    {
        $query = ChannelInventory::where('channel_id', $channelId);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $items = $query->get();

        return [
            'channel_id' => $channelId,
            'total_items' => $items->count(),
            'total_available' => $items->sum('available_qty'),
            'total_reserved' => $items->sum('reserved_qty'),
            'out_of_stock' => $items->where('available_qty', '<=', 0)->count(),
            'low_stock' => $items->where('available_qty', '<', 5)->count(),
            'items' => $items->map(fn($item) => [
                'product_id' => $item->product_id,
                'available' => $item->available_qty,
                'reserved' => $item->reserved_qty,
                'total' => $item->getNetStock(),
            ])->toArray(),
        ];
    }

    public function syncInventoryAcrossChannels(int $tenantId, string $localProductId): array
    {
        $mappings = ChannelMapping::where('tenant_id', $tenantId)
            ->where('local_id', $localProductId)
            ->get();

        $results = [
            'product_id' => $localProductId,
            'channels_synced' => 0,
            'channels_failed' => 0,
            'details' => [],
        ];

        foreach ($mappings as $mapping) {
            try {
                $channel = Channel::find($mapping->channel_id);
                if (!$channel || !$channel->enabled) {
                    continue;
                }

                $this->syncInventory($channel);
                $results['channels_synced']++;
            } catch (\Exception $e) {
                $results['channels_failed']++;
                $results['details'][$mapping->channel_id] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
