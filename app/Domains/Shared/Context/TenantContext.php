<?php

namespace App\Domains\Shared\Context;

use App\Domains\Shared\Identity\UserIdentity;
use App\Domains\Shared\Identity\ActorIdentity;

interface TenantContext
{
    /**
     * Get the immutable tenant ID for all operations
     */
    public function getTenantId(): string;

    /**
     * Get the authenticated user information
     */
    public function getUserIdentity(): ?UserIdentity;

    /**
     * Get the actor performing the action (user, service, webhook)
     */
    public function getActor(): ActorIdentity;

    /**
     * Get permissions array for authorization checks
     */
    public function getPermissions(): array;

    /**
     * Get current policy version for audit trails
     */
    public function getPolicyVersion(): string;

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool;

    /**
     * Get the current timestamp for this context
     */
    public function getTimestamp(): \DateTimeImmutable;
}
