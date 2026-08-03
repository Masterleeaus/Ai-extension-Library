<?php

declare(strict_types=1);

namespace TitanAI\Hybrid\Orchestration;

use TitanAI\Hybrid\Diagnostics\TitanAIDiagnostics;
use TitanAI\Hybrid\Registries\UnifiedRegistry;
use LogicException;
use Throwable;

/**
 * Structured execution surface for components discovered across extensions.
 */
final class CrossExtensionOrchestrator
{
    public function __construct(
        private readonly UnifiedRegistry $registry,
        private readonly TitanAIDiagnostics $diagnostics,
    ) {}

    /** @return array{ok:bool,type:string,key:string,correlation_id:string,result?:mixed,error_code?:string,message?:string} */
    public function executeAction(string $key, array $payload, array $context = []): array
    {
        if (! (bool) config('titanai.features.cross_extension_actions', true)) {
            return $this->failure('action', $key, 'feature_disabled', new LogicException('Cross-extension actions are disabled.'));
        }

        $action = $this->registry->getAction($key);
        if ($action === null) {
            return $this->failure('action', $key, 'component_not_found', new LogicException("TitanAI action [{$key}] is not registered."));
        }

        $correlationId = $this->correlationId($context);
        try {
            $result = $action->execute($payload, [...$context, 'correlation_id' => $correlationId]);
            $this->diagnostics->recordOrchestration('succeeded', 'action', $key, ['correlation_id' => $correlationId]);
            return [
                'ok' => true,
                'type' => 'action',
                'key' => $key,
                'correlation_id' => $correlationId,
                'result' => $result,
            ];
        } catch (Throwable $exception) {
            return $this->failure('action', $key, 'action_execution_failed', $exception, $correlationId);
        }
    }

    /** @return array{ok:bool,type:string,key:string,correlation_id:string,result?:mixed,error_code?:string,message?:string} */
    public function send(string $key, array $data, array $context = []): array
    {
        if (! (bool) config('titanai.features.cross_extension_connectors', true)) {
            return $this->failure('connector', $key, 'feature_disabled', new LogicException('Cross-extension connectors are disabled.'));
        }

        $connector = $this->registry->getConnector($key);
        if ($connector === null) {
            return $this->failure('connector', $key, 'component_not_found', new LogicException("TitanAI connector [{$key}] is not registered."));
        }
        if (! $connector->isConfigured()) {
            return $this->failure('connector', $key, 'connector_not_configured', new LogicException("TitanAI connector [{$key}] is not configured."));
        }

        $correlationId = $this->correlationId($context);
        try {
            $result = $connector->send($data);
            $this->diagnostics->recordOrchestration('succeeded', 'connector', $key, ['correlation_id' => $correlationId]);
            return [
                'ok' => true,
                'type' => 'connector',
                'key' => $key,
                'correlation_id' => $correlationId,
                'result' => $result,
            ];
        } catch (Throwable $exception) {
            return $this->failure('connector', $key, 'connector_send_failed', $exception, $correlationId);
        }
    }

    /** @return list<array<string,mixed>> */
    public function matchingSkills(string $intent): array
    {
        $matches = [];
        foreach ($this->registry->allSkills() as $skill) {
            if ($skill->canHandle($intent)) {
                $matches[] = [
                    'key' => $skill->key(),
                    'name' => $skill->name(),
                    'description' => $skill->description(),
                    'metadata' => $skill->metadata(),
                ];
            }
        }
        return $matches;
    }

    /** @return array{ok:false,type:string,key:string,correlation_id:string,error_code:string,message:string} */
    private function failure(
        string $type,
        string $key,
        string $errorCode,
        Throwable $exception,
        ?string $correlationId = null,
    ): array {
        $correlationId ??= $this->correlationId([]);
        $this->diagnostics->recordOrchestration('failed', $type, $key, ['correlation_id' => $correlationId], $exception);

        if ((bool) config('titanai.orchestration.rethrow_failures', false)) {
            throw $exception;
        }

        return [
            'ok' => false,
            'type' => $type,
            'key' => $key,
            'correlation_id' => $correlationId,
            'error_code' => $errorCode,
            'message' => match ($errorCode) {
                'feature_disabled', 'component_not_found', 'connector_not_configured' => $exception->getMessage(),
                'action_execution_failed' => 'TitanAI action execution failed.',
                'connector_send_failed' => 'TitanAI connector delivery failed.',
                default => 'TitanAI orchestration failed.',
            },
        ];
    }

    /** @param array<string,mixed> $context */
    private function correlationId(array $context): string
    {
        $provided = trim((string) ($context['correlation_id'] ?? ''));
        return $provided !== '' ? $provided : bin2hex(random_bytes(16));
    }
}
