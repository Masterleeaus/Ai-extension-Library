<?php

namespace Extensions\AIChatProSkills\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreSkillsIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeSkills(string $tenantId, string $userId): array
    {
        return [
            'skills' => $this->getSkills($tenantId),
            'templates' => $this->getTemplates($tenantId),
            'prompts' => $this->getPrompts($tenantId),
            'usage' => $this->getUsage($tenantId),
            'categories' => $this->getCategories($tenantId),
            'performance' => $this->getPerformance($tenantId),
        ];
    }

    public function getSkills(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('skills/skills', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getTemplates(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('skills/templates', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getPrompts(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('skills/prompts', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getUsage(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('skills/usage', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getCategories(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('skills/categories', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getPerformance(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('skills/performance', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function createSkill(string $tenantId, array $skillData): array
    {
        $response = $this->workCoreGateway->action('skills/create_skill', [
            'tenant_id' => $tenantId,
            'skill_data' => $skillData,
        ]);
        return $response->data ?? [];
    }

    public function enableSkill(string $tenantId, string $skillId): array
    {
        $response = $this->workCoreGateway->action('skills/enable_skill', [
            'tenant_id' => $tenantId,
            'skill_id' => $skillId,
        ]);
        return $response->data ?? [];
    }

    public function disableSkill(string $tenantId, string $skillId): array
    {
        $response = $this->workCoreGateway->action('skills/disable_skill', [
            'tenant_id' => $tenantId,
            'skill_id' => $skillId,
        ]);
        return $response->data ?? [];
    }

    public function updateSkillPrompt(string $tenantId, string $skillId, string $prompt): array
    {
        $response = $this->workCoreGateway->action('skills/update_prompt', [
            'tenant_id' => $tenantId,
            'skill_id' => $skillId,
            'prompt' => $prompt,
        ]);
        return $response->data ?? [];
    }

    public function trainSkill(string $tenantId, string $skillId, array $trainingData): array
    {
        $response = $this->workCoreGateway->action('skills/train_skill', [
            'tenant_id' => $tenantId,
            'skill_id' => $skillId,
            'training_data' => $trainingData,
        ]);
        return $response->data ?? [];
    }
}
