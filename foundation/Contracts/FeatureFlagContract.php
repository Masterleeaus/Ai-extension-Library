<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface FeatureFlagContract
{
    public function createFlag(
        string $tenantId,
        string $flagName,
        bool $enabled,
        array $metadata = []
    ): string;

    public function getFlag(
        string $tenantId,
        string $flagName
    ): ?array;

    public function isEnabled(
        string $tenantId,
        string $flagName,
        ?string $userId = null
    ): bool;

    public function enableFlag(
        string $tenantId,
        string $flagName,
        ?int $rolloutPercentage = null
    ): bool;

    public function disableFlag(
        string $tenantId,
        string $flagName
    ): bool;

    public function setRollout(
        string $tenantId,
        string $flagName,
        int $percentage
    ): bool;

    public function addUserVariant(
        string $tenantId,
        string $flagName,
        string $userId,
        bool $enabled
    ): bool;

    public function listFlags(string $tenantId): array;
}
