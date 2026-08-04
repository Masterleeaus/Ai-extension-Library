# Issue #145: Implement Credential Vault References & Envelope Encryption

**Status:** URGENT | Phase 0 | Foundation  
**Priority:** Critical  
**Effort:** 1 week  
**Depends on:** #143  
**Blocks:** #20, #31, #61, #67, #68  

## Problem Statement

- Credentials copied into extension settings, logs, queue payloads, events
- Secrets exposed in tool results and database snapshots
- No credential rotation metadata
- API keys, OAuth tokens, webhook secrets stored unencrypted in multiple locations

## Solution Requirements

Replace secrets in configuration and events with vault references and envelope encryption for all sensitive data.

## Deliverables

### 1. Define CredentialVaultReference Contract

```php
// app/Domains/Shared/Credentials/CredentialVaultReference.php

class CredentialVaultReference {
    public string $vaultName;           // "aws-secrets-manager", "hashicorp-vault", "azure-keyvault"
    public string $referencePath;       // Path to secret in vault
    public string $referenceId;         // Vault-specific ID
    
    // Rotation metadata
    public \DateTimeImmutable $createdAt;
    public ?\DateTimeImmutable $rotatedAt;
    public ?\DateTimeImmutable $nextRotation;
    
    // Access control
    public string $tenantId;            // Who owns this credential
    public array $allowedServices;      // Services that can use this
    public array $permissions;          // What operations are allowed
    
    // Usage tracking
    public int $usageCount;
    public ?\DateTimeImmutable $lastUsedAt;
    
    public function getSecret(): string {
        // Retrieve actual secret from vault
        // DON'T expose until needed
    }
    
    public function isExpired(): bool {
        return $this->nextRotation && now()->isAfter($this->nextRotation);
    }
}
```

### 2. Implement Vault Interface

```php
// app/Domains/Shared/Credentials/Vault.php

interface Vault {
    /**
     * Store a secret and return reference
     */
    public function store(
        string $tenantId,
        string $name,
        string $secret,
        array $metadata = []
    ): CredentialVaultReference;
    
    /**
     * Retrieve secret by reference
     */
    public function retrieve(CredentialVaultReference $reference): string;
    
    /**
     * Update secret (rotate credentials)
     */
    public function update(
        CredentialVaultReference $reference,
        string $newSecret
    ): CredentialVaultReference;
    
    /**
     * Revoke access to credential
     */
    public function revoke(CredentialVaultReference $reference): void;
    
    /**
     * List all secrets for tenant
     */
    public function listForTenant(string $tenantId): Collection;
    
    /**
     * Check if reference is still valid
     */
    public function isValid(CredentialVaultReference $reference): bool;
}

// Implementations:
// - AWSSecretsManagerVault
// - HashiCorpVault
// - AzureKeyVault
// - LocalEncryptedVault (for dev)
```

### 3. Envelope Encryption

```php
// app/Domains/Shared/Encryption/EnvelopeEncryption.php

class EnvelopeEncryption {
    /**
     * Encrypt data and return envelope with DEK (Data Encryption Key)
     */
    public function encryptData(
        string $plaintext,
        string $tenantId
    ): EncryptedEnvelope {
        // 1. Generate DEK (Data Encryption Key) for this data
        $dek = random_bytes(32);
        
        // 2. Encrypt plaintext with DEK using AES-256-GCM
        $iv = random_bytes(12);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $iv, $tag);
        
        // 3. Encrypt DEK with KEK (Key Encryption Key) from vault
        $kek = $this->vault->retrieveKEK($tenantId);
        $encryptedDek = openssl_encrypt($dek, 'aes-256-gcm', $kek, OPENSSL_RAW_DATA, $iv, $kekTag);
        
        // 4. Return envelope: {encryptedDek, ciphertext, iv, tag, kekTag}
        return new EncryptedEnvelope(
            encryptedDek: $encryptedDek,
            ciphertext: $ciphertext,
            iv: $iv,
            tag: $tag,
            kekTag: $kekTag,
            algorithm: 'aes-256-gcm'
        );
    }
    
    /**
     * Decrypt envelope
     */
    public function decryptData(EncryptedEnvelope $envelope, string $tenantId): string {
        // 1. Retrieve KEK from vault
        $kek = $this->vault->retrieveKEK($tenantId);
        
        // 2. Decrypt DEK
        $dek = openssl_decrypt(
            $envelope->encryptedDek,
            'aes-256-gcm',
            $kek,
            OPENSSL_RAW_DATA,
            $envelope->iv,
            $envelope->kekTag
        );
        
        // 3. Decrypt plaintext
        $plaintext = openssl_decrypt(
            $envelope->ciphertext,
            'aes-256-gcm',
            $dek,
            OPENSSL_RAW_DATA,
            $envelope->iv,
            $envelope->tag
        );
        
        return $plaintext;
    }
}
```

