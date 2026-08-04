<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class BulkOperationsAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'bulk-operations-agent'; }
    public static function name(): string { return 'Bulk Operations Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Execute bulk operations across multiple items',
            'Process batches with error handling',
            'Support operations: update, delete, create, assign',
            'Track batch progress and results',
            'Handle partial success scenarios',
            'Implement batch rollback capabilities',
            'Schedule batch jobs for later execution',
            'Generate batch execution reports',
            'Support bulk status updates'
        ];
    }
    public static function permissions(): array
    {
        return [
            'jobs.read',
            'jobs.write',
            'customers.read',
            'customers.write',
            'analytics.write'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'bulk.execute','atomic'=>false]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $operationType = $input->operationType ?? null;
        $itemIds = $input->itemIds ?? [];
        if (!$operationType || empty($itemIds)) throw new \InvalidArgumentException('BulkOperationsAgent requires operationType and itemIds.');
        
        $result = $this->runtime->executeTool('bulk.execute', ['operation_type' => $operationType, 'item_ids' => $itemIds], $context);
        return AgentActionResult::fromTool($result, 'bulk.completed', []);
    }
}
