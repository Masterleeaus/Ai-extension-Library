<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Http\Controllers;

use App\Domains\WorkCore\System\Modules\Channels\Models\Channel;
use App\Domains\WorkCore\System\Modules\Channels\Services\InventorySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChannelInventoryController extends Controller
{
    public function __construct(private InventorySyncService $inventoryService) {}

    public function index(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $inventory = $this->inventoryService->getInventoryStatus($channel->id);

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    public function show(string $channelId, string $productId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $inventory = $this->inventoryService->getInventoryStatus($channel->id, $productId);

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    public function sync(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $result = $this->inventoryService->syncInventory($channel);

        return response()->json([
            'success' => true,
            'message' => 'Inventory sync initiated',
            'data' => $result,
        ]);
    }

    public function reserve(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 0);

        $success = $this->inventoryService->reserveInventory($channel, $productId, $quantity);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Inventory reserved' : 'Insufficient inventory',
        ]);
    }

    public function release(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 0);

        $this->inventoryService->releaseInventory($channel, $productId, $quantity);

        return response()->json([
            'success' => true,
            'message' => 'Inventory released',
        ]);
    }
}
