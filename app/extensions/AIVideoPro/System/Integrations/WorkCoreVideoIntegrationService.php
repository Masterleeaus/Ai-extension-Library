<?php
namespace Extensions\AIVideoPro\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreVideoIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeVideo(string $tenantId, string $userId): array
    {
        return [
            'videos' => $this->getVideos($tenantId),
            'projects' => $this->getProjects($tenantId),
            'edits' => $this->getEdits($tenantId),
            'effects' => $this->getEffects($tenantId),
            'settings' => $this->getSettings($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getVideos(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('video/videos', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getProjects(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('video/projects', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getEdits(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('video/edits', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getEffects(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('video/effects', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getSettings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('video/settings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('video/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function uploadVideo(string $tenantId, array $videoData): array
    {
        $response = $this->workCoreGateway->action('video/upload', [
            'tenant_id' => $tenantId,
            'video_data' => $videoData,
        ]);
        return $response->data ?? [];
    }
}
