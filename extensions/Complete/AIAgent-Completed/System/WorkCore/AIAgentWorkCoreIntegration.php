<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\WorkCore;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Authorization\Policies\WorkflowAuthorizationPolicyContract;

final class AIAgentWorkCoreIntegration
{
    public function __construct(
        private TenantContextContract $tenantContext,
        private OperationContextContract $operationContext,
        private WorkflowAuthorizationPolicyContract $workflowPolicy,
    ) {}

    public function resolveWorkflowContext(string $workflowName): array
    {
        return [
            'tenantId' => $this->tenantContext->companyId(),
            'userId' => $this->tenantContext->userId(),
            'actorId' => $this->operationContext->actorId(),
            'actorSubject' => $this->operationContext->actorSubject(),
            'correlationId' => $this->operationContext->correlationId(),
            'workflowName' => $workflowName,
            'autonomous' => true,
        ];
    }

    public function canExecuteWorkflow(string $workflowName, array $workflowData = []): bool
    {
        return $this->workflowPolicy->canExecuteWorkflow(
            $workflowName,
            $this->operationContext,
            $workflowData
        );
    }

    public function canExecuteAction(string $actionName, array $actionData = []): bool
    {
        return $this->workflowPolicy->canExecuteAction(
            $actionName,
            $this->operationContext,
            $actionData
        );
    }

    public function isGovernedAction(string $actionName): bool
    {
        // High-risk actions require explicit governance
        $governedActions = [
            'delete', 'update_critical', 'send_notification', 'charge_customer',
            'modify_permissions', 'access_sensitive_data', 'external_api_call'
        ];
        return in_array(strtolower($actionName), $governedActions);
    }

    public function requiresApproval(string $actionName): bool
    {
        // Financial and sensitive actions require approval
        $requiresApproval = [
            'charge_customer', 'refund', 'delete_permanent', 'modify_permissions'
        ];
        return in_array(strtolower($actionName), $requiresApproval);
    }

    public function getDenialReason(string $workflowName, string $actionName): ?string
    {
        return $this->workflowPolicy->getDenialReasonForWorkflow(
            $workflowName,
            $actionName,
            $this->operationContext
        );
    }

    public function getWorkCoreContext(): array
    {
        return [
            'tenantId' => $this->tenantContext->companyId(),
            'userId' => $this->tenantContext->userId(),
            'actorId' => $this->operationContext->actorId(),
            'actorSubject' => $this->operationContext->actorSubject(),
            'workerId' => $this->operationContext->workerId(),
            'branchId' => $this->operationContext->branchId(),
            'territoryId' => $this->operationContext->territoryId(),
            'locale' => $this->operationContext->locale(),
            'timezone' => $this->operationContext->timezone(),
            'correlationId' => $this->operationContext->correlationId(),
            'causationId' => $this->operationContext->causationId(),
            'autonomous' => true,
        ];
    }

    public function getRateLimitBudget(string $actionType): array
    {
        // Define rate limits per action type
        return match ($actionType) {
            'api_call' => ['limit' => 100, 'window' => 3600], // 100 calls/hour
            'notification' => ['limit' => 50, 'window' => 3600], // 50 notifications/hour
            'workflow' => ['limit' => 20, 'window' => 3600], // 20 workflows/hour
            default => ['limit' => 1000, 'window' => 3600],
        };
    }
}
