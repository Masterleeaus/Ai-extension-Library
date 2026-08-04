<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WorkCoreOperationsContract
{
    public function scheduleJob(
        string $tenantId,
        array $jobData
    ): string;

    public function getJobStatus(
        string $tenantId,
        string $jobId
    ): ?array;

    public function createDispatchRoute(
        string $tenantId,
        array $routeData
    ): string;

    public function optimizeRoute(
        string $tenantId,
        string $routeId
    ): array;

    public function trackFleetVehicle(
        string $tenantId,
        string $vehicleId
    ): ?array;

    public function updateFleetStatus(
        string $tenantId,
        string $vehicleId,
        array $statusData
    ): bool;

    public function assignDriver(
        string $tenantId,
        string $vehicleId,
        string $driverId
    ): bool;

    public function getOperationalMetrics(
        string $tenantId,
        array $filters = []
    ): array;
}
