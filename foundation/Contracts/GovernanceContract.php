<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface GovernanceContract
{
    public function createPolicy(
        string $tenantId,
        string $policyName,
        array $rules
    ): string;

    public function getPolicy(
        string $tenantId,
        string $policyId
    ): ?array;

    public function updatePolicy(
        string $tenantId,
        string $policyId,
        array $rules
    ): bool;

    public function deletePolicy(
        string $tenantId,
        string $policyId
    ): bool;

    public function enforcePolicy(
        string $tenantId,
        string $policyId,
        string $resourceType,
        array $resourceData
    ): array;

    public function auditPolicyEnforcement(
        string $tenantId,
        string $policyId
    ): array;

    public function listPolicies(
        string $tenantId,
        ?string $category = null
    ): array;

    public function getPolicyVersion(
        string $tenantId,
        string $policyId,
        int $version
    ): ?array;
}
