<?php

namespace Extensions\Chatbot\System\Integration\WorkCore;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class BusinessQueryService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function queryBusinessData(string $tenantId, array $queryParams): array
    {
        $response = $this->workCoreGateway->query('business_network/query', [
            'tenant_id' => $tenantId,
            'query_params' => $queryParams,
        ]);
        return $response->data ?? [];
    }

    public function getBusinessContext(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('business_network/context', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
