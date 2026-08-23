<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Services;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelOrder, ChannelMapping};
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    public function importOrders(Channel $channel): array
    {
        $connector = $channel->getConnector();
        return $connector->syncOrders();
    }

    public function pushOrderStatus(ChannelOrder $order, string $status): bool
    {
        try {
            $channel = $order->channel;
            $connector = $channel->getConnector();

            // Push status to channel
            $result = $connector->pushOrderStatus($order->order_id_remote, $status);

            if ($result) {
                $order->pushStatus($status);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to push order status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function resolveMappings(ChannelOrder $order): array
    {
        $mappings = ChannelMapping::where('channel_id', $order->channel_id)
            ->get()
            ->mapWithKeys(fn($mapping) => [
                $mapping->channel_id_remote => $mapping->local_id,
            ])
            ->toArray();

        return $mappings;
    }

    public function getOrdersByChannel(int $channelId, array $filters = []): array
    {
        $query = ChannelOrder::where('channel_id', $channelId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['synced'])) {
            if ($filters['synced']) {
                $query->synced();
            } else {
                $query->pending();
            }
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 25)->toArray();
    }

    public function getPendingOrders(int $channelId): array
    {
        $orders = ChannelOrder::where('channel_id', $channelId)
            ->pending()
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_id_remote' => $order->order_id_remote,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ])
            ->toArray();

        return [
            'channel_id' => $channelId,
            'pending_count' => count($orders),
            'orders' => $orders,
        ];
    }

    public function getOrdersWithErrors(int $channelId): array
    {
        $orders = ChannelOrder::where('channel_id', $channelId)
            ->withErrors()
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_id_remote' => $order->order_id_remote,
                'error_message' => $order->error_message,
                'created_at' => $order->created_at,
            ])
            ->toArray();

        return [
            'channel_id' => $channelId,
            'error_count' => count($orders),
            'orders' => $orders,
        ];
    }

    public function retryFailedOrders(int $channelId): array
    {
        $orders = ChannelOrder::where('channel_id', $channelId)
            ->withErrors()
            ->get();

        $results = [
            'total' => count($orders),
            'successful' => 0,
            'failed' => 0,
        ];

        foreach ($orders as $order) {
            try {
                $order->sync();
                $results['successful']++;
            } catch (\Exception $e) {
                $order->markError($e->getMessage());
                $results['failed']++;
            }
        }

        return $results;
    }

    public function linkOrderToLocal(ChannelOrder $channelOrder, string $localOrderId): void
    {
        $channelOrder->update([
            'local_order_id' => $localOrderId,
        ]);
    }

    public function getOrderDetails(ChannelOrder $order): array
    {
        return [
            'id' => $order->id,
            'channel_id' => $order->channel_id,
            'order_id_remote' => $order->order_id_remote,
            'local_order_id' => $order->local_order_id,
            'status' => $order->status,
            'order_data' => $order->getOrderData(),
            'synced_at' => $order->synced_at,
            'pushed_at' => $order->pushed_at,
            'error_message' => $order->error_message,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }

    public function syncOrdersForTenant(int $tenantId): array
    {
        $channels = Channel::where('tenant_id', $tenantId)
            ->enabled()
            ->get();

        $results = [
            'tenant_id' => $tenantId,
            'channels_processed' => 0,
            'total_orders_synced' => 0,
            'total_errors' => 0,
            'details' => [],
        ];

        foreach ($channels as $channel) {
            try {
                $syncResult = $this->importOrders($channel);
                $results['channels_processed']++;
                $results['total_orders_synced'] += $syncResult['synced'] ?? 0;
                $results['total_errors'] += $syncResult['errors'] ?? 0;
                $results['details'][$channel->id] = $syncResult;
            } catch (\Exception $e) {
                Log::error('Failed to sync orders for channel', [
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
}
