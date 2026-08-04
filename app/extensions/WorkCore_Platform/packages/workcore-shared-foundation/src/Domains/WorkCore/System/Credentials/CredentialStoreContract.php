<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Credentials;

interface CredentialStoreContract
{
    public function store(
        int $tenantId,
        string $key,
        mixed $credential,
        ?CredentialVaultReferenceContract $reference = null,
    ): void;

    public function retrieve(int $tenantId, string $key): mixed;

    public function has(int $tenantId, string $key): bool;

    public function delete(int $tenantId, string $key): bool;

    public function rotateCredential(
        int $tenantId,
        string $key,
        mixed $newCredential,
    ): void;

    public function listCredentials(int $tenantId): array;
}
