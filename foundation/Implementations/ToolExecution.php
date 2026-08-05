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
    private const TABLE_PREFIX = 'tools_';
    private const TABLE_DEFINITIONS = self::TABLE_PREFIX . 'definitions';
    private const TABLE_EXECUTIONS = self::TABLE_PREFIX . 'executions';
    private const TABLE_HISTORY = self::TABLE_PREFIX . 'history';
    private const TABLE_LIMITS = self::TABLE_PREFIX . 'limits';
    private const TABLE_PERMISSIONS = self::TABLE_PREFIX . 'permissions';
    private string $tablePrefix = self::TABLE_PREFIX;

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
            "INSERT INTO " . self::TABLE_EXECUTIONS . " (id, tenant_id, tool_id, params, context, status, started_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $executionId,
            $tenantId,
            $toolId,
            json_encode($params),
            json_encode($context),
            'running',
            DateTimeHelper::now(),
        ]);

        return [
            'execution_id' => $executionId,
            'status' => 'running',
            'started_at' => DateTimeHelper::now(),
        ];
    }

    public function authorize(
        TenantContextContract $context,
        string $toolId,
        array $params = []
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_PERMISSIONS . "
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
            "SELECT rate_limit FROM " . self::TABLE_LIMITS . "
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
            "INSERT INTO " . self::TABLE_HISTORY . " (tenant_id, tool_id, execution, cost_estimate, recorded_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $tenantId,
            $toolId,
            json_encode($execution),
            $costEstimate,
            DateTimeHelper::now(),
        ]);
    }

    public function listTools(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_DEFINITIONS . " WHERE tenant_id = ? OR is_public = 1"
        );
        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getToolDefinition(
        string $toolId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_DEFINITIONS . " WHERE id = ?"
        );
        $stmt->execute([$toolId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['schema'] = JsonHelper::decode($result['schema']);
        }

        return $result ?: null;
    }
}
