<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ChannelRepositoryContract
{
    public function registerChannel(array $data, int $tenantId, int $actorId): array;
    public function updateChannel(string $channelId, array $data, int $tenantId, int $actorId): array;
    public function disconnectChannel(string $channelId, int $tenantId, int $actorId): bool;
    public function listChannels(int $tenantId, array $filters = [], int $perPage = 25): LengthAwarePaginator;
    public function testConnection(string $channelId, int $tenantId): array;
    public function authenticateChannel(string $channelId, array $credentials, int $tenantId): bool;
    public function getChannelById(string $channelId, int $tenantId): ?array;
    public function syncInventory(string $channelId, int $tenantId): array;
    public function syncOrders(string $channelId, int $tenantId): array;
    public function syncPricing(string $channelId, int $tenantId): array;
}
