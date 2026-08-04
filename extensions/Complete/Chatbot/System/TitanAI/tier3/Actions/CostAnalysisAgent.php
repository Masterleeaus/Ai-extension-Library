<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class CostAnalysisAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'cost-analysis-agent'; }
    public static function name(): string { return 'Cost Analysis Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Analyze job costs in detail',
            'Compare pricing against benchmarks',
            'Identify cost optimization opportunities',
            'Track cost trends over time',
            'Calculate profitability metrics',
            'Support what-if analysis',
            'Generate cost breakdown reports',
            'Identify cost outliers and anomalies',
            'Recommend budget optimizations'
        ];
    }
    public static function permissions(): array
    {
        return [
            'jobs.read',
            'pricing.read',
            'analytics.read',
            'analytics.write',
            'reports.generate'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'costs.analyze','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $jobId = $input->jobId ?? null;
        if (!$jobId) throw new \InvalidArgumentException('CostAnalysisAgent requires jobId.');
        
        $result = $this->runtime->executeTool('costs.analyze', ['job_id' => $jobId], $context);
        return AgentActionResult::fromTool($result, 'costs.analyzed', ['CreateInvoiceAgent']);
    }
}
