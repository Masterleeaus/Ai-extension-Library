<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ComplianceContract
{
    public function createCompliancePolicy(
        string $tenantId,
        string $policyName,
        array $requirements
    ): string;

    public function verifyCompliance(
        string $tenantId,
        string $policyId
    ): array;

    public function getComplianceStatus(
        string $tenantId,
        string $policyId
    ): ?array;

    public function testDataResidency(
        string $tenantId
    ): bool;

    public function testDataRetention(
        string $tenantId,
        string $policyId
    ): array;

    public function auditCompliance(
        string $tenantId,
        string $policyId
    ): array;

    public function generateComplianceReport(
        string $tenantId,
        string $policyId
    ): string;

    public function listCompliancePolicies(
        string $tenantId
    ): array;
}
