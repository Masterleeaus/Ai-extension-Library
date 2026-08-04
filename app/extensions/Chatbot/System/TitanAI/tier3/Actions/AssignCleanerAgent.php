<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\DTO\CrewAssignment\AssignCleanerRequest;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class AssignCleanerAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    public static function id(): string { return 'assign-cleaner-agent'; }
    public static function name(): string { return 'Assign Cleaner Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return ['Assign one cleaner to one existing job']; }
    public static function permissions(): array { return ['jobs.read','workforce.read','dispatch.assign']; }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'cleaning.dispatch.assign_cleaner','atomic'=>true]; }
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        if (! $input instanceof AssignCleanerRequest) throw new \InvalidArgumentException('AssignCleanerAgent requires AssignCleanerRequest.');
        $result=$this->runtime->executeTool('cleaning.dispatch.assign_cleaner',$input->toArray(),$context,null,$input->confirmationId,$input->idempotencyKey);
        return AgentActionResult::fromTool($result,'cleaner.assigned',['NotifyCleanerAgent']);
    }
}
