<?php

namespace App\Domains\WorkCore\Pricing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domains\WorkCore\Pricing\Services\DemandAnalysisService;
use App\Domains\WorkCore\Pricing\Models\DemandIndicator;

class AnalyzeDemandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    protected string $companyId;

    protected string $resourceType;

    protected int $resourceId;

    public function __construct(string $companyId, string $resourceType, int $resourceId)
    {
        $this->companyId = $companyId;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
    }

    public function handle(DemandAnalysisService $demandAnalysis): void
    {
        // Get the latest demand indicator
        $indicator = DemandIndicator::getLatest(
            $this->companyId,
            $this->resourceType,
            $this->resourceId
        );

        if (! $indicator) {
            return;
        }

        // Calculate demand score
        $indicator->calculateDemandScore();

        // Get insights
        $insights = $demandAnalysis->getInsights(
            $this->companyId,
            $this->resourceType,
            $this->resourceId
        );

        // Store insights in metadata
        $indicator->update([
            'metadata' => array_merge(
                $indicator->metadata ?? [],
                ['insights' => $insights]
            ),
        ]);
    }
}