### 4. Replace Credentials in Configuration

**Before:**
```php
// config/aichatpro.php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),  // ❌ EXPOSED IN ENV
    'secret' => env('OPENAI_SECRET'),
]
```

**After:**
```php
// config/aichatpro.php
'openai' => [
    'api_key_reference' => CredentialVaultReference::create(
        vaultName: 'aws-secrets-manager',
        referencePath: '/aichatpro/openai/api-key',
        tenantId: $tenantId
    ),
    'secret_reference' => CredentialVaultReference::create(
        vaultName: 'aws-secrets-manager',
        referencePath: '/aichatpro/openai/secret',
        tenantId: $tenantId
    ),
]
```

### 5. Redact Secrets from Logs

```php
// app/Domains/Shared/Logging/SecretRedactor.php

class SecretRedactor {
    private array $secretPatterns = [
        'api_key',
        'secret',
        'password',
        'token',
        'credential',
        'auth',
        'bearer',
    ];
    
    public function redact(string $text): string {
        foreach ($this->secretPatterns as $pattern) {
            $text = preg_replace(
                "/$pattern['\"]?\s*:\s*['\"]([^'\"]+)['\"]/i",
                "$pattern" . ': "***REDACTED***"',
                $text
            );
        }
        return $text;
    }
    
    public function redactArray(array $data): array {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->redactArray($value);
            }
            if (is_string($value) && strlen($value) > 20 && $this->looksLikeSecret($value)) {
                return '***REDACTED***';
            }
            return $value;
        }, $data);
    }
    
    private function looksLikeSecret(string $value): bool {
        // Check for common secret patterns
        return preg_match('/^[a-zA-Z0-9_\-]{20,}$/', $value) ||
               preg_match('/^sk_[a-zA-Z0-9]+$/', $value) ||  // Stripe
               preg_match('/^pk_[a-zA-Z0-9]+$/', $value) ||  // Publishable key
               preg_match('/^Bearer\s+/', $value);
    }
}
```

### 6. Extensions Configuration Migration

```php
// Affected extensions need to migrate from direct secret storage to vault references:

// extensions/AIAgent/config/aiagent.php
// extensions/Chatbot/config/chatbot.php
// extensions/AIChatPro/config/aichatpro.php
// extensions/PhoneCallAgent/config/phonecallagent.php
// extensions/AIAgentGmail/config/aiagent-gmail.php
// extensions/AIAgentSlackChannel/config/aiagent-slack.php
// extensions/AIAgentWhatsappChannel/config/aiagent-whatsapp.php
```

### 7. Credential Lifecycle Management

```php
// app/Domains/Shared/Credentials/CredentialLifecycleManager.php

class CredentialLifecycleManager {
    /**
     * Rotate credentials before they expire
     */
    public function rotateExpiring(): void {
        $references = $this->vault->findExpiringCredentials(days: 7);
        
        foreach ($references as $reference) {
            // Notify users to refresh credentials
            $this->notificationService->notifyExpiring($reference);
            
            // Schedule rotation
            $this->scheduleRotation($reference);
        }
    }
    
    /**
     * Revoke credential when service no longer needed
     */
    public function revokeForService(string $service, string $tenantId): void {
        $references = $this->vault->listForTenant($tenantId);
        
        foreach ($references as $reference) {
            if (in_array($service, $reference->allowedServices)) {
                $this->vault->revoke($reference);
                $this->auditLog->record("Credential revoked for service: $service");
            }
        }
    }
}
```

## Exit Criteria (All must pass)

