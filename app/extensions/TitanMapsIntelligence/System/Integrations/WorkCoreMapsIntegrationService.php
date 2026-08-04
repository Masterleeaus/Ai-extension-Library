<?php
namespace Extensions\TitanMapsIntelligence\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreMapsIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeMaps(string $tenantId, string $userId): array
    {
        return [
            'locations' => $this->getLocations($tenantId),
            'routes' => $this->getRoutes($tenantId),
            'territories' => $this->getTerritories($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
            'geofences' => $this->getGeofences($tenantId),
            'insights' => $this->getInsights($tenantId),
        ];
    }
    public function getLocations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('maps/locations', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getRoutes(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('maps/routes', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getTerritories(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('maps/territories', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('maps/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getGeofences(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('maps/geofences', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getInsights(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('maps/insights', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function optimizeRoute(string $tenantId, array $routeData): array
    {
        $response = $this->workCoreGateway->action('maps/optimize_route', [
            'tenant_id' => $tenantId,
            'route_data' => $routeData,
        ]);
        return $response->data ?? [];
    }
}
