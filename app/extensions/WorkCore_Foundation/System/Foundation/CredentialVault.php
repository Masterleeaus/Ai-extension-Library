<?php

namespace Extensions\WorkCore_Foundation\System\Foundation;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

/**
 * Issue #145: Credential Vault References & Envelope Encryption
 * Secure credential storage with encryption and reference-based access
 */
class CredentialVault
{
    protected $tenantId;
    protected const CACHE_TTL = 3600; // 1 hour

    public function __construct(string $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Store a credential securely
     */
    public function store(string $key, string $secret, array $metadata = []): string
    {
        $reference = "vault://{$this->tenantId}/{$key}/" . bin2hex(random_bytes(8));

        $encrypted = Crypt::encrypt(json_encode([
            'secret' => $secret,
            'metadata' => $metadata,
            'created_at' => now()->toIso8601String(),
            'tenant_id' => $this->tenantId,
        ]));

        Cache::put($reference, $encrypted, self::CACHE_TTL);

        // Also store in database for persistence
        $this->persistCredential($reference, $encrypted);

        return $reference;
    }

    /**
     * Retrieve credential by reference
     */
    public function retrieve(string $reference): string
    {
        // Check cache first
        $encrypted = Cache::get($reference);

        if (!$encrypted) {
            // Fall back to database
            $encrypted = $this->fetchFromDatabase($reference);
        }

        if (!$encrypted) {
            throw new \Exception("Credential not found: {$reference}");
        }

        $data = json_decode(Crypt::decrypt($encrypted), true);

        // Validate tenant isolation
        if ($data['tenant_id'] !== $this->tenantId) {
            throw new \Exception("Credential access denied (tenant mismatch)");
        }

        return $data['secret'];
    }

    /**
     * Get credential metadata without exposing secret
     */
    public function getMetadata(string $reference): array
    {
        $encrypted = Cache::get($reference) ?: $this->fetchFromDatabase($reference);

        if (!$encrypted) {
            throw new \Exception("Credential not found: {$reference}");
        }

        $data = json_decode(Crypt::decrypt($encrypted), true);

        if ($data['tenant_id'] !== $this->tenantId) {
            throw new \Exception("Credential access denied (tenant mismatch)");
        }

        return [
            'key' => $this->extractKeyFromReference($reference),
            'metadata' => $data['metadata'] ?? [],
            'created_at' => $data['created_at'],
        ];
    }

    /**
     * Revoke credential access
     */
    public function revoke(string $reference): bool
    {
        Cache::forget($reference);
        return $this->deleteFromDatabase($reference);
    }

    /**
     * Create envelope with reference instead of secret
     */
    public function createEnvelope(array $data, array $credentialReferences = []): array
    {
        $envelope = [
            'id' => bin2hex(random_bytes(16)),
            'tenant_id' => $this->tenantId,
            'data' => $data,
            'credentials' => [],
        ];

        foreach ($credentialReferences as $field => $reference) {
            $envelope['credentials'][$field] = $reference;
        }

        return $envelope;
    }

    /**
     * Decrypt envelope with credentials
     */
    public function decryptEnvelope(array $envelope): array
    {
        if ($envelope['tenant_id'] !== $this->tenantId) {
            throw new \Exception("Envelope tenant mismatch");
        }

        $decrypted = $envelope;

        foreach ($envelope['credentials'] as $field => $reference) {
            $decrypted[$field] = $this->retrieve($reference);
        }

        unset($decrypted['credentials']);
        return $decrypted;
    }

    protected function persistCredential(string $reference, string $encrypted): void
    {
        // Implementation would store in database
        // For now, cache is primary storage
    }

    protected function fetchFromDatabase(string $reference): ?string
    {
        // Implementation would retrieve from database
        return null;
    }

    protected function deleteFromDatabase(string $reference): bool
    {
        // Implementation would delete from database
        return true;
    }

    protected function extractKeyFromReference(string $reference): string
    {
        // Parse vault://tenant/key/id format
        preg_match('/vault:\/\/[^\/]+\/([^\/]+)/', $reference, $matches);
        return $matches[1] ?? 'unknown';
    }
}
