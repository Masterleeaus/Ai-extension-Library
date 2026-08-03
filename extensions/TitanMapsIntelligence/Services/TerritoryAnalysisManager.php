<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Services;

use App\Extensions\TitanMapsIntelligence\Contracts\AuditRecorder;
use App\Extensions\TitanMapsIntelligence\Contracts\AuthorisedCompanyContext;
use App\Extensions\TitanMapsIntelligence\Contracts\PermissionAuthorizer;
use App\Extensions\TitanMapsIntelligence\Events\TerritoryAnalysisCompleted;
use App\Extensions\TitanMapsIntelligence\Models\DiscoveryCandidate;
use App\Extensions\TitanMapsIntelligence\Models\TerritoryAnalysis;
use Illuminate\Contracts\Events\Dispatcher;

final class TerritoryAnalysisManager
{
    public function __construct(
        private readonly AuthorisedCompanyContext $context,
        private readonly PermissionAuthorizer $authorizer,
        private readonly TerritoryAnalysisService $analysis,
        private readonly AuditRecorder $audit,
        private readonly Dispatcher $events,
    ) {}

    public function analyse(string $searchId, string $analysisType = 'provider_coverage', array $searchArea = [], array $execution = []): TerritoryAnalysis
    {
        $companyId = $this->context->companyId();
        $userId = $this->context->userId();
        $this->authorizer->authorize($userId, $companyId, 'titan-maps-intelligence.territory.analyse', ['search_id' => $searchId]);
        $places = DiscoveryCandidate::query()->with('place')->where('company_id', $companyId)->where('discovery_search_id', $searchId)->get()->map(static fn ($candidate): array => ($candidate->place?->toArray() ?? []) + ['confidence' => (float) $candidate->confidence_score])->all();
        $result = $this->analysis->analyse($places);
        $record = TerritoryAnalysis::query()->create([
            'company_id' => $companyId, 'branch_id' => $this->context->branchId(), 'workspace_id' => $this->context->workspaceId(),
            'discovery_search_id' => $searchId, 'search_area' => $searchArea, 'categories' => array_keys($result['categories']),
            'analysis_type' => $analysisType, 'result_summary' => ['total_places' => $result['total_places']],
            'generated_metrics' => ['categories' => $result['categories'], 'distance_bands' => $result['distance_bands'], 'average_confidence' => $result['average_confidence']],
            'source_coverage' => $result['source_coverage'], 'confidence' => $result['average_confidence'], 'generated_at' => now(),
        ]);
        $payload = ['analysis_id' => $record->getKey(), 'search_id' => $searchId, 'analysis_type' => $analysisType];
        $this->audit->record(['type' => 'territory.analysis_completed', 'company_id' => $companyId, 'user_id' => $userId, 'agent_id' => $execution['agent_id'] ?? null, 'entity_type' => 'territory_analysis', 'entity_id' => $record->getKey(), 'after' => $record->toArray()]);
        $this->events->dispatch(new TerritoryAnalysisCompleted($companyId, $userId, $execution['agent_id'] ?? null, $execution['conversation_id'] ?? null, $execution['correlation_id'] ?? null, null, $payload));
        return $record;
    }
}
