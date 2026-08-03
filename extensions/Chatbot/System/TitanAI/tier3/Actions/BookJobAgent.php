<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\DTO\JobManagement\BookJobRequest;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class BookJobAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    public static function id(): string { return 'book-job-agent'; }
    public static function name(): string { return 'Book Job Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return ['Create one confirmed cleaning job']; }
    public static function permissions(): array { return ['customers.read','properties.read','jobs.create']; }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'cleaning.jobs.book','atomic'=>true]; }
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        if (! $input instanceof BookJobRequest) throw new \InvalidArgumentException('BookJobAgent requires BookJobRequest.');
        $result=$this->runtime->executeTool('cleaning.jobs.book',$input->toArray(),$context,null,$input->confirmationId,$input->idempotencyKey);
        return AgentActionResult::fromTool($result,'job.booked',['AssignCleanerAgent','SendCustomerMessageAgent']);
    }
}
