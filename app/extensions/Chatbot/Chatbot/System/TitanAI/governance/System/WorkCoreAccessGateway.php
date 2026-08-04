<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System;

use App\Extensions\TitanAIGovernance\System\Contracts\WorkCoreGateway;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Extensions\Chatbot\System\TitanAI\Runtime\WorkCoreToolMappingRegistry;
use RuntimeException;

final class WorkCoreAccessGateway implements WorkCoreGateway
{
    public function __construct(
        private readonly WorkCoreRuntimeClient $runtime,
        private readonly WorkCoreToolMappingRegistry $toolMappings,
    ) {}

    public function read(string $domain, string $operation, array $payload): array
    {
        return $this->call($domain, $operation, $payload);
    }

    public function execute(string $domain, string $operation, array $payload): array
    {
        return $this->call($domain, $operation, $payload);
    }

    private function call(string $domain, string $operation, array $payload): array
    {
        $governance = (array) ($payload['_governance'] ?? []);
        $companyId = (int) ($governance['tenant_id'] ?? $payload['tenant_id'] ?? 0);
        $actorId = (int) ($governance['user_id'] ?? $payload['user_id'] ?? 0);
        if ($companyId < 1 || $actorId < 1) {
            throw new RuntimeException('WorkCore execution requires valid tenant and actor context.');
        }

        $tool = $this->toolMappings->toolFor($domain, $operation);
        unset($payload['_governance']);
        $context = new WorkerExecutionContext(
            companyId: $companyId,
            actorId: $actorId,
            workerId: (string) ($governance['agent'] ?? $payload['_agent'] ?? 'titan-ai-governed-agent'),
            workerType: 'agent',
            tier: 3,
            delegatedByWorkerId: $governance['manager'] ?? null,
            conversationId: $governance['conversation_id'] ?? null,
            workflowId: $governance['workflow_id'] ?? null,
            channel: (string) ($governance['channel'] ?? 'chatbot-http'),
        );

        $result = $this->runtime->executeTool(
            $tool,
            $payload,
            $context,
            null,
            $payload['confirmation_id'] ?? null,
            $governance['idempotency_key'] ?? null,
        );

        return $result->toArray();
    }

}
