<?php

namespace Extensions\AIAgentToolMarketingBot\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreMarketingBotIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeMarketingBotOperations(string $tenantId, string $userId): array
    {
        return [
            'campaigns' => $this->getCampaigns($tenantId),
            'audiences' => $this->getAudiences($tenantId),
            'messages' => $this->getMessages($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
            'automation' => $this->getAutomationRules($tenantId),
            'content' => $this->getContent($tenantId),
        ];
    }

    public function getCampaigns(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('marketing/campaigns', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getAudiences(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('marketing/audiences', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getMessages(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('marketing/messages', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('marketing/analytics', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getAutomationRules(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('marketing/automation', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getContent(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('marketing/content', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function createCampaign(string $tenantId, array $campaignData): array
    {
        $response = $this->workCoreGateway->action('marketing/create_campaign', [
            'tenant_id' => $tenantId,
            'campaign_data' => $campaignData,
        ]);
        return $response->data ?? [];
    }

    public function sendCampaign(string $tenantId, string $campaignId): array
    {
        $response = $this->workCoreGateway->action('marketing/send_campaign', [
            'tenant_id' => $tenantId,
            'campaign_id' => $campaignId,
        ]);
        return $response->data ?? [];
    }

    public function createAudience(string $tenantId, array $audienceData): array
    {
        $response = $this->workCoreGateway->action('marketing/create_audience', [
            'tenant_id' => $tenantId,
            'audience_data' => $audienceData,
        ]);
        return $response->data ?? [];
    }

    public function addAutomationRule(string $tenantId, array $ruleData): array
    {
        $response = $this->workCoreGateway->action('marketing/add_automation', [
            'tenant_id' => $tenantId,
            'rule_data' => $ruleData,
        ]);
        return $response->data ?? [];
    }

    public function generateContent(string $tenantId, array $contentRequest): array
    {
        $response = $this->workCoreGateway->action('marketing/generate_content', [
            'tenant_id' => $tenantId,
            'content_request' => $contentRequest,
        ]);
        return $response->data ?? [];
    }

    public function scheduleCampaign(string $tenantId, string $campaignId, string $scheduleTime): array
    {
        $response = $this->workCoreGateway->action('marketing/schedule_campaign', [
            'tenant_id' => $tenantId,
            'campaign_id' => $campaignId,
            'schedule_time' => $scheduleTime,
        ]);
        return $response->data ?? [];
    }
}
