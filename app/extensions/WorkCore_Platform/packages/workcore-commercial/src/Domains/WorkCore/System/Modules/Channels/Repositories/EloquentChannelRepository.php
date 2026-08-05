<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Repositories;

use App\Domains\WorkCore\System\Modules\Channels\Contracts\ChannelRepositoryContract;
use App\Domains\WorkCore\System\Modules\Channels\Models\Channel;
use App\Domains\WorkCore\System\Modules\Channels\Services\{
    ChannelService,
    InventorySyncService,
    OrderSyncService,
    PricingSyncService,
};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentChannelRepository implements ChannelRepositoryContract
{
    public function __construct(
        private ChannelService $channelService,
        private InventorySyncService $inventorySyncService,
        private OrderSyncService $orderSyncService,
        private PricingSyncService $pricingSyncService,
    ) {}

    public function registerChannel(array $data, int $tenantId, int $actorId): array
    {
        $channel = $this->channelService->registerChannel($data, $tenantId, $actorId);

        return [
            'id' => $channel->id,
            'tenant_id' => $channel->tenant_id,
            'name' => $channel->name,
            'type' => $channel->type,
            'enabled' => $channel->enabled,
            'auth_status' => $channel->auth_status,
            'authenticated_at' => $channel->authenticated_at,
            'created_at' => $channel->created_at,
        ];
    }

    public function updateChannel(string $channelId, array $data, int $tenantId, int $actorId): array
    {
        $channel = $this->getChannel($channelId, $tenantId);
        $updated = $this->channelService->updateChannel($channel, $data, $tenantId, $actorId);

        return [
            'id' => $updated->id,
            'tenant_id' => $updated->tenant_id,
            'name' => $updated->name,
            'type' => $updated->type,
            'enabled' => $updated->enabled,
            'auth_status' => $updated->auth_status,
            'updated_at' => $updated->updated_at,
        ];
    }

    public function disconnectChannel(string $channelId, int $tenantId, int $actorId): bool
    {
        $channel = $this->getChannel($channelId, $tenantId);
        return $this->channelService->disconnectChannel($channel, $tenantId, $actorId);
    }

    public function listChannels(int $tenantId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->channelService->listChannels($tenantId, $filters, $perPage);
    }

    public function testConnection(string $channelId, int $tenantId): array
    {
        $channel = $this->getChannel($channelId, $tenantId);
        return $this->channelService->testConnection($channel);
    }

    public function authenticateChannel(string $channelId, array $credentials, int $tenantId): bool
    {
        $channel = $this->getChannel($channelId, $tenantId);
        return $this->channelService->authenticateChannel($channel, $credentials);
    }

    public function getChannelById(string $channelId, int $tenantId): ?array
    {
        $channel = $this->getChannel($channelId, $tenantId);

        if (!$channel) {
            return null;
        }

        return [
            'id' => $channel->id,
            'tenant_id' => $channel->tenant_id,
            'name' => $channel->name,
            'type' => $channel->type,
            'enabled' => $channel->enabled,
            'auth_status' => $channel->auth_status,
            'authenticated_at' => $channel->authenticated_at,
            'last_sync_at' => $channel->last_sync_at,
            'settings' => $channel->settings,
            'created_at' => $channel->created_at,
            'updated_at' => $channel->updated_at,
        ];
    }

    public function syncInventory(string $channelId, int $tenantId): array
    {
        $channel = $this->getChannel($channelId, $tenantId);
        return $this->inventorySyncService->syncInventory($channel);
    }

    public function syncOrders(string $channelId, int $tenantId): array
    {
        $channel = $this->getChannel($channelId, $tenantId);
        return $this->orderSyncService->importOrders($channel);
    }

    public function syncPricing(string $channelId, int $tenantId): array
    {
        $channel = $this->getChannel($channelId, $tenantId);
        return $this->pricingSyncService->syncPrices($channel);
    }

    private function getChannel(string $channelId, int $tenantId): ?Channel
    {
        return Channel::where('id', $channelId)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
