<?php
namespace Extensions\ChatbotCompleted\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreChatbotCompletedIntegrationService
{
    protected $workCoreGateway;
    
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    
    public function initialize(string $tenantId, string $userId): array
    {
        return [
            'data' => $this->getData($tenantId),
            'settings' => $this->getSettings($tenantId),
            'projects' => $this->getProjects($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
            'resources' => $this->getResources($tenantId),
        ];
    }
    
    public function getData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_completed/data', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    
    public function getSettings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_completed/settings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    
    public function getProjects(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_completed/projects', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_completed/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    
    public function getResources(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('chatbot_completed/resources', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    
    public function create(string $tenantId, array $data): array
    {
        $response = $this->workCoreGateway->action('chatbot_completed/create', [
            'tenant_id' => $tenantId,
            'data' => $data,
        ]);
        return $response->data ?? [];
    }
}
