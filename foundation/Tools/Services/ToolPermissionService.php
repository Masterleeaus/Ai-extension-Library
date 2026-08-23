<?php

declare(strict_types=1);

namespace Foundation\Tools\Services;

/**
 * Service for checking tool permissions.
 *
 * Validates whether a user has required permissions to execute a tool.
 */
interface ToolPermissionService
{
    /**
     * Check if user has required permissions.
     *
     * @param string $tenantId Tenant ID
     * @param string $userId User ID
     * @param array $requiredPermissions List of required permission names
     * @return bool True if all required permissions are granted
     */
    public function checkPermissions(
        string $tenantId,
        string $userId,
        array $requiredPermissions
    ): bool;
}
