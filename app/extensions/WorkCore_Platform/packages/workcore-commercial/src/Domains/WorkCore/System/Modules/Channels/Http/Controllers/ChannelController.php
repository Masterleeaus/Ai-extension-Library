<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Http\Controllers;

use App\Domains\WorkCore\System\Modules\Channels\Http\Requests\{
    RegisterChannelRequest,
    UpdateChannelRequest,
};
use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelMapping};
use App\Domains\WorkCore\System\Modules\Channels\Services\{
    ChannelService,
    InventorySyncService,
    OrderSyncService,
    PricingSyncService,
};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChannelController extends Controller
{
    public function __construct(
        private ChannelService $channelService,
        private InventorySyncService $inventoryService,
        private OrderSyncService $orderService,
        private PricingSyncService $pricingService,
    ) {}

    public function register(RegisterChannelRequest $request): JsonResponse
    {
        $channel = $this->channelService->registerChannel(
            $request->validated(),
            $request->user()->tenant_id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Channel registered successfully',
            'data' => [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
                'auth_status' => $channel->auth_status,
            ],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->query('filters', []);
        $perPage = $request->query('per_page', 25);

        $channels = $this->channelService->listChannels(
            $request->user()->tenant_id,
            is_array($filters) ? $filters : [],
            (int) $perPage
        );

        return response()->json([
            'success' => true,
            'data' => $channels->items(),
            'pagination' => [
                'total' => $channels->total(),
                'per_page' => $channels->perPage(),
                'current_page' => $channels->currentPage(),
                'last_page' => $channels->lastPage(),
            ],
        ]);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
                'enabled' => $channel->enabled,
                'auth_status' => $channel->auth_status,
                'authenticated_at' => $channel->authenticated_at,
                'last_sync_at' => $channel->last_sync_at,
                'settings' => $channel->settings,
            ],
        ]);
    }

    public function update(string $id, UpdateChannelRequest $request): JsonResponse
    {
        $channel = Channel::where('id', $id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $updated = $this->channelService->updateChannel(
            $channel,
            $request->validated(),
            $request->user()->tenant_id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Channel updated successfully',
            'data' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'enabled' => $updated->enabled,
            ],
        ]);
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $this->channelService->disconnectChannel(
            $channel,
            $request->user()->tenant_id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Channel disconnected successfully',
        ]);
    }

    public function test(string $id, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $result = $this->channelService->testConnection($channel);

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Test failed',
            'data' => $result,
        ]);
    }

    public function status(string $id, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $status = $this->channelService->getSyncStatus($channel);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function sync(string $id, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $syncType = $request->input('type', 'all'); // 'all', 'inventory', 'orders', 'pricing'

        $results = [];

        if (in_array($syncType, ['all', 'inventory'])) {
            $results['inventory'] = $this->inventoryService->syncInventory($channel);
        }

        if (in_array($syncType, ['all', 'orders'])) {
            $results['orders'] = $this->orderService->importOrders($channel);
        }

        if (in_array($syncType, ['all', 'pricing'])) {
            $results['pricing'] = $this->pricingService->syncPrices($channel);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sync initiated successfully',
            'data' => $results,
        ]);
    }

    public function mappings(Request $request): JsonResponse
    {
        $channelId = $request->query('channel_id');
        $tenantId = $request->user()->tenant_id;

        $query = ChannelMapping::where('tenant_id', $tenantId);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        $mappings = $query->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $mappings->items(),
            'pagination' => [
                'total' => $mappings->total(),
                'per_page' => $mappings->perPage(),
                'current_page' => $mappings->currentPage(),
                'last_page' => $mappings->lastPage(),
            ],
        ]);
    }
}
