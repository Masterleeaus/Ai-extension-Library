<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ConnectorRuntimeContract;
use PDO;
use Foundation\Support\JsonHelper;

class ConnectorRuntime implements ConnectorRuntimeContract
{
    private PDO $db;
    private string $tablePrefix = 'connectors_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function register(
        string $tenantId,
        string $connectorName,
        string $connectorType,
        array $config
    ): string {
        $connectorId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}registry (id, tenant_id, name, type, config, registered_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $connectorId,
            $tenantId,
            $connectorName,
            $connectorType,
            json_encode($config),
            gmdate('c'),
        ]);

        return $connectorId;
    }

    public function connect(
        string $tenantId,
        string $connectorId,
        array $credentials = []
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}registry
             SET status = ?, credentials = ?, connected_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([
            'connected',
            json_encode($credentials),
            gmdate('c'),
            $connectorId,
            $tenantId,
        ]);
    }

    public function disconnect(
        string $tenantId,
        string $connectorId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}registry
             SET status = ?, disconnected_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([
            'disconnected',
            gmdate('c'),
            $connectorId,
            $tenantId,
        ]);
    }

    public function execute(
        string $tenantId,
        string $connectorId,
        string $operation,
        array $params = []
    ): array {
        $executionId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}executions (id, tenant_id, connector_id, operation, params, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $executionId,
            $tenantId,
            $connectorId,
            $operation,
            json_encode($params),
            'running',
            gmdate('c'),
        ]);

        return [
            'execution_id' => $executionId,
            'status' => 'running',
        ];
    }

    public function getStatus(
        string $tenantId,
        string $connectorId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}registry WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$connectorId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['config'] = JsonHelper::decode($result['config']);
        }

        return $result ?: null;
    }

    public function listConnectors(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}registry WHERE tenant_id = ?"
        );

        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
