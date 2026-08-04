<?php

namespace Extensions\ChatbotAgent\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreChatbotAgentIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeAgent(string $tenantId, string $userId): array
    {
        return [
            'agents' => $this->getAgents($tenantId),
            'behaviors' => $this->getBehaviors($tenantId),
            'workflows' => $this->getWorkflows($tenantId),
            'autonomy_level' => $this->getAutonomyLevel($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
            'configuration' => $this->getConfiguration($tenantId),
        ];
    }

    public function getAgents(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_agent/agents', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getBehaviors(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_agent/behaviors', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getWorkflows(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_agent/workflows', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getAutonomyLevel(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_agent/autonomy', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_agent/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getConfiguration(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_agent/configuration', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function createAgent(string $tenantId, array $agentData): array
    {
        $response = $this->workCoreGateway->action('chatbot_agent/create_agent', [
            'tenant_id' => $tenantId,
            'agent_data' => $agentData,
        ]);
        return $response->data ?? [];
    }

    public function enableAutoResponseal(string $tenantId, string $agentId): array
    {
        $response = $this->workCoreGateway->action('chatbot_agent/enable_auto_response', [
            'tenant_id' => $tenantId,
            'agent_id' => $agentId,
        ]);
        return $response->data ?? [];
    }
}
