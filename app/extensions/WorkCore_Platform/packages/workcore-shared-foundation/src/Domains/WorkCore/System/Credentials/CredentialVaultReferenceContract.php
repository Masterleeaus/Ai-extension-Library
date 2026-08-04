<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Credentials;

interface CredentialVaultReferenceContract
{
    public function vaultName(): string;

    public function referencePath(): string;

    public function tenantId(): int;

    public function serviceIdentifier(): string;

    public function permissionLevel(): string;

    public function rotatedAt(): ?\DateTimeImmutable;

    public function nextRotationDue(): ?\DateTimeImmutable;

    public function toArray(): array;

    public static function from(array $data): self;
}
