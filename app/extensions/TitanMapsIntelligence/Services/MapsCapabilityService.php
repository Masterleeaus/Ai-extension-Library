<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Services;

use App\Extensions\TitanMapsIntelligence\Tools\AnalyseTerritoryTool;
use App\Extensions\TitanMapsIntelligence\Tools\ApproveCandidateTool;
use App\Extensions\TitanMapsIntelligence\Tools\CancelSearchTool;
use App\Extensions\TitanMapsIntelligence\Tools\ClassifyCandidateTool;
use App\Extensions\TitanMapsIntelligence\Tools\ExportSearchTool;
use App\Extensions\TitanMapsIntelligence\Tools\GetSearchTool;
use App\Extensions\TitanMapsIntelligence\Tools\ListCandidatesTool;
use App\Extensions\TitanMapsIntelligence\Tools\MatchCandidateTool;
use App\Extensions\TitanMapsIntelligence\Tools\PromoteCandidateTool;
use App\Extensions\TitanMapsIntelligence\Tools\ReadUsageTool;
use App\Extensions\TitanMapsIntelligence\Tools\RejectCandidateTool;
use App\Extensions\TitanMapsIntelligence\Tools\SearchBusinessesTool;

final class MapsCapabilityService
{
    public function definitions(): array
    {
        return [
            $this->definition('titan-maps-intelligence.search.businesses', 'titan-maps-intelligence.search.create', SearchBusinessesTool::class, [
                'query' => $this->string(),
                'purpose' => $this->enum(['provider_discovery', 'supplier_discovery', 'sales_lead_discovery', 'competitor_analysis', 'accommodation_discovery', 'emergency_sourcing']),
                'maximum_results' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                'language' => $this->string(), 'country' => $this->string(), 'category' => $this->string(),
                'latitude' => ['type' => 'number', 'minimum' => -90, 'maximum' => 90],
                'longitude' => ['type' => 'number', 'minimum' => -180, 'maximum' => 180],
                'radius_metres' => ['type' => 'number', 'exclusiveMinimum' => 0, 'maximum' => 50000],
                'open_now' => ['type' => 'boolean'], 'minimum_rating' => ['type' => 'number', 'minimum' => 0, 'maximum' => 5],
                'conversation_id' => $this->string(), 'agent_id' => $this->string(), 'correlation_id' => $this->string(),
            ], ['query']),
            $this->definition('titan-maps-intelligence.search.read', 'titan-maps-intelligence.search.read', GetSearchTool::class, ['search_id' => $this->string()], ['search_id']),
            $this->definition('titan-maps-intelligence.search.cancel', 'titan-maps-intelligence.search.cancel', CancelSearchTool::class, ['search_id' => $this->string()], ['search_id']),
            $this->definition('titan-maps-intelligence.search.export', 'titan-maps-intelligence.search.export', ExportSearchTool::class, [
                'search_id' => $this->string(), 'format' => $this->enum(['csv', 'json', 'xlsx']),
                'candidate_status' => $this->enum(['approved', 'rejected', 'pending', 'unreviewed']),
                'agent_id' => $this->string(), 'conversation_id' => $this->string(),
            ], ['search_id', 'format']),
            $this->definition('titan-maps-intelligence.candidates.list', 'titan-maps-intelligence.candidate.read', ListCandidatesTool::class, ['search_id' => $this->string(), 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]], ['search_id']),
            $this->definition('titan-maps-intelligence.candidate.match', 'titan-maps-intelligence.candidate.read', MatchCandidateTool::class, [
                'candidate_id' => $this->string(), 'workcore_entity_type' => $this->enum(['lead', 'customer', 'provider', 'contractor', 'supplier']),
                'workcore_entity_id' => $this->string(), 'agent_id' => $this->string(), 'conversation_id' => $this->string(),
            ], ['candidate_id', 'workcore_entity_type', 'workcore_entity_id']),
            $this->definition('titan-maps-intelligence.candidate.classify', 'titan-maps-intelligence.candidate.classify', ClassifyCandidateTool::class, [
                'candidate_id' => $this->string(), 'candidate_type' => $this->enum(['sales_lead', 'customer_candidate', 'provider_candidate', 'contractor_candidate', 'supplier_candidate', 'property_operator', 'accommodation_provider', 'property_manager', 'facilities_manager', 'competitor', 'emergency_provider', 'irrelevant', 'unclassified']),
                'reason' => $this->string(), 'agent_id' => $this->string(), 'conversation_id' => $this->string(),
            ], ['candidate_id', 'candidate_type']),
            $this->definition('titan-maps-intelligence.candidate.approve', 'titan-maps-intelligence.candidate.approve', ApproveCandidateTool::class, ['candidate_id' => $this->string(), 'agent_id' => $this->string(), 'conversation_id' => $this->string()], ['candidate_id']),
            $this->definition('titan-maps-intelligence.candidate.reject', 'titan-maps-intelligence.candidate.reject', RejectCandidateTool::class, ['candidate_id' => $this->string(), 'reason' => $this->string(), 'agent_id' => $this->string(), 'conversation_id' => $this->string()], ['candidate_id', 'reason']),
            $this->definition('titan-maps-intelligence.candidate.promote', 'titan-maps-intelligence.candidate.promote', PromoteCandidateTool::class, [
                'candidate_id' => $this->string(), 'target_type' => $this->enum(['lead', 'customer', 'provider', 'contractor', 'supplier']),
                'accepted_fields' => ['type' => 'array', 'minItems' => 1, 'uniqueItems' => true, 'items' => $this->enum(['name', 'phone', 'website', 'public_email', 'address', 'categories'])],
                'agent_id' => $this->string(), 'conversation_id' => $this->string(), 'correlation_id' => $this->string(),
            ], ['candidate_id', 'target_type', 'accepted_fields']),
            $this->definition('titan-maps-intelligence.territory.analyse', 'titan-maps-intelligence.territory.analyse', AnalyseTerritoryTool::class, [
                'search_id' => $this->string(), 'analysis_type' => $this->enum(['provider_coverage', 'competitor_density', 'supplier_coverage', 'service_gap']),
                'search_area' => ['type' => 'object'], 'agent_id' => $this->string(), 'conversation_id' => $this->string(),
            ], ['search_id']),
            $this->definition('titan-maps-intelligence.usage.read', 'titan-maps-intelligence.usage.read', ReadUsageTool::class, ['search_id' => $this->string()], []),
        ];
    }

    private function definition(string $id, string $permission, string $handler, array $properties, array $required): array
    {
        return [
            'id' => $id,
            'permission' => $permission,
            'handler' => $handler,
            'description' => str_replace(['titan-maps-intelligence.', '.'], ['', ' '], $id),
            'input_schema' => ['type' => 'object', 'properties' => $properties, 'required' => $required, 'additionalProperties' => false],
            'output_schema' => [
                'type' => 'object',
                'properties' => ['ok' => ['type' => 'boolean'], 'data' => ['type' => ['object', 'array', 'null']], 'error' => ['type' => ['object', 'null']]],
                'required' => ['ok'],
                'additionalProperties' => false,
            ],
        ];
    }

    private function string(): array
    {
        return ['type' => 'string', 'minLength' => 1];
    }

    private function enum(array $values): array
    {
        return ['type' => 'string', 'enum' => $values];
    }
}
