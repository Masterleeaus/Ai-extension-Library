<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\TenantContextContract;
use RuntimeException;

class TenantContext implements TenantContextContract
{
    private ?string $tenantId = null;
    private ?string $userId = null;
    private ?string $actorId = null;
    private array $permissions = [];
    private int $policyVersion = 1;

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPolicyVersion(): int
    {
        return $this->policyVersion;
    }

    public function set(string $tenantId, ?string $userId = null, ?string $actorId = null): void
    {
        if (empty($tenantId)) {
            throw new RuntimeException('Tenant ID cannot be empty');
        }

        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->actorId = $actorId;
        $this->permissions = [];
    }

    public function restore(array $snapshot): void
    {
        $tenantId = $snapshot['tenant_id'] ?? null;
        if ($tenantId !== null && empty($tenantId)) {
            throw new RuntimeException('Tenant ID cannot be empty when restoring');
        }

        $this->tenantId = $tenantId;
        $this->userId = $snapshot['user_id'] ?? null;
        $this->actorId = $snapshot['actor_id'] ?? null;
        $this->permissions = is_array($snapshot['permissions'] ?? null) ? $snapshot['permissions'] : [];
        $this->policyVersion = is_int($snapshot['policy_version'] ?? null) ? $snapshot['policy_version'] : 1;
    }

    public function snapshot(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'actor_id' => $this->actorId,
            'permissions' => $this->permissions,
            'policy_version' => $this->policyVersion,
        ];
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->userId = null;
        $this->actorId = null;
        $this->permissions = [];
    }

    public function hasPermission(string $action, string $resource): bool
    {
        $key = "{$action}:{$resource}";
        return isset($this->permissions[$key]);
    }

    public function grantPermission(string $action, string $resource): void
    {
        $key = "{$action}:{$resource}";
        $this->permissions[$key] = true;
    }

    public function revokePermission(string $action, string $resource): void
    {
        $key = "{$action}:{$resource}";
        unset($this->permissions[$key]);
    }
}
