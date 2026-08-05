<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\OrderService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources\OrderResource;

class OrderController
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id ?? $request->header('X-Tenant-ID');

        $orders = $this->orderService->listOrders(
            $tenantId,
            auth()->user()?->id,
            $request->query('status')
        );

        return response()->json(OrderResource::collection($orders));
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json(new OrderResource($order));
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
        ]);

        try {
            $order = $this->orderService->updateStatus($id, $validated['status']);

            return response()->json(new OrderResource($order));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($id);

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            if (!$order->cancel()) {
                return response()->json(['error' => 'Order cannot be cancelled at this stage'], 400);
            }

            return response()->json(new OrderResource($order));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function refund(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $order = $this->orderService->refund($id, $validated['amount'] ?? null);

            return response()->json(new OrderResource($order));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function export(int $id): JsonResponse
    {
        try {
            $data = $this->orderService->export($id);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
