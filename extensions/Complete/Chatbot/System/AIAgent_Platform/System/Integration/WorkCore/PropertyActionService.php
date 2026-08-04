<?php

namespace Extensions\Chatbot\System\AIAgent_Platform\System\Integration\WorkCore;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class PropertyActionService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function processPropertyAction(string $tenantId, array $actionData): array
    {
        $response = $this->workCoreGateway->action('property_operations/property_action', [
            'tenant_id' => $tenantId,
            'action_data' => $actionData,
        ]);
        return $response->data ?? [];
    }

    public function requestApproval(string $tenantId, string $actionId): array
    {
        $response = $this->workCoreGateway->action('property_operations/request_approval', [
            'tenant_id' => $tenantId,
            'action_id' => $actionId,
        ]);
        return $response->data ?? [];
    }
}
