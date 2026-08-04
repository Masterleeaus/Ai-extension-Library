<?php

namespace App\Domains\Shared\Identity;

class UserIdentity
{
    public function __construct(
        private string $userId,
        private string $email,
        private ?string $name = null,
        private array $roles = [],
        private ?string $organizationId = null
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public static function fromUser($user): self
    {
        return new self(
            (string) $user->id,
            $user->email,
            $user->name ?? null,
            $user->roles()->pluck('name')->toArray(),
            $user->organization_id ?? null
        );
    }
}
