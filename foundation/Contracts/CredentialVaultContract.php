<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface CredentialVaultContract
{
    public function store(
        string $tenantId,
        string $key,
        string $secret,
        ?string $type = null,
        array $metadata = []
    ): string;

    public function retrieve(string $tenantId, string $key): ?string;

    public function delete(string $tenantId, string $key): bool;

    public function exists(string $tenantId, string $key): bool;

    public function rotate(string $tenantId, string $key, string $newSecret): bool;

    public function list(string $tenantId, ?string $type = null): array;

    public function redact(string $value): string;

    public function isRedacted(string $value): bool;
}