- ✅ CredentialVaultReference schema implemented
- ✅ Vault interface with implementations
- ✅ Envelope encryption for at-rest storage
- ✅ No secrets in logs, events, queue payloads
- ✅ All API keys use vault references only
- ✅ Secrets absent from configuration snapshots
- ✅ Envelope encryption tests pass
- ✅ Secret redaction works in audit logs
- ✅ Credential lifecycle management working

## Testing Requirements

1. **Vault Tests:**
   - Store and retrieve credentials
   - Rotation workflows
   - Revocation works
   - Expired credentials detected

2. **Encryption Tests:**
   - Encryption/decryption round trips
   - Different tenants can't decrypt each other's data
   - IV/nonce changes each encryption

3. **Redaction Tests:**
   - API keys redacted from logs
   - OAuth tokens redacted
   - Webhook secrets redacted
   - Redaction in error messages

4. **Integration Tests:**
   - Extension retrieves secret from vault
   - Secret used to authenticate provider
   - Secret rotated without breaking requests

## Implementation Phases

### Phase 1: Vault Interfaces (Day 1)
- Create CredentialVaultReference
- Define Vault interface
- Create local encrypted implementation

### Phase 2: Encryption (Day 2)
- Implement EnvelopeEncryption
- Create database schema for encrypted storage
- Write encryption tests

### Phase 3: Redaction (Day 3)
- Create SecretRedactor
- Update Logger to use redactor
- Verify logs are clean

### Phase 4: Configuration Migration (Day 4)
- Migrate extension configs to use references
- Update authentication code
- Test all providers work

### Phase 5: Lifecycle Management (Day 5)
- Implement rotation tracking
- Add expiration detection
- Create rotation workflows

### Phase 6: Testing & Cleanup (Day 6-7)
- Write comprehensive tests
- Verify all secrets removed
- Fix any integration issues

## Database Migrations

```sql
-- credential_vault table
CREATE TABLE credential_vault (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id VARCHAR(255) NOT NULL,
    vault_name VARCHAR(255) NOT NULL,
    reference_path VARCHAR(255) NOT NULL,
    reference_id VARCHAR(255),
    allowed_services JSON,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rotated_at TIMESTAMP NULL,
    next_rotation TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    usage_count INT DEFAULT 0,
    UNIQUE KEY unique_reference (tenant_id, vault_name, reference_path)
);

-- encrypted_data table
CREATE TABLE encrypted_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id VARCHAR(255) NOT NULL,
    key VARCHAR(255),
    encrypted_envelope LONGBLOB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_key (tenant_id, key)
);
```

## Files to Create/Modify

```
app/Domains/Shared/Credentials/
  └── CredentialVaultReference.php (NEW)
  └── Vault.php (NEW - interface)
  └── AWSSecretsManagerVault.php (NEW)
  └── LocalEncryptedVault.php (NEW)
  └── CredentialLifecycleManager.php (NEW)

app/Domains/Shared/Encryption/
  └── EnvelopeEncryption.php (NEW)
  └── EncryptedEnvelope.php (NEW)

app/Domains/Shared/Logging/
  └── SecretRedactor.php (NEW)
  └── RedactingLogger.php (NEW)

extensions/*/config/
  └── *.php (MODIFY - use vault references)

database/migrations/
  └── 2026_08_04_create_credential_vault.php (NEW)
  └── 2026_08_04_create_encrypted_data.php (NEW)

tests/Feature/Credentials/
  └── VaultTest.php (NEW)
  └── EncryptionTest.php (NEW)
  └── RedactionTest.php (NEW)
```

## Acceptance Criteria Checklist

- [ ] CredentialVaultReference implemented
- [ ] Vault interface with AWS/local implementations
- [ ] Envelope encryption working
- [ ] Secret redaction in logs
- [ ] All extension configs updated
- [ ] No plain secrets in database
- [ ] Credential rotation tested
- [ ] Vault tests passing
- [ ] Encryption tests passing
- [ ] No regression in existing functionality
- [ ] Documentation updated

## Related Issues

- #143: TenantContext & Authorization Policies
- #31: Credential Vault (if exists)
- #75: Audit and observability
- #20: PhoneCallAgent webhook hardening
- #61: Connector Runtime

## Next Steps

1. Create CredentialVaultReference
2. Implement Vault interface
3. Create EnvelopeEncryption
4. Create SecretRedactor
5. Update extension configs
6. Write comprehensive tests
7. Merge to main
