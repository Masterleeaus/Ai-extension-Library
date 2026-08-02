<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\DTO\Quotation\CreateQuoteRequest;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class CreateQuoteAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    public static function id(): string { return 'create-quote-agent'; }
    public static function name(): string { return 'Create Quote Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return ['Create one governed cleaning quote']; }
    public static function permissions(): array { return ['customers.read','properties.read','quotes.create']; }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'cleaning.quotes.create','atomic'=>true]; }
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        if (! $input instanceof CreateQuoteRequest) throw new \InvalidArgumentException('CreateQuoteAgent requires CreateQuoteRequest.');
        $result=$this->runtime->executeTool('cleaning.quotes.create',$input->toArray(),$context,null,$input->confirmationId,$input->idempotencyKey);
        return AgentActionResult::fromTool($result,'quote.created',['SendQuoteAgent']);
    }
}
