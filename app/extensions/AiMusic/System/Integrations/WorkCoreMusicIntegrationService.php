<?php
namespace Extensions\AiMusic\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreMusicIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeMusic(string $tenantId, string $userId): array
    {
        return [
            'tracks' => $this->getTracks($tenantId),
            'playlists' => $this->getPlaylists($tenantId),
            'compositions' => $this->getCompositions($tenantId),
            'effects' => $this->getEffects($tenantId),
            'settings' => $this->getSettings($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getTracks(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('music/tracks', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getPlaylists(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('music/playlists', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getCompositions(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('music/compositions', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getEffects(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('music/effects', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getSettings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('music/settings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('music/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function generateMusic(string $tenantId, array $musicRequest): array
    {
        $response = $this->workCoreGateway->action('music/generate', [
            'tenant_id' => $tenantId,
            'music_request' => $musicRequest,
        ]);
        return $response->data ?? [];
    }
}
