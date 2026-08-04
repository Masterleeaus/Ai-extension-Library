<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use App\Extensions\Chatbot\System\TitanAI\Capabilities\SharedCapabilityRegistry;
use App\Extensions\Chatbot\System\TitanAI\Contracts\AssistantDeploymentResolverContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\GeneralConversationEngineContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\KnowledgeContextProviderContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\MemoryContextProviderContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\TitanAIRuntimeContract;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIRequest;
use App\Extensions\Chatbot\System\TitanAI\DTO\TitanAIResponse;
use Illuminate\Support\Str;
use Throwable;

final class UnifiedTitanAIRuntime implements TitanAIRuntimeContract
{
    public function __construct(
        private readonly FiveTierOrchestrator $orchestrator,
        private readonly GeneralConversationEngineContract $conversationEngine,
        private readonly AssistantDeploymentResolverContract $deployments,
        private readonly MemoryContextProviderContract $memory,
        private readonly KnowledgeContextProviderContract $knowledge,
        private readonly SharedCapabilityRegistry $capabilities,
    ) {}

    public function execute(TitanAIRequest $request): TitanAIResponse
    {
        $runUuid = (string) Str::uuid();
        $deployment = $this->deployments->resolve($request);
        $capabilities = $this->capabilities->all((array) ($deployment['capabilities'] ?? []));
        $messages = [
            ...$this->knowledge->context($request),
            ...$this->memory->context($request),
        ];

        $result = $this->orchestrator->handle(
            $request->message,
            [
                ...$request->payload,
                'messages' => $messages,
                'attachments' => $request->attachments,
                'assistant_deployment' => $deployment,
                'capabilities' => $capabilities,
            ],
            [...$request->context(), 'ai_run_uuid' => $runUuid],
        );

        $handled = (bool) ($result['handled'] ?? false);
        if ($handled) {
            return $this->businessActionResponse($request, $runUuid, $deployment, $capabilities, $result);
        }

        try {
            $general = $this->conversationEngine->complete($request, [
                'run_uuid' => $runUuid,
                'deployment' => $deployment,
                'capabilities' => $capabilities,
                'messages' => $messages,
            ]);

            return new TitanAIResponse(
                runUuid: $runUuid,
                status: 'completed',
                handled: true,
                message: $this->scalar($general['message'] ?? null),
                data: [
                    'execution_mode' => 'conversation',
                    'conversation_source' => $request->conversationSource,
                    'conversation_id' => $request->conversationId,
                    'assistant_deployment_id' => $deployment['id'] ?? null,
                    'assistant' => $deployment,
                    'channel' => $request->channel,
                    'provider' => $general['provider'] ?? ($deployment['provider'] ?? null),
                    'model' => $general['model'] ?? ($deployment['model'] ?? null),
                    'capabilities' => $capabilities,
                    'tool_calls' => $general['tool_calls'] ?? [],
                    'citations' => $general['citations'] ?? [],
                    'generative_ui' => $general['generative_ui'] ?? null,
                    'usage' => $general['usage'] ?? [],
                    'requires_legacy_fallback' => false,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            return new TitanAIResponse(
                runUuid: $runUuid,
                status: 'failed',
                handled: false,
                message: 'Titan AI could not complete this request because no shared conversation provider is available.',
                data: [
                    'execution_mode' => 'conversation',
                    'conversation_source' => $request->conversationSource,
                    'conversation_id' => $request->conversationId,
                    'assistant_deployment_id' => $deployment['id'] ?? null,
                    'assistant' => $deployment,
                    'channel' => $request->channel,
                    'capabilities' => $capabilities,
                    'error_code' => 'shared_provider_unavailable',
                    'requires_legacy_fallback' => false,
                ],
            );
        }
    }

    public function stream(TitanAIRequest $request, callable $emit): TitanAIResponse
    {
        $emit(['type' => 'run.started', 'channel' => $request->channel]);
        $response = $this->execute($request);
        if ($response->message !== null && $response->message !== '') {
            $emit(['type' => 'content.delta', 'delta' => $response->message]);
        }
        if (($response->data['generative_ui'] ?? null) !== null) {
            $emit(['type' => 'generative_ui', 'data' => $response->data['generative_ui']]);
        }
        $emit(['type' => 'run.result', 'data' => $response->toArray()]);
        $emit(['type' => 'run.completed', 'run_uuid' => $response->runUuid, 'status' => $response->status]);

        return $response;
    }

    /** @param array<string,mixed> $deployment @param array<int,array<string,mixed>> $capabilities @param array<string,mixed> $result */
    private function businessActionResponse(TitanAIRequest $request, string $runUuid, array $deployment, array $capabilities, array $result): TitanAIResponse
    {
        return new TitanAIResponse(
            runUuid: $runUuid,
            status: (string) ($result['status'] ?? 'completed'),
            handled: true,
            message: $this->extractMessage($result),
            data: [
                'execution_mode' => 'business_action',
                'conversation_source' => $request->conversationSource,
                'conversation_id' => $request->conversationId,
                'assistant_deployment_id' => $deployment['id'] ?? null,
                'assistant' => $deployment,
                'channel' => $request->channel,
                'route' => $result['route'] ?? null,
                'result' => $result['result'] ?? null,
                'approval_id' => $result['approval_id'] ?? null,
                'reason' => $result['reason'] ?? null,
                'capabilities' => $capabilities,
                'requires_legacy_fallback' => false,
            ],
        );
    }

    private function extractMessage(array $result): ?string
    {
        $value = $result['result']['message'] ?? $result['result']['text'] ?? $result['message'] ?? null;
        if (($message = $this->scalar($value)) !== null) return $message;
        if (($result['status'] ?? null) === 'needs_review') return (string) ($result['reason'] ?? 'This action requires approval.');
        return 'Titan AI completed the requested operation.';
    }

    private function scalar(mixed $value): ?string
    {
        if (! is_scalar($value)) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
