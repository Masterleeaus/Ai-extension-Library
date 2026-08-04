<?php

namespace Extensions\Chatbot\System\Integration\WorkCore;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class PropertyQueryService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function queryPropertyData(string $tenantId, array $queryParams): array
    {
        $response = $this->workCoreGateway->query('property_operations/query', [
            'tenant_id' => $tenantId,
            'query_params' => $queryParams,
        ]);
        return $response->data ?? [];
    }

    public function getPropertyContext(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('property_operations/context', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
