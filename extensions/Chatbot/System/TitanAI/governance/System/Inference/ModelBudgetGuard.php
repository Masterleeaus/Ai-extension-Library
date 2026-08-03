<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Inference;

use RuntimeException;

final class ModelBudgetGuard
{
    public function assertAllowed(string $model, int $inputTokens, int $outputTokens): array
    {
        $pricing = (array) config("titan-ai-governance.model_pricing.$model", []);
        $inputRate = (float) ($pricing['input_per_million'] ?? 0.0);
        $outputRate = (float) ($pricing['output_per_million'] ?? 0.0);
        $estimated = (($inputTokens / 1_000_000) * $inputRate) + (($outputTokens / 1_000_000) * $outputRate);
        $limit = (float) config('titan-ai-governance.max_cost_per_review', 0.25);

        if ($estimated > $limit) {
            throw new RuntimeException('Model review cost exceeded the configured limit.');
        }

        return ['currency' => 'USD', 'estimated_cost' => round($estimated, 6), 'limit' => $limit];
    }
}
