<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use App\Extensions\TitanAIGovernance\System\Exceptions\GovernanceBlocked;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolDefinition;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolExecutor;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

final class FiveTierOrchestrator
{
    public function __construct(
        private readonly IntentGateway $intents,
        private readonly GovernedToolExecutor $executor,
        private readonly Container $container,
        private readonly WorkerRouteRegistry $workers,
        private readonly WorkerPermissionGuard $permissions,
        private readonly DynamicWorkerSelector $selector,
        private readonly AgentExecutionPathResolver $executionPaths,
        private readonly AssistantChainPlanner $chains,
    ) {}

    /** @param array<string,mixed> $payload @param array<string,mixed> $context */
    public function handle(string $message, array $payload, array $context): array
    {
        $decision = $this->intents->classify($message);
        if (! $decision->isBusinessAction()) {
            return ['handled' => false, 'route' => $decision->toArray()];
        }

        $this->assertContext($context);
        [$decision, $chain] = $this->resolveWorkers($decision);
        $this->assertWorkerExists($decision);
        $executionPath = $this->executionPaths->resolve(
            (string) $decision->agent,
            (string) $decision->domain,
            (string) $decision->operation,
        );
        $this->permissions->assertAllowed($decision->permissions, $context);

        $tool = new GovernedToolDefinition(
            name: (string) $decision->agent,
            domain: (string) $decision->domain,
            operation: (string) $decision->operation,
            riskLevel: $decision->riskLevel,
            permissions: $decision->permissions,
            audited: true,
            idempotent: true,
            rollbackSupported: in_array($decision->intent, ['book_job','reschedule_job','assign_cleaner'], true),
        );

        try {
            $result = $this->executor->execute($tool, $payload, [
                ...$context,
                'manager' => $this->workers->canonicalId((string) $decision->manager),
                'assistant' => $this->workers->canonicalId((string) $decision->assistant),
                'agent' => $this->workers->canonicalId((string) $decision->agent),
                'agent_execution_path' => $executionPath,
                'worker_chain' => $chain,
                'worker_definitions' => [
                    'manager' => $this->workers->definitionFor((string) $decision->manager),
                    'assistant' => $this->workers->definitionFor((string) $decision->assistant),
                    'agent' => $this->workers->definitionFor((string) $decision->agent),
                ],
            ]);
            return ['handled'=>true,'status'=>'executed','route'=>$decision->toArray(),'worker_chain'=>$chain,'result'=>$result];
        } catch (GovernanceBlocked $blocked) {
            return ['handled'=>true,'status'=>'needs_review','route'=>$decision->toArray(),'approval_id'=>$blocked->approvalId,'reason'=>$blocked->reason];
        }
    }

    /** @return array{0:IntentDecision,1:array<string,mixed>} */
    private function resolveWorkers(IntentDecision $decision): array
    {
        $terms = array_values(array_filter([
            $decision->intent, $decision->domain, $decision->operation,
            ...$decision->permissions,
        ], static fn (mixed $value): bool => is_string($value) && $value !== ''));

        $chain = $this->chains->plan($decision, $terms);
        $resolved = new IntentDecision(
            lane: $decision->lane,
            intent: $decision->intent,
            confidence: $decision->confidence,
            manager: (string) $chain['manager'],
            assistant: (string) $chain['assistant'],
            agent: (string) $chain['agent'],
            domain: $decision->domain,
            operation: $decision->operation,
            riskLevel: $decision->riskLevel,
            permissions: $decision->permissions,
        );

        return [$resolved, $chain];
    }

    private function assertContext(array $context): void
    {
        foreach (['tenant_id','user_id','device_id'] as $key) {
            if (! isset($context[$key]) || (string) $context[$key] === '') {
                throw new RuntimeException("Missing required Titan AI context [{$key}].");
            }
        }
    }

    private function assertWorkerExists(IntentDecision $decision): void
    {
        foreach ([$decision->manager, $decision->assistant, $decision->agent] as $workerId) {
            if ($workerId === null || $workerId === '') {
                throw new RuntimeException('Titan AI route contains an empty worker.');
            }
            if (! $this->workers->has($workerId)) {
                throw new RuntimeException("Titan AI route references unregistered worker [{$workerId}].");
            }
        }
    }
}
