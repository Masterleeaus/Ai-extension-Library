<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface DataEncryptionContract
{
    public function encrypt(
        string $tenantId,
        string $plaintext,
        string $keyId = 'default'
    ): string;

    public function decrypt(
        string $tenantId,
        string $ciphertext,
        string $keyId = 'default'
    ): ?string;

    public function createKey(
        string $tenantId,
        string $algorithm,
        int $keySize
    ): string;

    public function rotateKey(
        string $tenantId,
        string $keyId
    ): bool;

    public function getKeyMetadata(
        string $tenantId,
        string $keyId
    ): ?array;

    public function listKeys(string $tenantId): array;

    public function deleteKey(
        string $tenantId,
        string $keyId
    ): bool;

    public function encryptField(
        string $tenantId,
        array $data,
        array $fieldNames
    ): array;

    public function decryptField(
        string $tenantId,
        array $data,
        array $fieldNames
    ): array;
}
