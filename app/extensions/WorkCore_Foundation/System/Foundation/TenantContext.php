<?php

namespace Extensions\WorkCore_Foundation\System\Foundation;

use Illuminate\Support\Facades\Auth;

/**
 * Issue #181: TenantContext & Authorization Policies
 * Provides enterprise tenancy isolation and permission boundaries
 */
class TenantContext
{
    protected $tenantId;
    protected $userId;
    protected $permissions = [];
    protected $scopes = [];
    protected $roleId;

    public function __construct(string $tenantId, string $userId, array $permissions = [], array $scopes = [])
    {
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->permissions = $permissions;
        $this->scopes = $scopes;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasPermission(string $permission): bool
    {
        if (in_array('*', $this->permissions)) {
            return true;
        }

        if (in_array($permission, $this->permissions)) {
            return true;
        }

        // Check wildcard permissions (e.g., "read:*" matches "read:users")
        foreach ($this->permissions as $perm) {
            if ($this->matchesWildcard($perm, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes) || in_array('*', $this->scopes);
    }

    public function setRoleId(string $roleId): void
    {
        $this->roleId = $roleId;
    }

    public function getRoleId(): ?string
    {
        return $this->roleId ?? null;
    }

    public function enforcePermission(string $permission): bool
    {
        if (!$this->hasPermission($permission)) {
            throw new \Exception("Permission denied: {$permission}");
        }
        return true;
    }

    public function enforceScope(string $scope): bool
    {
        if (!$this->hasScope($scope)) {
            throw new \Exception("Scope violation: {$scope}");
        }
        return true;
    }

    public static function current(): ?self
    {
        return request()->get('tenant_context');
    }

    public static function for(string $tenantId, string $userId): self
    {
        return new self($tenantId, $userId);
    }

    protected function matchesWildcard(string $pattern, string $permission): bool
    {
        $pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
        return preg_match("/^{$pattern}$/", $permission) === 1;
    }
}
