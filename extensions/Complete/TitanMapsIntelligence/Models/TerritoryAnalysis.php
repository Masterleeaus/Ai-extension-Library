<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Models;

final class TerritoryAnalysis extends CompanyScopedModel
{
    protected $table = 'territory_analyses';

    protected $fillable = [
        'company_id',
        'branch_id',
        'workspace_id',
        'discovery_search_id',
        'search_area',
        'categories',
        'analysis_type',
        'observation_period',
        'result_summary',
        'map_layer_reference',
        'generated_metrics',
        'source_coverage',
        'confidence',
        'generated_at'
    ];

    protected function casts(): array
    {
        return [
            'search_area' => 'array',
        'categories' => 'array',
        'observation_period' => 'array',
        'result_summary' => 'array',
        'generated_metrics' => 'array',
        'source_coverage' => 'array',
        'confidence' => 'float',
        'generated_at' => 'immutable_datetime'
        ];
    }
}
