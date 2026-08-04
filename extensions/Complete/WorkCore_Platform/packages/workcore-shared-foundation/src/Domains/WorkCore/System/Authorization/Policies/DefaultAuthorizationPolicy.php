<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Authorization\Policies;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;

final class DefaultAuthorizationPolicy implements
    ToolAuthorizationPolicyContract,
    WorkflowAuthorizationPolicyContract,
    ConnectorAuthorizationPolicyContract,
    KnowledgeIngestionPolicyContract
{
    public function __construct(private array $denialReasons = []) {}

    public function canExecuteTool(
        string $toolName,
        OperationContextContract $context,
        array $parameters = [],
    ): bool {
        return $context->hasContext() && $context->actorId() !== null;
    }

    public function getDenialReason(
        string $toolName,
        OperationContextContract $context,
    ): ?string {
        if (!$context->hasContext()) {
            return 'Operation context is not set';
        }
        if ($context->actorId() === null) {
            return 'Actor identity is not set';
        }
        return $this->denialReasons[$toolName] ?? null;
    }

    public function canExecuteWorkflow(
        string $workflowName,
        OperationContextContract $context,
        array $workflowData = [],
    ): bool {
        return $context->hasContext() && $context->actorId() !== null;
    }

    public function canExecuteAction(
        string $actionName,
        OperationContextContract $context,
        array $actionData = [],
    ): bool {
        return $context->hasContext() && $context->actorId() !== null;
    }

    public function getDenialReasonForWorkflow(
        string $workflowName,
        string $actionName,
        OperationContextContract $context,
    ): ?string {
        if (!$context->hasContext()) {
            return 'Operation context is not set';
        }
        if ($context->actorId() === null) {
            return 'Actor identity is not set';
        }
        $key = "{$workflowName}.{$actionName}";
        return $this->denialReasons[$key] ?? null;
    }

    public function canUseConnector(
        string $connectorName,
        OperationContextContract $context,
        array $connectorConfig = [],
    ): bool {
        return $context->hasContext() && $context->actorId() !== null;
    }

    public function canExecuteConnectorAction(
        string $connectorName,
        string $actionName,
        OperationContextContract $context,
        array $actionData = [],
    ): bool {
        return $context->hasContext() && $context->actorId() !== null;
    }

    public function getDenialReasonForConnector(
        string $connectorName,
        OperationContextContract $context,
    ): ?string {
        if (!$context->hasContext()) {
            return 'Operation context is not set';
        }
        if ($context->actorId() === null) {
            return 'Actor identity is not set';
        }
        return $this->denialReasons[$connectorName] ?? null;
    }

    public function canIngestKnowledge(
        string $knowledgeSourceName,
        OperationContextContract $context,
        array $sourceConfig = [],
    ): bool {
        return $context->hasContext() && $context->actorId() !== null;
    }

    public function canAccessKnowledgeSource(
        string $knowledgeSourceName,
        OperationContextContract $context,
    ): bool {
        return $context->hasContext() && $context->actorId() !== null;
    }

    public function getDenialReasonForKnowledge(
        string $knowledgeSourceName,
        OperationContextContract $context,
    ): ?string {
        if (!$context->hasContext()) {
            return 'Operation context is not set';
        }
        if ($context->actorId() === null) {
            return 'Actor identity is not set';
        }
        return $this->denialReasons[$knowledgeSourceName] ?? null;
    }
}
