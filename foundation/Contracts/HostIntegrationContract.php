<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface HostIntegrationContract
{
    public function registerDeploymentTarget(
        string $tenantId,
        string $hostEnvironment,
        array $hostConfig
    ): string;

    public function deployToHost(
        string $tenantId,
        string $deploymentId,
        array $releaseConfig
    ): string;

    public function getDeploymentStatus(
        string $tenantId,
        string $deploymentId
    ): ?array;

    public function createPilotGate(
        string $tenantId,
        string $deploymentId,
        array $gateConfig
    ): string;

    public function validatePilotGate(
        string $tenantId,
        string $gateId
    ): array;

    public function approvePilotGate(
        string $tenantId,
        string $gateId,
        string $approver
    ): bool;

    public function rolloutRelease(
        string $tenantId,
        string $deploymentId
    ): bool;

    public function rollbackDeployment(
        string $tenantId,
        string $deploymentId
    ): bool;
}
