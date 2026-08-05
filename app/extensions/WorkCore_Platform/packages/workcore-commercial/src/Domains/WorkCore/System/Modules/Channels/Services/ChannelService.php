<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Services;

use App\Domains\WorkCore\System\Modules\Channels\Models\Channel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChannelService
{
    public function registerChannel(array $data, int $tenantId, int $actorId): Channel
    {
        $channel = Channel::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'type' => $data['type'],
            'credentials' => $data['credentials'] ?? null,
            'api_token' => $data['api_token'] ?? null,
            'api_secret' => $data['api_secret'] ?? null,
            'enabled' => $data['enabled'] ?? true,
            'settings' => $data['settings'] ?? null,
            'auth_status' => 'pending',
        ]);

        // Attempt authentication if credentials provided
        if ($channel->authenticate()) {
            $channel->refresh();
        }

        return $channel;
    }

    public function updateChannel(Channel $channel, array $data, int $tenantId, int $actorId): Channel
    {
        $channel->update(array_filter([
            'name' => $data['name'] ?? null,
            'credentials' => $data['credentials'] ?? null,
            'api_token' => $data['api_token'] ?? null,
            'api_secret' => $data['api_secret'] ?? null,
            'enabled' => $data['enabled'] ?? null,
            'settings' => $data['settings'] ?? null,
        ], fn($value) => $value !== null));

        return $channel->refresh();
    }

    public function disconnectChannel(Channel $channel, int $tenantId, int $actorId): bool
    {
        return $channel->delete();
    }

    public function listChannels(int $tenantId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Channel::byTenant($tenantId);

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['enabled'])) {
            $query->enabled();
        }

        if (isset($filters['auth_status'])) {
            $query->where('auth_status', $filters['auth_status']);
        }

        return $query->paginate($perPage);
    }

    public function testConnection(Channel $channel): array
    {
        try {
            $connector = $channel->getConnector();
            return $connector->test();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function authenticateChannel(Channel $channel, array $credentials): bool
    {
        $channel->update([
            'api_token' => $credentials['api_token'] ?? $channel->api_token,
            'api_secret' => $credentials['api_secret'] ?? $channel->api_secret,
            'credentials' => $credentials['credentials'] ?? $channel->credentials,
        ]);

        return $channel->authenticate();
    }

    public function syncAllChannels(int $tenantId): array
    {
        $channels = Channel::byTenant($tenantId)->enabled()->get();

        $results = [
            'total' => count($channels),
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($channels as $channel) {
            try {
                $syncResult = $channel->sync();
                $results['successful']++;
                $results['details'][$channel->id] = [
                    'status' => 'success',
                    'data' => $syncResult,
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][$channel->id] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function getSyncStatus(Channel $channel): array
    {
        return [
            'channel_id' => $channel->id,
            'name' => $channel->name,
            'type' => $channel->type,
            'enabled' => $channel->enabled,
            'auth_status' => $channel->auth_status,
            'authenticated_at' => $channel->authenticated_at,
            'last_sync_at' => $channel->last_sync_at,
            'is_authenticated' => $channel->isAuthenticated(),
        ];
    }
}
