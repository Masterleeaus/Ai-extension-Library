<?php

namespace App\Domains\Shared\Context;

use App\Domains\Shared\Identity\UserIdentity;
use App\Domains\Shared\Identity\ActorIdentity;
use RuntimeException;

class TenantContextImpl implements TenantContext
{
    private string $tenantId;
    private ?UserIdentity $userIdentity;
    private ActorIdentity $actor;
    private array $permissions = [];
    private string $policyVersion;
    private \DateTimeImmutable $timestamp;

    public function __construct(
        string $tenantId,
        ActorIdentity $actor,
        ?UserIdentity $userIdentity = null,
        array $permissions = [],
        string $policyVersion = '1.0'
    ) {
        if (empty($tenantId)) {
            throw new RuntimeException('Tenant ID cannot be empty');
        }

        $this->tenantId = $tenantId;
        $this->actor = $actor;
        $this->userIdentity = $userIdentity;
        $this->permissions = $permissions;
        $this->policyVersion = $policyVersion;
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getUserIdentity(): ?UserIdentity
    {
        return $this->userIdentity;
    }

    public function getActor(): ActorIdentity
    {
        return $this->actor;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPolicyVersion(): string
    {
        return $this->policyVersion;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * Create from HTTP request context
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        $tenantId = $request->header('X-Tenant-ID')
            ?? auth()->user()?->tenant_id
            ?? throw new RuntimeException('No tenant context found');

        $user = auth()->user();
        $userIdentity = $user ? UserIdentity::fromUser($user) : null;

        $permissions = $user?->getPermissions() ?? [];

        $actor = ActorIdentity::forUser($user);

        return new self($tenantId, $actor, $userIdentity, $permissions);
    }

    /**
     * Create for service or webhook execution
     */
    public static function forService(
        string $tenantId,
        string $serviceName,
        array $permissions = []
    ): self {
        $actor = ActorIdentity::forService($serviceName);
        return new self($tenantId, $actor, null, $permissions);
    }

    /**
     * Create for webhook execution
     */
    public static function forWebhook(
        string $tenantId,
        string $webhookName,
        array $permissions = []
    ): self {
        $actor = ActorIdentity::forWebhook($webhookName);
        return new self($tenantId, $actor, null, $permissions);
    }
}
