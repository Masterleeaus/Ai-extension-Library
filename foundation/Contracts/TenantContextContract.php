<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface TenantContextContract
{
    public function hasTenant(): bool;

    public function getTenantId(): ?string;

    public function getUserId(): ?string;

    public function getActorId(): ?string;

    public function getPermissions(): array;

    public function getPolicyVersion(): int;

    public function set(string $tenantId, ?string $userId = null, ?string $actorId = null): void;

    public function restore(array $snapshot): void;

    public function snapshot(): array;

    public function clear(): void;

    public function hasPermission(string $action, string $resource): bool;

    public function grantPermission(string $action, string $resource): void;

    public function revokePermission(string $action, string $resource): void;
}
