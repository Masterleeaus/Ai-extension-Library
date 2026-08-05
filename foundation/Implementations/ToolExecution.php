<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ToolExecutionContract;
use Foundation\Contracts\TenantContextContract;
use PDO;
use Foundation\Support\JsonHelper;

class ToolExecution implements ToolExecutionContract
{
    private PDO $db;
    private string $tablePrefix = 'tools_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function execute(
        string $tenantId,
        string $toolId,
        array $params = [],
        array $context = []
    ): array {
        $executionId = bin2hex(random_bytes(16));
        $startTime = microtime(true);

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}executions (id, tenant_id, tool_id, params, context, status, started_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $executionId,
            $tenantId,
            $toolId,
            json_encode($params),
            json_encode($context),
            'running',
            date('c'),
        ]);

        return [
            'execution_id' => $executionId,
            'status' => 'running',
            'started_at' => date('c'),
        ];
    }

    public function authorize(
        TenantContextContract $context,
        string $toolId,
        array $params = []
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}permissions
             WHERE tenant_id = ? AND tool_id = ?"
        );

        $stmt->execute([
            $context->getTenantId(),
            $toolId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getRateLimit(
        string $tenantId,
        string $toolId
    ): ?int {
        $stmt = $this->db->prepare(
            "SELECT rate_limit FROM {$this->tablePrefix}limits
             WHERE tenant_id = ? AND tool_id = ?"
        );

        $stmt->execute([$tenantId, $toolId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['rate_limit'] : null;
    }

    public function recordExecution(
        string $tenantId,
        string $toolId,
        array $execution,
        float $costEstimate = 0.0
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}history (tenant_id, tool_id, execution, cost_estimate, recorded_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $tenantId,
            $toolId,
            json_encode($execution),
            $costEstimate,
            date('c'),
        ]);
    }

    public function listTools(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}definitions WHERE tenant_id = ? OR is_public = 1"
        );
        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getToolDefinition(
        string $toolId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}definitions WHERE id = ?"
        );
        $stmt->execute([$toolId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['schema'] = JsonHelper::decode($result['schema']);
        }

        return $result ?: null;
    }
}
