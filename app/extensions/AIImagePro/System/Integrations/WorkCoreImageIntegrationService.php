<?php
namespace Extensions\AIImagePro\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreImageIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeImage(string $tenantId, string $userId): array
    {
        return [
            'images' => $this->getImages($tenantId),
            'galleries' => $this->getGalleries($tenantId),
            'edits' => $this->getEdits($tenantId),
            'filters' => $this->getFilters($tenantId),
            'settings' => $this->getSettings($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getImages(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('image/images', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getGalleries(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('image/galleries', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getEdits(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('image/edits', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getFilters(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('image/filters', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getSettings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('image/settings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('image/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function uploadImage(string $tenantId, array $imageData): array
    {
        $response = $this->workCoreGateway->action('image/upload', [
            'tenant_id' => $tenantId,
            'image_data' => $imageData,
        ]);
        return $response->data ?? [];
    }
}
