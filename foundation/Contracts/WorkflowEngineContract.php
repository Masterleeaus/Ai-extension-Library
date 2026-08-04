<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WorkflowEngineContract
{
    public function createWorkflow(
        string $tenantId,
        string $workflowName,
        array $steps,
        array $metadata = []
    ): string;

    public function executeWorkflow(
        string $tenantId,
        string $workflowId,
        array $input
    ): string;

    public function getWorkflowStatus(
        string $tenantId,
        string $executionId
    ): ?array;

    public function pauseExecution(
        string $tenantId,
        string $executionId
    ): bool;

    public function resumeExecution(
        string $tenantId,
        string $executionId
    ): bool;

    public function cancelExecution(
        string $tenantId,
        string $executionId,
        string $reason
    ): bool;

    public function getStepOutput(
        string $tenantId,
        string $executionId,
        string $stepId
    ): ?array;

    public function listExecutions(
        string $tenantId,
        string $workflowId
    ): array;
}
