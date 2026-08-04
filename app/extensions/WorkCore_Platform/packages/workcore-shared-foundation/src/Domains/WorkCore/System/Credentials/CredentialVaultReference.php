<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Credentials;

use DateTimeImmutable;
use InvalidArgumentException;

final class CredentialVaultReference implements CredentialVaultReferenceContract
{
    public function __construct(
        private string $vaultName,
        private string $referencePath,
        private int $tenantId,
        private string $serviceIdentifier,
        private string $permissionLevel = 'read',
        private ?DateTimeImmutable $rotatedAt = null,
        private ?DateTimeImmutable $nextRotationDue = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->vaultName)) {
            throw new InvalidArgumentException('Vault name cannot be empty');
        }
        if (empty($this->referencePath)) {
            throw new InvalidArgumentException('Reference path cannot be empty');
        }
        if ($this->tenantId <= 0) {
            throw new InvalidArgumentException('Tenant ID must be positive');
        }
        if (!in_array($this->permissionLevel, ['read', 'read-write', 'rotate'])) {
            throw new InvalidArgumentException('Invalid permission level');
        }
    }

    public function vaultName(): string
    {
        return $this->vaultName;
    }

    public function referencePath(): string
    {
        return $this->referencePath;
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function serviceIdentifier(): string
    {
        return $this->serviceIdentifier;
    }

    public function permissionLevel(): string
    {
        return $this->permissionLevel;
    }

    public function rotatedAt(): ?DateTimeImmutable
    {
        return $this->rotatedAt;
    }

    public function nextRotationDue(): ?DateTimeImmutable
    {
        return $this->nextRotationDue;
    }

    public function toArray(): array
    {
        return [
            'vaultName' => $this->vaultName,
            'referencePath' => $this->referencePath,
            'tenantId' => $this->tenantId,
            'serviceIdentifier' => $this->serviceIdentifier,
            'permissionLevel' => $this->permissionLevel,
            'rotatedAt' => $this->rotatedAt?->format('c'),
            'nextRotationDue' => $this->nextRotationDue?->format('c'),
        ];
    }

    public static function from(array $data): self
    {
        return new self(
            vaultName: $data['vaultName'] ?? $data['vault_name'] ?? throw new InvalidArgumentException('vaultName is required'),
            referencePath: $data['referencePath'] ?? $data['reference_path'] ?? throw new InvalidArgumentException('referencePath is required'),
            tenantId: $data['tenantId'] ?? $data['tenant_id'] ?? throw new InvalidArgumentException('tenantId is required'),
            serviceIdentifier: $data['serviceIdentifier'] ?? $data['service_identifier'] ?? throw new InvalidArgumentException('serviceIdentifier is required'),
            permissionLevel: $data['permissionLevel'] ?? $data['permission_level'] ?? 'read',
            rotatedAt: isset($data['rotatedAt']) ? new DateTimeImmutable($data['rotatedAt']) : (isset($data['rotated_at']) ? new DateTimeImmutable($data['rotated_at']) : null),
            nextRotationDue: isset($data['nextRotationDue']) ? new DateTimeImmutable($data['nextRotationDue']) : (isset($data['next_rotation_due']) ? new DateTimeImmutable($data['next_rotation_due']) : null),
        );
    }

    public function withNextRotationDue(DateTimeImmutable $nextRotationDue): self
    {
        return new self(
            $this->vaultName,
            $this->referencePath,
            $this->tenantId,
            $this->serviceIdentifier,
            $this->permissionLevel,
            $this->rotatedAt,
            $nextRotationDue,
        );
    }

    public function markAsRotated(): self
    {
        return new self(
            $this->vaultName,
            $this->referencePath,
            $this->tenantId,
            $this->serviceIdentifier,
            $this->permissionLevel,
            new DateTimeImmutable(),
            $this->nextRotationDue,
        );
    }
}
