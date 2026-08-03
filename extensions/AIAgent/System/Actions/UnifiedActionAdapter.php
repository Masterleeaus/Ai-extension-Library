<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Actions;

use App\Domains\TitanAI\ActionCompleted;
use App\Domains\TitanAI\ActionFailed;
use App\Domains\TitanAI\ActionInvoked;
use App\Domains\TitanAI\Contracts\ActionDefinition;
use App\Domains\TitanAI\Events\TitanAIEventBus;
use App\Extensions\AIAgent\System\Actions\Contracts\AIAgentActionInterface;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflow;
use App\Extensions\AIAgent\System\Models\AIAgentWorkflowRun;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Preserves the native workflow action API while exposing shared discovery.
 */
final class UnifiedActionAdapter implements ActionDefinition
{
    public function __construct(
        private readonly string $actionKey,
        private readonly AIAgentActionInterface $action,
        private readonly ?TitanAIEventBus $events = null,
    ) {}

    public function key(): string
    {
        return $this->actionKey;
    }

    public function name(): string
    {
        return $this->action->getLabel();
    }

    public function description(): string
    {
        return $this->action->getDescription();
    }

    public function metadata(): array
    {
        return [
            'source' => 'aiagent',
            'category' => $this->action->getCategory(),
            'icon' => $this->action->getIcon(),
            'native_class' => $this->action::class,
            'requires_workflow_context' => true,
        ];
    }

    public function execute(array $payload, array $context = []): mixed
    {
        $workflow = $context['workflow'] ?? null;
        $run = $context['run'] ?? null;
        if (! $workflow instanceof AIAgentWorkflow || ! $run instanceof AIAgentWorkflowRun) {
            throw new InvalidArgumentException(
                'Unified AI Agent execution requires workflow and run objects in the context array.',
            );
        }

        $state = is_array($context['state'] ?? null) ? $context['state'] : [];
        $config = is_array($payload['config'] ?? null) ? $payload['config'] : $payload;
        $userId = isset($workflow->user_id) ? (string) $workflow->user_id : null;
        $correlationId = trim((string) ($context['correlation_id'] ?? ''));
        if ($correlationId === '') {
            $correlationId = bin2hex(random_bytes(16));
        }
        $metadata = [
            'adapter' => self::class,
            'native_class' => $this->action::class,
            'correlation_id' => $correlationId,
        ];

        $this->publish(
            new ActionInvoked('aiagent', $this->actionKey, $userId, $config, $metadata),
            "action:{$correlationId}:invoked",
        );

        try {
            $result = $this->action->execute($config, $state, $workflow, $run);
            $this->publish(
                new ActionCompleted('aiagent', $this->actionKey, $userId, $result, $metadata),
                "action:{$correlationId}:completed",
            );
            return $result;
        } catch (Throwable $exception) {
            $this->publish(
                new ActionFailed('aiagent', $this->actionKey, $userId, $exception, $config, $metadata),
                "action:{$correlationId}:failed",
            );
            throw $exception;
        }
    }

    private function publish(object $event, string $idempotencyKey): void
    {
        if ($this->events !== null) {
            $this->events->dispatch($event, $idempotencyKey);
            return;
        }

        try {
            Event::dispatch($event);
        } catch (Throwable $exception) {
            Log::warning('TitanAI lifecycle listener failed; AI Agent execution was isolated.', [
                'event' => $event::class,
                'idempotency_key' => $idempotencyKey,
                'exception' => $exception,
            ]);
            if ((bool) config('titanai.events.strict', false)) {
                throw $exception;
            }
        }
    }

    public function inputSchema(): array
    {
        return $this->action->getConfigSchema();
    }

    public function outputSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => true,
            'description' => 'Updated AI Agent workflow context.',
        ];
    }
}
