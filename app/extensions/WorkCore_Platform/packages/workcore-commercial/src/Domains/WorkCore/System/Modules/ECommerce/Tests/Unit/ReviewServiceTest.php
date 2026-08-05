<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models\Review;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\ReviewService;

class ReviewServiceTest extends TestCase
{
    protected ReviewService $reviewService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reviewService = new ReviewService();
    }

    public function testReviewCanBeSubmitted(): void
    {
        $review = $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-123',
            rating: 5,
            title: 'Great product',
            comment: 'This is a great product!',
            customerName: 'John Doe'
        );

        $this->assertNotNull($review);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('pending', $review->moderation_status);
    }

    public function testReviewRatingValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-123',
            rating: 6 // Invalid rating
        );
    }

    public function testReviewCanBeModerated(): void
    {
        $review = $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-123',
            rating: 4,
            comment: 'Good product'
        );

        $moderated = $this->reviewService->approveReview($review->id);

        $this->assertEquals('approved', $moderated->moderation_status);
    }

    public function testReviewCanBeRejected(): void
    {
        $review = $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-123',
            rating: 1,
            comment: 'Offensive comment'
        );

        $rejected = $this->reviewService->rejectReview($review->id);

        $this->assertEquals('rejected', $rejected->moderation_status);
    }

    public function testProductAverageRating(): void
    {
        // Create multiple reviews
        $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-001',
            rating: 5
        )->update(['moderation_status' => 'approved']);

        $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-002',
            rating: 3
        )->update(['moderation_status' => 'approved']);

        $average = $this->reviewService->getProductAverageRating('prod-001', 'tenant-123');

        $this->assertEquals(4.0, $average);
    }

    public function testReviewCanBeMarkedHelpful(): void
    {
        $review = $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-123',
            rating: 4,
            comment: 'Helpful review'
        );

        $initial = $review->helpful_count;

        $updated = $this->reviewService->markHelpful($review->id);

        $this->assertEquals($initial + 1, $updated->helpful_count);
    }

    public function testReviewCanBeDeleted(): void
    {
        $review = $this->reviewService->submitReview(
            tenantId: 'tenant-123',
            productId: 'prod-001',
            customerId: 'customer-123',
            rating: 4
        );

        $deleted = $this->reviewService->deleteReview($review->id);

        $this->assertTrue($deleted);
    }
}
