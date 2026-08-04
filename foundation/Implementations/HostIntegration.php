<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\HostIntegrationContract;
use PDO;

class HostIntegration implements HostIntegrationContract
{
    private PDO $db;
    private string $tablePrefix = 'host_integration_';

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
            "INSERT INTO {$this->tablePrefix}deployment_targets (id, tenant_id, environment, config, status, registered_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $deploymentId,
            $tenantId,
            $hostEnvironment,
            json_encode($hostConfig),
            'active',
            date('c'),
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
            "INSERT INTO {$this->tablePrefix}deployments (id, tenant_id, deployment_target_id, config, status, deployed_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $releaseId,
            $tenantId,
            $deploymentId,
            json_encode($releaseConfig),
            'in_progress',
            date('c'),
        ]);

        return $releaseId;
    }

    public function getDeploymentStatus(
        string $tenantId,
        string $deploymentId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}deployments WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$deploymentId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['config'] = json_decode($result['config'], true);
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
            "INSERT INTO {$this->tablePrefix}pilot_gates (id, tenant_id, deployment_id, config, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $gateId,
            $tenantId,
            $deploymentId,
            json_encode($gateConfig),
            'pending',
            date('c'),
        ]);

        return $gateId;
    }

    public function validatePilotGate(
        string $tenantId,
        string $gateId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}pilot_gates WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$gateId, $tenantId]);
        $gate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gate) {
            return ['valid' => false, 'errors' => ['Gate not found']];
        }

        $config = json_decode($gate['config'], true);
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
            "UPDATE {$this->tablePrefix}pilot_gates SET status = ?, approver_id = ?, approved_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['approved', $approver, date('c'), $gateId, $tenantId]);
    }

    public function rolloutRelease(
        string $tenantId,
        string $deploymentId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}deployments SET status = ?, rolled_out_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['rolled_out', date('c'), $deploymentId, $tenantId]);
    }

    public function rollbackDeployment(
        string $tenantId,
        string $deploymentId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}deployments SET status = ?, rolled_back_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['rolled_back', date('c'), $deploymentId, $tenantId]);
    }
}
