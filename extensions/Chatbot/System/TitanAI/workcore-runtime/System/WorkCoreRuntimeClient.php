<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore;

use App\Domains\WorkCore\System\AI\DTO\{AiToolExecutionContext,AiToolResult,ModelMessage,ModelResponse};
use App\Domains\WorkCore\System\AI\Providers\NativeModelGateway;
use App\Domains\WorkCore\System\AI\Tools\{AiToolRouter,NativeToolCatalogue};
use App\Services\AI\Contracts\AIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;

final class WorkCoreRuntimeClient
{
    public function __construct(
        private NativeModelGateway $models,
        private NativeToolCatalogue $tools,
        private AiToolRouter $router,
        private WorkerModelRequestFactory $requests,
    ) {}

    /**
     * @param class-string<AIWorker> $worker
     * @param list<ModelMessage> $messages
     * @param array<string,mixed> $metadata
     */
    public function generate(
        string $worker,
        WorkerExecutionContext $context,
        array $messages,
        string $purpose,
        ?string $system = null,
        array $metadata = [],
    ): ModelResponse {
        $toolDefinitions = $this->tools->modelTools(
            $context->companyId,
            $context->actorId,
            $context->channel,
            $context->workerId,
        );

        return $this->models->generate($this->requests->make(
            worker: $worker,
            context: $context,
            messages: $messages,
            purpose: $purpose,
            system: $system,
            tools: $toolDefinitions,
            metadata: $metadata,
        ));
    }

    /** @param array<string,mixed> $input */
    public function executeTool(
        string $toolName,
        array $input,
        WorkerExecutionContext $context,
        ?string $aiRunPublicId = null,
        ?string $confirmationId = null,
        ?string $idempotencyKey = null,
    ): AiToolResult {
        return $this->router->execute(
            $toolName,
            $input,
            new AiToolExecutionContext(
                companyId: $context->companyId,
                actorId: $context->actorId,
                aiRunPublicId: $aiRunPublicId,
                channel: $context->channel,
                agent: $context->workerId,
                confirmationId: $confirmationId,
                idempotencyKey: $idempotencyKey,
                source: 'titan_ai.cleaning',
            ),
        );
    }
}
