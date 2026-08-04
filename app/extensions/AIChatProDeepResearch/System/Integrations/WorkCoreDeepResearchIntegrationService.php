<?php

namespace Extensions\AIChatProDeepResearch\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreDeepResearchIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeDeepResearch(string $tenantId, string $userId): array
    {
        return [
            'research_projects' => $this->getResearchProjects($tenantId),
            'sources' => $this->getSources($tenantId),
            'citations' => $this->getCitations($tenantId),
            'findings' => $this->getFindings($tenantId),
            'reports' => $this->getReports($tenantId),
            'bibliography' => $this->getBibliography($tenantId),
        ];
    }

    public function getResearchProjects(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('research/projects', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getSources(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('research/sources', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getCitations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('research/citations', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getFindings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('research/findings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getReports(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('research/reports', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getBibliography(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('research/bibliography', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function createResearchProject(string $tenantId, array $projectData): array
    {
        $response = $this->workCoreGateway->action('research/create_project', [
            'tenant_id' => $tenantId,
            'project_data' => $projectData,
        ]);
        return $response->data ?? [];
    }

    public function addSource(string $tenantId, string $projectId, array $sourceData): array
    {
        $response = $this->workCoreGateway->action('research/add_source', [
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
            'source_data' => $sourceData,
        ]);
        return $response->data ?? [];
    }

    public function generateReport(string $tenantId, string $projectId): array
    {
        $response = $this->workCoreGateway->action('research/generate_report', [
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
        ]);
        return $response->data ?? [];
    }

    public function analyzeSources(string $tenantId, string $projectId): array
    {
        $response = $this->workCoreGateway->action('research/analyze_sources', [
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
        ]);
        return $response->data ?? [];
    }
}
