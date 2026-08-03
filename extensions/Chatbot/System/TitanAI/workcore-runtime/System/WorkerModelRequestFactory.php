<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore;

use App\Domains\WorkCore\System\AI\DTO\{ModelMessage,ModelRequest};
use App\Services\AI\Contracts\AIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use InvalidArgumentException;

final class WorkerModelRequestFactory
{
    /**
     * @param class-string<AIWorker> $worker
     * @param list<ModelMessage> $messages
     * @param list<array<string,mixed>> $tools
     * @param array<string,mixed> $metadata
     */
    public function make(
        string $worker,
        WorkerExecutionContext $context,
        array $messages,
        string $purpose,
        ?string $system = null,
        array $tools = [],
        array $metadata = [],
        string $responseFormat = 'text',
        float $temperature = 0.2,
        int $maxOutputTokens = 1024,
    ): ModelRequest {
        if (! is_subclass_of($worker, AIWorker::class)) {
            throw new InvalidArgumentException("Worker [{$worker}] must implement AIWorker.");
        }
        if ($worker::id() !== $context->workerId || $worker::tier() !== $context->tier) {
            throw new InvalidArgumentException('Worker execution context does not match the selected worker.');
        }

        return new ModelRequest(
            companyId: $context->companyId,
            actorId: $context->actorId,
            messages: $messages,
            purpose: $purpose,
            system: $system,
            tools: $tools,
            responseFormat: $responseFormat,
            temperature: $temperature,
            maxOutputTokens: $maxOutputTokens,
            threadPublicId: $context->conversationId,
            agentPublicId: $context->workerId,
            metadata: array_replace($context->metadata(), $metadata),
        );
    }
}
