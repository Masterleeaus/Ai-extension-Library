<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TerritoryAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'search_id' => $this->discovery_search_id, 'analysis_type' => $this->analysis_type,
            'search_area' => $this->search_area, 'result_summary' => $this->result_summary,
            'generated_metrics' => $this->generated_metrics, 'source_coverage' => $this->source_coverage,
            'confidence' => $this->confidence, 'generated_at' => $this->generated_at,
        ];
    }
}
