<?php

namespace Extensions\Chatbot\System\Integrations\WorkCore\Assets;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class PropertyAssistantAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getPropertyContext(string $tenantId, array $conversationContext): array
    {
        return [
            'property_data' => $this->getPropertyData($tenantId),
            'asset_information' => $this->getAssetInformation($tenantId),
            'documents' => $this->getDocuments($tenantId),
            'maintenance_requests' => $this->getMaintenanceRequests($tenantId),
            'media' => $this->getPropertyMedia($tenantId),
        ];
    }

    protected function getPropertyData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/properties', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getAssetInformation(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/assets', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getDocuments(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/documents', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getMaintenanceRequests(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/maintenance', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getPropertyMedia(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/media', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
}
