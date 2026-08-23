<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\ReviewService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources\ReviewResource;

class ReviewController
{
    public function __construct(
        protected ReviewService $reviewService
    ) {
    }

    public function index(string $productId, Request $request): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id ?? $request->header('X-Tenant-ID');

        $reviews = $this->reviewService->getProductReviews(
            $productId,
            $tenantId,
            'approved',
            $request->query('limit', 10)
        );

        return response()->json(ReviewResource::collection($reviews));
    }

    public function store(string $productId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
        ]);

        try {
            $tenantId = auth()->user()?->tenant_id ?? $request->header('X-Tenant-ID');

            $review = $this->reviewService->submitReview(
                $tenantId,
                $productId,
                auth()->user()?->id ?? $request->ip(),
                $validated['rating'],
                $validated['title'] ?? null,
                $validated['comment'] ?? null,
                $validated['customer_name'] ?? null
            );

            return response()->json(new ReviewResource($review), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'title' => 'sometimes|string|max:255',
            'comment' => 'sometimes|string',
        ]);

        try {
            $review = \WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\Review::findOrFail($id);
            $review->update($validated);

            return response()->json(new ReviewResource($review));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            if (!$this->reviewService->deleteReview($id)) {
                return response()->json(['error' => 'Review not found'], 404);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $review = $this->reviewService->approveReview($id);

            return response()->json(new ReviewResource($review));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reject(int $id): JsonResponse
    {
        try {
            $review = $this->reviewService->rejectReview($id);

            return response()->json(new ReviewResource($review));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
