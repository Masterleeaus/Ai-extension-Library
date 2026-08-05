<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkflowEngineContract;
use PDO;

class WorkflowEngine implements WorkflowEngineContract
{
    private PDO $db;
    private string $tablePrefix = 'workflow_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createWorkflow(
        string $tenantId,
        string $workflowName,
        array $steps,
        array $metadata = []
    ): string {
        $workflowId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}definitions (id, tenant_id, name, steps, metadata, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $workflowId,
            $tenantId,
            $workflowName,
            json_encode($steps),
            json_encode($metadata),
            DateTimeHelper::now(),
        ]);

        return $workflowId;
    }

    public function executeWorkflow(
        string $tenantId,
        string $workflowId,
        array $input
    ): string {
        $executionId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}executions (id, tenant_id, workflow_id, input, status, started_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $executionId,
            $tenantId,
            $workflowId,
            json_encode($input),
            'running',
            DateTimeHelper::now(),
        ]);

        return $executionId;
    }

    public function getWorkflowStatus(
        string $tenantId,
        string $executionId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}executions WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$executionId, $tenantId]);
        $execution = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($execution) {
            $execution['input'] = json_decode($execution['input'], true);

            $stepStmt = $this->db->prepare(
                "SELECT * FROM {$this->tablePrefix}execution_steps WHERE execution_id = ? ORDER BY step_order ASC"
            );

            $stepStmt->execute([$executionId]);
            $execution['steps'] = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($execution['steps'] as &$step) {
                $step['input'] = json_decode($step['input'], true);
                $step['output'] = $step['output'] ? json_decode($step['output'], true) : null;
            }
        }

        return $execution ?: null;
    }

    public function pauseExecution(
        string $tenantId,
        string $executionId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}executions SET status = ?, paused_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['paused', DateTimeHelper::now(), $executionId, $tenantId]);
    }

    public function resumeExecution(
        string $tenantId,
        string $executionId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}executions SET status = ?, resumed_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['running', DateTimeHelper::now(), $executionId, $tenantId]);
    }

    public function cancelExecution(
        string $tenantId,
        string $executionId,
        string $reason
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}executions SET status = ?, cancel_reason = ?, cancelled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['cancelled', $reason, DateTimeHelper::now(), $executionId, $tenantId]);
    }

    public function getStepOutput(
        string $tenantId,
        string $executionId,
        string $stepId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}execution_steps WHERE id = ? AND execution_id = ? AND tenant_id = ?"
        );

        $stmt->execute([$stepId, $executionId, $tenantId]);
        $step = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($step) {
            $step['input'] = json_decode($step['input'], true);
            $step['output'] = $step['output'] ? json_decode($step['output'], true) : null;
        }

        return $step ?: null;
    }

    public function listExecutions(
        string $tenantId,
        string $workflowId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}executions WHERE tenant_id = ? AND workflow_id = ? ORDER BY started_at DESC"
        );

        $stmt->execute([$tenantId, $workflowId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
