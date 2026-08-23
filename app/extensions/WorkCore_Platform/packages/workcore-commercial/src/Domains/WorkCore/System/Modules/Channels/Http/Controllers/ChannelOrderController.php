<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Http\Controllers;

use App\Domains\WorkCore\System\Modules\Channels\Models\{Channel, ChannelOrder};
use App\Domains\WorkCore\System\Modules\Channels\Services\OrderSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChannelOrderController extends Controller
{
    public function __construct(private OrderSyncService $orderService) {}

    public function index(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $filters = [
            'status' => $request->query('status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'per_page' => $request->query('per_page', 25),
        ];

        $orders = $this->orderService->getOrdersByChannel($channel->id, $filters);

        return response()->json([
            'success' => true,
            'data' => $orders['data'] ?? [],
            'pagination' => $orders['pagination'] ?? [],
        ]);
    }

    public function show(string $channelId, string $orderId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $order = ChannelOrder::where('id', $orderId)
            ->where('channel_id', $channel->id)
            ->firstOrFail();

        $details = $this->orderService->getOrderDetails($order);

        return response()->json([
            'success' => true,
            'data' => $details,
        ]);
    }

    public function sync(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $result = $this->orderService->importOrders($channel);

        return response()->json([
            'success' => true,
            'message' => 'Order sync initiated',
            'data' => $result,
        ]);
    }

    public function pushStatus(string $channelId, string $orderId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $order = ChannelOrder::where('id', $orderId)
            ->where('channel_id', $channel->id)
            ->firstOrFail();

        $status = $request->input('status');
        $success = $this->orderService->pushOrderStatus($order, $status);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Status pushed successfully' : 'Failed to push status',
        ]);
    }
}
