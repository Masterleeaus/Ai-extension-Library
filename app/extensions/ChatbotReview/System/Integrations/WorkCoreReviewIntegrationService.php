<?php
namespace Extensions\ChatbotReview\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreReviewIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeReviews(string $tenantId, string $userId): array
    {
        return [
            'reviews' => $this->getReviews($tenantId),
            'ratings' => $this->getRatings($tenantId),
            'feedback' => $this->getFeedback($tenantId),
            'sentiment' => $this->getSentiment($tenantId),
            'responses' => $this->getResponses($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getReviews(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('review/reviews', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getRatings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('review/ratings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getFeedback(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('review/feedback', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getSentiment(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('review/sentiment', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getResponses(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('review/responses', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('review/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function respondToReview(string $tenantId, string $reviewId, string $response): array
    {
        $res = $this->workCoreGateway->action('review/respond_to_review', [
            'tenant_id' => $tenantId,
            'review_id' => $reviewId,
            'response' => $response,
        ]);
        return $res->data ?? [];
    }
}
