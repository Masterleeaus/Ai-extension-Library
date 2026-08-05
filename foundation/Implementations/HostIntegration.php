<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\HostIntegrationContract;
use PDO;
use Foundation\Support\JsonHelper;

class HostIntegration implements HostIntegrationContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'host_integration_';
    private const TABLE_DEPLOYMENT_TARGETS = self::TABLE_PREFIX . 'deployment_targets';
    private const TABLE_DEPLOYMENTS = self::TABLE_PREFIX . 'deployments';
    private const TABLE_PILOT_GATES = self::TABLE_PREFIX . 'pilot_gates';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function registerDeploymentTarget(
        string $tenantId,
        string $hostEnvironment,
        array $hostConfig
    ): string {
        $deploymentId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_DEPLOYMENT_TARGETS . " (id, tenant_id, environment, config, status, registered_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $deploymentId,
            $tenantId,
            $hostEnvironment,
            json_encode($hostConfig),
            'active',
            DateTimeHelper::now(),
        ]);

        return $deploymentId;
    }

    public function deployToHost(
        string $tenantId,
        string $deploymentId,
        array $releaseConfig
    ): string {
        $releaseId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_DEPLOYMENTS . " (id, tenant_id, deployment_target_id, config, status, deployed_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $releaseId,
            $tenantId,
            $deploymentId,
            json_encode($releaseConfig),
            'in_progress',
            DateTimeHelper::now(),
        ]);

        return $releaseId;
    }

    public function getDeploymentStatus(
        string $tenantId,
        string $deploymentId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_DEPLOYMENTS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$deploymentId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['config'] = JsonHelper::decode($result['config']);
        }

        return $result ?: null;
    }

    public function createPilotGate(
        string $tenantId,
        string $deploymentId,
        array $gateConfig
    ): string {
        $gateId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_PILOT_GATES . " (id, tenant_id, deployment_id, config, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $gateId,
            $tenantId,
            $deploymentId,
            json_encode($gateConfig),
            'pending',
            DateTimeHelper::now(),
        ]);

        return $gateId;
    }

    public function validatePilotGate(
        string $tenantId,
        string $gateId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_PILOT_GATES . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$gateId, $tenantId]);
        $gate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gate) {
            return ['valid' => false, 'errors' => ['Gate not found']];
        }

        $config = JsonHelper::decode($gate['config']);
        $errors = [];

        if (empty($config['approval_criteria'])) {
            $errors[] = 'Approval criteria not defined';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'gate_id' => $gateId,
        ];
    }

    public function approvePilotGate(
        string $tenantId,
        string $gateId,
        string $approver
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_PILOT_GATES . " SET status = ?, approver_id = ?, approved_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['approved', $approver, DateTimeHelper::now(), $gateId, $tenantId]);
    }

    public function rolloutRelease(
        string $tenantId,
        string $deploymentId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_DEPLOYMENTS . " SET status = ?, rolled_out_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['rolled_out', DateTimeHelper::now(), $deploymentId, $tenantId]);
    }

    public function rollbackDeployment(
        string $tenantId,
        string $deploymentId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_DEPLOYMENTS . " SET status = ?, rolled_back_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['rolled_back', DateTimeHelper::now(), $deploymentId, $tenantId]);
    }
}
