<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface AccessControlContract
{
    public function grantPermission(
        string $tenantId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $permission
    ): bool;

    public function revokePermission(
        string $tenantId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $permission
    ): bool;

    public function hasPermission(
        string $tenantId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $permission
    ): bool;

    public function getUserPermissions(
        string $tenantId,
        string $userId,
        ?string $resourceType = null
    ): array;

    public function getResourceAccess(
        string $tenantId,
        string $resourceType,
        string $resourceId
    ): array;

    public function assignRole(
        string $tenantId,
        string $userId,
        string $role
    ): bool;

    public function revokeRole(
        string $tenantId,
        string $userId,
        string $role
    ): bool;

    public function getRolePermissions(
        string $tenantId,
        string $role
    ): array;
}
