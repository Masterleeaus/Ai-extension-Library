<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface CustomerIdentityContract
{
    public function resolve(
        string $tenantId,
        string $providerId,
        array $identifiers
    ): ?string;

    public function link(
        string $tenantId,
        string $customerId,
        string $providerId,
        array $providerIdentity
    ): bool;

    public function getIdentities(string $tenantId, string $customerId): array;

    public function unlink(
        string $tenantId,
        string $customerId,
        string $providerId
    ): bool;

    public function getProfile(string $tenantId, string $customerId): ?array;

    public function updateProfile(
        string $tenantId,
        string $customerId,
        array $profile
    ): bool;
}
