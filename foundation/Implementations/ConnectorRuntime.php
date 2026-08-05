<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ConnectorRuntimeContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class ConnectorRuntime implements ConnectorRuntimeContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'connectors_';
    private const TABLE_EXECUTIONS = self::TABLE_PREFIX . 'executions';
    private const TABLE_REGISTRY = self::TABLE_PREFIX . 'registry';
    private string $tablePrefix = self::TABLE_PREFIX;

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
            "INSERT INTO " . self::TABLE_REGISTRY . " (id, tenant_id, name, type, config, registered_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $connectorId,
            $tenantId,
            $connectorName,
            $connectorType,
            json_encode($config),
            DateTimeHelper::now(),
        ]);

        return $connectorId;
    }

    public function connect(
        string $tenantId,
        string $connectorId,
        array $credentials = []
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_REGISTRY . "
             SET status = ?, credentials = ?, connected_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([
            'connected',
            json_encode($credentials),
            DateTimeHelper::now(),
            $connectorId,
            $tenantId,
        ]);
    }

    public function disconnect(
        string $tenantId,
        string $connectorId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_REGISTRY . "
             SET status = ?, disconnected_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([
            'disconnected',
            DateTimeHelper::now(),
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
            "INSERT INTO " . self::TABLE_EXECUTIONS . " (id, tenant_id, connector_id, operation, params, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $executionId,
            $tenantId,
            $connectorId,
            $operation,
            json_encode($params),
            'running',
            DateTimeHelper::now(),
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
            "SELECT * FROM " . self::TABLE_REGISTRY . " WHERE id = ? AND tenant_id = ?"
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
            "SELECT * FROM " . self::TABLE_REGISTRY . " WHERE tenant_id = ?"
        );

        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
