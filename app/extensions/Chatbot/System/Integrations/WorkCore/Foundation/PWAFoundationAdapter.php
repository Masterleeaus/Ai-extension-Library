<?php

namespace Extensions\Chatbot\System\Integrations\WorkCore\Foundation;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class PWAFoundationAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializePWA(string $tenantId, string $userId): array
    {
        return [
            'tenant_context' => $this->getTenantContext($tenantId),
            'authorization' => $this->getAuthorizationPolicies($tenantId, $userId),
            'governed_actions' => $this->getGovernedActions($tenantId),
            'credentials' => $this->getCredentials($tenantId),
            'audit' => $this->getAuditSetup($tenantId),
            'event_bus' => $this->getEventBusConfig($tenantId),
        ];
    }

    protected function getTenantContext(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('shared_foundation/tenant_context', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getAuthorizationPolicies(string $tenantId, string $userId): array
    {
        $response = $this->workCoreGateway->query('shared_foundation/authorization_policies', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
        ]);
        return $response->data ?? [];
    }

    protected function getGovernedActions(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('shared_foundation/governed_actions', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getCredentials(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('shared_foundation/credential_vault', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getAuditSetup(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('shared_foundation/audit_trails', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getEventBusConfig(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('shared_foundation/event_envelope', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
