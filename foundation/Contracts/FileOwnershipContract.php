<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface FileOwnershipContract
{
    public function assertOwnership(
        string $tenantId,
        string $filePath,
        string $userId
    ): bool;

    public function transferOwnership(
        string $tenantId,
        string $filePath,
        string $fromUserId,
        string $toUserId
    ): bool;

    public function getOwner(
        string $tenantId,
        string $filePath
    ): ?string;

    public function listOwned(
        string $tenantId,
        string $userId
    ): array;

    public function isAccessible(
        string $tenantId,
        string $filePath,
        string $userId,
        string $permission
    ): bool;

    public function grantAccess(
        string $tenantId,
        string $filePath,
        string $userId,
        string $permission
    ): bool;

    public function revokeAccess(
        string $tenantId,
        string $filePath,
        string $userId,
        string $permission
    ): bool;

    public function auditAccess(
        string $tenantId,
        string $filePath
    ): array;
}
