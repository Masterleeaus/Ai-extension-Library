<?php

namespace Extensions\AIChatPro\System\Integrations\WorkCore\Assets;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class AssetManagementAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getAssetDashboard(string $tenantId, string $userId): array
    {
        return [
            'properties' => $this->getProperties($tenantId),
            'assets' => $this->getAssets($tenantId),
            'documents' => $this->getDocuments($tenantId),
            'maintenance_schedules' => $this->getMaintenanceSchedules($tenantId),
            'vertical_profiles' => $this->getVerticalProfiles($tenantId),
        ];
    }

    protected function getProperties(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/properties', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getAssets(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/assets', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getDocuments(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/documents', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getMaintenanceSchedules(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/maintenance', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getVerticalProfiles(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/vertical_profiles', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
