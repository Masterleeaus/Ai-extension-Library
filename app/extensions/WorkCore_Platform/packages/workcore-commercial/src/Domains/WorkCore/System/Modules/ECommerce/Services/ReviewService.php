<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services;

use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\Review;
use Illuminate\Database\Eloquent\Collection;

class ReviewService
{
    public function submitReview(
        string $tenantId,
        string $productId,
        string $customerId,
        int $rating,
        ?string $title = null,
        ?string $comment = null,
        ?string $customerName = null
    ): Review {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException("Rating must be between 1 and 5");
        }

        return Review::updateOrCreate(
            [
                'product_id' => $productId,
                'customer_id' => $customerId,
            ],
            [
                'tenant_id' => $tenantId,
                'rating' => $rating,
                'title' => $title,
                'comment' => $comment,
                'customer_name' => $customerName,
                'moderation_status' => 'pending',
            ]
        );
    }

    public function moderateReview(int $reviewId, string $status): Review
    {
        $review = Review::findOrFail($reviewId);

        if (!in_array($status, ['approved', 'rejected'])) {
            throw new \InvalidArgumentException("Invalid moderation status: {$status}");
        }

        $review->moderate($status);

        return $review;
    }

    public function approveReview(int $reviewId): Review
    {
        return $this->moderateReview($reviewId, 'approved');
    }

    public function rejectReview(int $reviewId): Review
    {
        return $this->moderateReview($reviewId, 'rejected');
    }

    public function getProductReviews(
        string $productId,
        string $tenantId,
        ?string $status = null,
        int $limit = 10
    ): Collection {
        $query = Review::where('product_id', $productId)
            ->where('tenant_id', $tenantId);

        if ($status) {
            $query->where('moderation_status', $status);
        }

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getApprovedReviews(string $productId, string $tenantId, int $limit = 10): Collection
    {
        return $this->getProductReviews($productId, $tenantId, 'approved', $limit);
    }

    public function getProductAverageRating(string $productId, string $tenantId): float
    {
        $average = Review::where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('moderation_status', 'approved')
            ->avg('rating');

        return round($average ?? 0, 2);
    }

    public function getProductRatingDistribution(string $productId, string $tenantId): array
    {
        $distribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $count = Review::where('product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->where('rating', $i)
                ->where('moderation_status', 'approved')
                ->count();

            $distribution[$i] = $count;
        }

        return $distribution;
    }

    public function deleteReview(int $reviewId): bool
    {
        $review = Review::find($reviewId);

        if (!$review) {
            return false;
        }

        return $review->delete();
    }

    public function markHelpful(int $reviewId): Review
    {
        $review = Review::findOrFail($reviewId);
        $review->increment('helpful_count');

        return $review;
    }
}
