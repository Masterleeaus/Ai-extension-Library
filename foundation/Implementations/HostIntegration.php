<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\HostIntegrationContract;
use Foundation\Support\TransactionHelper;
use PDO;
use Foundation\Support\JsonHelper;

class HostIntegration implements HostIntegrationContract
{
    private PDO $db;
    private string $tablePrefix = 'host_integration_';
    private TransactionHelper $transactions;

    public function __construct(PDO $db, ?TransactionHelper $transactions = null)
    {
        $this->db = $db;
        $this->transactions = $transactions ?? new TransactionHelper($db);
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
            gmdate('c'),
        ]);

        return $deploymentId;
    }

    public function deployToHost(
        string $tenantId,
        string $deploymentId,
        array $releaseConfig
    ): string {
        $releaseId = bin2hex(random_bytes(16));

        return $this->transactions->executeInTransaction(
            function (PDO $db) use ($releaseId, $tenantId, $deploymentId, $releaseConfig) {
                // Insert deployment record
                $stmt = $db->prepare(
                    "INSERT INTO {$this->tablePrefix}deployments (id, tenant_id, deployment_target_id, config, status, deployed_at)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                if (!$stmt->execute([
                    $releaseId,
                    $tenantId,
                    $deploymentId,
                    json_encode($releaseConfig),
                    'in_progress',
                    gmdate('c'),
                ])) {
                    throw new \Exception('Failed to insert deployment record');
                }

                // Update deployment target status
                $updateStmt = $db->prepare(
                    "UPDATE {$this->tablePrefix}deployment_targets SET status = ? WHERE id = ? AND tenant_id = ?"
                );

                if (!$updateStmt->execute(['in_use', $deploymentId, $tenantId])) {
                    throw new \Exception('Failed to update deployment target status');
                }

                return $releaseId;
            },
            'deployToHost',
            ['deployment_id' => $deploymentId]
        );
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
            "INSERT INTO {$this->tablePrefix}pilot_gates (id, tenant_id, deployment_id, config, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $gateId,
            $tenantId,
            $deploymentId,
            json_encode($gateConfig),
            'pending',
            gmdate('c'),
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
        return $this->transactions->executeInTransaction(
            function (PDO $db) use ($tenantId, $gateId, $approver) {
                // Update pilot gate approval
                $stmt = $db->prepare(
                    "UPDATE {$this->tablePrefix}pilot_gates SET status = ?, approver_id = ?, approved_at = ? WHERE id = ? AND tenant_id = ?"
                );

                if (!$stmt->execute(['approved', $approver, gmdate('c'), $gateId, $tenantId])) {
                    throw new \Exception('Failed to approve pilot gate');
                }

                // Update associated deployment status to ready for rollout
                $gateStmt = $db->prepare(
                    "SELECT deployment_id FROM {$this->tablePrefix}pilot_gates WHERE id = ? AND tenant_id = ?"
                );

                $gateStmt->execute([$gateId, $tenantId]);
                $gate = $gateStmt->fetch(PDO::FETCH_ASSOC);

                if ($gate) {
                    $updateStmt = $db->prepare(
                        "UPDATE {$this->tablePrefix}deployments SET status = ? WHERE id = ? AND tenant_id = ?"
                    );

                    if (!$updateStmt->execute(['ready_for_rollout', $gate['deployment_id'], $tenantId])) {
                        throw new \Exception('Failed to update deployment status');
                    }
                }

                return true;
            },
            'approvePilotGate',
            ['gate_id' => $gateId]
        );
    }

    public function rolloutRelease(
        string $tenantId,
        string $deploymentId
    ): bool {
        return $this->transactions->executeInTransaction(
            function (PDO $db) use ($tenantId, $deploymentId) {
                $stmt = $db->prepare(
                    "UPDATE {$this->tablePrefix}deployments SET status = ?, rolled_out_at = ? WHERE id = ? AND tenant_id = ?"
                );

                if (!$stmt->execute(['rolled_out', gmdate('c'), $deploymentId, $tenantId])) {
                    throw new \Exception('Failed to rollout release');
                }

                return true;
            },
            'rolloutRelease',
            ['deployment_id' => $deploymentId]
        );
    }

    public function rollbackDeployment(
        string $tenantId,
        string $deploymentId
    ): bool {
        return $this->transactions->executeInTransaction(
            function (PDO $db) use ($tenantId, $deploymentId) {
                $stmt = $db->prepare(
                    "UPDATE {$this->tablePrefix}deployments SET status = ?, rolled_back_at = ? WHERE id = ? AND tenant_id = ?"
                );

                if (!$stmt->execute(['rolled_back', gmdate('c'), $deploymentId, $tenantId])) {
                    throw new \Exception('Failed to rollback deployment');
                }

                return true;
            },
            'rollbackDeployment',
            ['deployment_id' => $deploymentId]
        );
    }
}
