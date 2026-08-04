<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WorkCoreFoundationContract
{
    public function registerTenant(
        string $tenantId,
        array $tenantConfig
    ): bool;

    public function getTenantConfig(
        string $tenantId
    ): ?array;

    public function updateTenantConfig(
        string $tenantId,
        array $updates
    ): bool;

    public function grantPermission(
        string $tenantId,
        string $userId,
        string $resource,
        string $action
    ): bool;

    public function checkPermission(
        string $tenantId,
        string $userId,
        string $resource,
        string $action
    ): bool;

    public function listUserRoles(
        string $tenantId,
        string $userId
    ): array;

    public function syncWithFoundation(
        string $tenantId,
        array $data
    ): bool;

    public function getFoundationStatus(
        string $tenantId
    ): ?array;
}
