<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\DataEncryptionContract;
use PDO;

class DataEncryption implements DataEncryptionContract
{
    private PDO $db;
    private string $tablePrefix = 'data_encryption_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function encrypt(
        string $tenantId,
        string $plaintext,
        string $keyId = 'default'
    ): string {
        $key = $this->getKeyForEncryption($tenantId, $keyId);

        if (!$key) {
            throw new \RuntimeException("Encryption key not found: {$keyId}");
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key['material']);

        return base64_encode($nonce . $ciphertext);
    }

    public function decrypt(
        string $tenantId,
        string $ciphertext,
        string $keyId = 'default'
    ): ?string {
        $key = $this->getKeyForDecryption($tenantId, $keyId);

        if (!$key) {
            return null;
        }

        try {
            $decoded = base64_decode($ciphertext, true);
            $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $encryptedData = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

            return sodium_crypto_secretbox_open($encryptedData, $nonce, $key['material']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function createKey(
        string $tenantId,
        string $algorithm,
        int $keySize
    ): string {
        $keyId = bin2hex(random_bytes(16));
        $keyMaterial = random_bytes($keySize);

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}keys (id, tenant_id, algorithm, key_size, material, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $keyId,
            $tenantId,
            $algorithm,
            $keySize,
            base64_encode($keyMaterial),
            'active',
            gmdate('c'),
        ]);

        return $keyId;
    }

    public function rotateKey(
        string $tenantId,
        string $keyId
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT algorithm, key_size FROM {$this->tablePrefix}keys WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$keyId, $tenantId]);
        $oldKey = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldKey) {
            return false;
        }

        $newKeyId = $this->createKey($tenantId, $oldKey['algorithm'], $oldKey['key_size']);

        $updateOldStmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}keys SET status = 'rotated', rotated_at = ? WHERE id = ?"
        );

        $updateOldStmt->execute([gmdate('c'), $keyId]);

        return true;
    }

    public function getKeyMetadata(
        string $tenantId,
        string $keyId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, tenant_id, algorithm, key_size, status, created_at, rotated_at
             FROM {$this->tablePrefix}keys WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$keyId, $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listKeys(string $tenantId): array {
        $stmt = $this->db->prepare(
            "SELECT id, tenant_id, algorithm, key_size, status, created_at, rotated_at
             FROM {$this->tablePrefix}keys WHERE tenant_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteKey(
        string $tenantId,
        string $keyId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}keys WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$keyId, $tenantId]);
    }

    public function encryptField(
        string $tenantId,
        array $data,
        array $fieldNames
    ): array {
        $encrypted = $data;

        foreach ($fieldNames as $fieldName) {
            if (isset($encrypted[$fieldName])) {
                $encrypted[$fieldName] = $this->encrypt($tenantId, (string)$encrypted[$fieldName]);
            }
        }

        return $encrypted;
    }

    public function decryptField(
        string $tenantId,
        array $data,
        array $fieldNames
    ): array {
        $decrypted = $data;

        foreach ($fieldNames as $fieldName) {
            if (isset($decrypted[$fieldName])) {
                $decrypted[$fieldName] = $this->decrypt($tenantId, $decrypted[$fieldName]);
            }
        }

        return $decrypted;
    }

    private function getKeyForEncryption(string $tenantId, string $keyId): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}keys WHERE id = ? AND tenant_id = ? AND status = 'active'"
        );

        $stmt->execute([$keyId, $tenantId]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($key) {
            $key['material'] = base64_decode($key['material']);
        }

        return $key ?: null;
    }

    private function getKeyForDecryption(string $tenantId, string $keyId): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}keys WHERE id = ? AND tenant_id = ? AND (status = 'active' OR status = 'rotated')"
        );

        $stmt->execute([$keyId, $tenantId]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($key) {
            $key['material'] = base64_decode($key['material']);
        }

        return $key ?: null;
    }
}
