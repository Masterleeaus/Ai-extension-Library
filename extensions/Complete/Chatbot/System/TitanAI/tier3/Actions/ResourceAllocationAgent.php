<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class ResourceAllocationAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'resource-allocation-agent'; }
    public static function name(): string { return 'Resource Allocation Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Allocate resources optimally across jobs',
            'Balance workload across team members',
            'Maximize resource utilization',
            'Consider skill matching and certifications',
            'Handle conflicting resource requests',
            'Support real-time reallocation',
            'Track resource allocation metrics',
            'Predict resource requirements',
            'Generate resource utilization reports'
        ];
    }
    public static function permissions(): array
    {
        return [
            'jobs.read',
            'workers.read',
            'assignments.create',
            'assignments.update',
            'analytics.write'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'resources.allocate','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $jobIds = $input->jobIds ?? [];
        if (empty($jobIds)) throw new \InvalidArgumentException('ResourceAllocationAgent requires jobIds.');
        
        $result = $this->runtime->executeTool('resources.allocate', ['job_ids' => $jobIds], $context);
        return AgentActionResult::fromTool($result, 'resources.allocated', ['AssignCleanerAgent', 'AllocateEquipmentAgent']);
    }
}
