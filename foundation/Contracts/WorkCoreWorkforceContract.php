<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WorkCoreWorkforceContract
{
    public function registerEmployee(
        string $tenantId,
        array $employeeData
    ): string;

    public function getEmployee(
        string $tenantId,
        string $employeeId
    ): ?array;

    public function updateEmployee(
        string $tenantId,
        string $employeeId,
        array $updates
    ): bool;

    public function trackCompliance(
        string $tenantId,
        string $employeeId,
        array $complianceData
    ): bool;

    public function verifyNDISEligibility(
        string $tenantId,
        string $employeeId
    ): bool;

    public function recordTraining(
        string $tenantId,
        string $employeeId,
        array $trainingData
    ): string;

    public function generateComplianceReport(
        string $tenantId,
        array $filters
    ): string;

    public function manageRoles(
        string $tenantId,
        string $employeeId,
        array $roles
    ): bool;
}
