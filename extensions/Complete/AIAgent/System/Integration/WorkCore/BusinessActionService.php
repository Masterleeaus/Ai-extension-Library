<?php

namespace Extensions\AIAgent\System\Integration\WorkCore;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class BusinessActionService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function processBusinessAction(string $tenantId, array $actionData): array
    {
        $response = $this->workCoreGateway->action('business_network/business_action', [
            'tenant_id' => $tenantId,
            'action_data' => $actionData,
        ]);
        return $response->data ?? [];
    }

    public function requestApproval(string $tenantId, string $actionId): array
    {
        $response = $this->workCoreGateway->action('business_network/request_approval', [
            'tenant_id' => $tenantId,
            'action_id' => $actionId,
        ]);
        return $response->data ?? [];
    }
}
