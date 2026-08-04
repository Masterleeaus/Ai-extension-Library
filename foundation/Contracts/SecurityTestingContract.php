<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface SecurityTestingContract
{
    public function runSecurityScan(
        string $tenantId,
        string $targetSystem
    ): string;

    public function getScanResults(
        string $tenantId,
        string $scanId
    ): ?array;

    public function testAuthenticationFlow(
        string $tenantId,
        array $credentials
    ): array;

    public function testAuthorizationBoundaries(
        string $tenantId,
        array $testCases
    ): array;

    public function runVulnerabilityScan(
        string $tenantId,
        string $targetComponent
    ): array;

    public function testTenantIsolation(
        string $tenantId,
        string $targetTenantId
    ): array;

    public function generateSecurityReport(
        string $tenantId,
        string $scanId
    ): string;

    public function recordSecurityFinding(
        string $tenantId,
        array $finding
    ): string;
}
