<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI;

use App\Extensions\Chatbot\System\TitanAI\Runtime\WorkerRouteRegistry;
use App\Extensions\Chatbot\System\TitanAI\Runtime\AgentExecutionPathResolver;
use App\Extensions\Chatbot\System\TitanAI\Runtime\AssistantDelegationGraph;
use App\Extensions\Chatbot\System\TitanAI\Runtime\ConfidenceFallbackPolicy;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class TitanAIRuntimeDiagnosticsController extends Controller
{
    public function __construct(
        private readonly WorkerRouteRegistry $workers,
        private readonly AgentExecutionPathResolver $executionPaths,
        private readonly AssistantDelegationGraph $delegations,
        private readonly ConfidenceFallbackPolicy $fallbacks,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            ...$this->workers->diagnostics($this->executionPaths, $this->delegations),
            'confidence_thresholds' => $this->fallbacks->thresholds(),
        ]);
    }
}
