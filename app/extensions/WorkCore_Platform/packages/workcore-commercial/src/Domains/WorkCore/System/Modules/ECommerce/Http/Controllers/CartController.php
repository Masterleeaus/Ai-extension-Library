<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CartService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources\CartResource;

class CartController
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|string',
            'session_id' => 'nullable|string',
        ]);

        $tenantId = auth()->user()?->tenant_id ?? $request->header('X-Tenant-ID');

        $cart = $this->cartService->createCart(
            $validated['customer_id'] ?? null,
            $validated['session_id'] ?? null,
            $tenantId
        );

        return response()->json(new CartResource($cart), 201);
    }

    public function show(int $id): JsonResponse
    {
        $cart = $this->cartService->getCart($id);

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        return response()->json(new CartResource($cart));
    }

    public function addItem(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'variant_id' => 'nullable|string',
            'attributes' => 'nullable|array',
        ]);

        try {
            $item = $this->cartService->addItem(
                $id,
                $validated['product_id'],
                $validated['quantity'],
                (float) $validated['price'],
                $validated['variant_id'] ?? null,
                $validated['attributes'] ?? null
            );

            return response()->json($item, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function removeItem(int $id, int $itemId): JsonResponse
    {
        try {
            $success = $this->cartService->removeItem($id, $itemId);

            if (!$success) {
                return response()->json(['error' => 'Item not found'], 404);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateQuantity(int $id, int $itemId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $item = $this->cartService->updateQuantity($id, $itemId, $validated['quantity']);

            return response()->json($item);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function applyCoupon(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $success = $this->cartService->applyCoupon($id, $validated['code']);

            if (!$success) {
                return response()->json(['error' => 'Invalid or expired coupon'], 400);
            }

            $cart = $this->cartService->getCart($id);

            return response()->json(new CartResource($cart));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function removeCoupon(int $id): JsonResponse
    {
        $this->cartService->removeCoupon($id);

        $cart = $this->cartService->getCart($id);

        return response()->json(new CartResource($cart));
    }
}
