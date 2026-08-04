<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Vault;

use App\Domains\WorkCore\System\Tenancy\TenantContext;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Issue #145: Credential Vault References & Envelopes
 * Secure storage and retrieval of sensitive credentials with encryption.
 */
final class CredentialVaultService
{
    private const PREFIX = 'vault:';

    public function __construct(
        private TenantContext $tenantContext,
        private Encrypter $encrypter,
    ) {}

    /**
     * Store a credential securely in the vault.
     * Returns a reference ID that can be used to retrieve the credential later.
     */
    public function storeCredential(string $name, string $value, ?array $metadata = null): string
    {
        $tenantId = $this->tenantContext->companyId();
        $credentialId = $this->generateCredentialId($tenantId, $name);

        // Encrypt the credential value
        $encrypted = $this->encrypter->encrypt($value);

        // Store with metadata (without storing the actual credential)
        $this->persistCredential(
            credentialId: $credentialId,
            tenantId: $tenantId,
            name: $name,
            encryptedValue: $encrypted,
            metadata: $metadata,
        );

        return self::PREFIX . $credentialId;
    }

    /**
     * Retrieve a credential by its vault reference.
     * Only accessible to the tenant that created it.
     */
    public function retrieveCredential(string $vaultReference): ?string
    {
        if (!str_starts_with($vaultReference, self::PREFIX)) {
            throw new RuntimeException('Invalid vault reference format');
        }

        $credentialId = substr($vaultReference, strlen(self::PREFIX));
        $tenantId = $this->tenantContext->companyId();

        $credential = $this->getCredential($credentialId, $tenantId);
        if (!$credential) {
            return null;
        }

        // Decrypt and return the value
        try {
            return $this->encrypter->decrypt($credential['encrypted_value']);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to decrypt credential: {$e->getMessage()}");
        }
    }

    /**
     * Rotate a credential (generate new value, keep reference).
     */
    public function rotateCredential(string $vaultReference, string $newValue): bool
    {
        if (!str_starts_with($vaultReference, self::PREFIX)) {
            return false;
        }

        $credentialId = substr($vaultReference, strlen(self::PREFIX));
        $tenantId = $this->tenantContext->companyId();

        $credential = $this->getCredential($credentialId, $tenantId);
        if (!$credential) {
            return false;
        }

        $encrypted = $this->encrypter->encrypt($newValue);
        return $this->updateCredential($credentialId, $tenantId, $encrypted);
    }

    /**
     * Delete a credential reference.
     */
    public function deleteCredential(string $vaultReference): bool
    {
        if (!str_starts_with($vaultReference, self::PREFIX)) {
            return false;
        }

        $credentialId = substr($vaultReference, strlen(self::PREFIX));
        $tenantId = $this->tenantContext->companyId();

        return $this->removeCredential($credentialId, $tenantId);
    }

    /**
     * Get credential metadata without decrypting the value.
     */
    public function getCredentialMetadata(string $vaultReference): ?array
    {
        if (!str_starts_with($vaultReference, self::PREFIX)) {
            return null;
        }

        $credentialId = substr($vaultReference, strlen(self::PREFIX));
        $tenantId = $this->tenantContext->companyId();

        $credential = $this->getCredential($credentialId, $tenantId);
        if (!$credential) {
            return null;
        }

        return [
            'id' => $credentialId,
            'name' => $credential['name'] ?? null,
            'created_at' => $credential['created_at'] ?? null,
            'last_accessed' => $credential['last_accessed'] ?? null,
            'metadata' => $credential['metadata'] ?? null,
        ];
    }

    /**
     * Generate a secure credential ID.
     */
    private function generateCredentialId(int $tenantId, string $name): string
    {
        return "{$tenantId}-" . Str::slug($name) . '-' . Str::random(16);
    }

    /**
     * Store credential in persistent storage (database, vault, etc.).
     * Implementation depends on storage backend.
     */
    private function persistCredential(
        string $credentialId,
        int $tenantId,
        string $name,
        string $encryptedValue,
        ?array $metadata = null,
    ): void {
        // Placeholder: implement storage
        // In production: store in database or external vault service
    }

    /**
     * Retrieve credential from storage.
     */
    private function getCredential(string $credentialId, int $tenantId): ?array
    {
        // Placeholder: implement retrieval
        return null;
    }

    /**
     * Update credential value.
     */
    private function updateCredential(string $credentialId, int $tenantId, string $encryptedValue): bool
    {
        // Placeholder: implement update
        return false;
    }

    /**
     * Remove credential from storage.
     */
    private function removeCredential(string $credentialId, int $tenantId): bool
    {
        // Placeholder: implement deletion
        return false;
    }
}
