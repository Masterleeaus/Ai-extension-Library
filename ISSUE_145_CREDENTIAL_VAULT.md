# Issue #145: Credential Vault References & Envelope Encryption (Phase 0 Foundation)

## Overview
Implement a secure credential vault system with envelope encryption to store and reference secrets without exposing them in logs, error messages, or unencrypted storage.

## Problem Statement
- Credentials (API keys, tokens, passwords) stored in plaintext risk exposure
- Secrets visible in logs and error messages cause security breaches
- Need audit trail for credential access
- Credential rotation must not break dependent systems

## Requirements

### Credential Vault Reference System
- Credentials stored with vault references (e.g., `vault://slack/api-key`)
- Reference resolves to encrypted value at runtime
- Envelope encryption: data encrypted with data key, data key encrypted with master key
- Secrets redacted in logs (replaced with `[REDACTED]`)
- Audit trail tracks all credential access

### Implementation

1. **CredentialVault Interface**
   - `store(key: string, value: string, ttl?: int): string` → Returns vault reference
   - `retrieve(reference: string): string` → Decrypts and returns value
   - `rotate(key: string, newValue: string): void` → Updates secret
   - `exists(reference: string): bool` → Check if exists

2. **VaultReference**
   - Format: `vault://namespace/key`
   - Immutable value object
   - Validation on construction

3. **EnvelopeEncryption**
   - Data key per credential
   - Data keys encrypted with master key
   - Master key rotatable without re-encrypting all data
   - AES-256-GCM for data encryption

4. **CredentialAudit**
   - Log all access: who, when, what, why
   - Immutable audit records
   - Query by: tenant, credential, actor, time range

## Testing Requirements
- Unit tests for encryption/decryption
- Unit tests for vault references
- Integration tests for credential storage and retrieval
- Tests for credential rotation
- Tests for secret redaction in logs
- Performance tests (decryption speed)
- Security tests (encryption strength verification)

## Acceptance Criteria
- ✅ Credentials stored encrypted (AES-256-GCM)
- ✅ Vault references resolve to secrets at runtime
- ✅ Secrets redacted from all logs
- ✅ Audit trail captures access
- ✅ Master key rotation possible
- ✅ Performance: decryption <5ms
- ✅ All tests pass (18+ assertions)

## Related Issues
- Depends on: #144 (EventEnvelope for audit events)
- Blocks: #146 (Webhook Verification - needs webhook secrets)

## Files to Create
- `app/Domains/Shared/Vault/CredentialVault.php` (Interface)
- `app/Domains/Shared/Vault/CredentialVaultImpl.php` (Implementation)
- `app/Domains/Shared/Vault/VaultReference.php` (Value object)
- `app/Domains/Shared/Vault/EnvelopeEncryption.php` (Implementation)
- `app/Domains/Shared/Vault/CredentialAudit.php` (Value object)
- `app/Domains/Shared/Vault/CredentialAuditRepository.php` (Interface)
- `tests/Feature/Vault/CredentialVaultTest.php`
- `tests/Feature/Vault/EnvelopeEncryptionTest.php`

## Timeline
- **Phase 0 Foundation**
- Start after: #144 (EventEnvelope)
- Estimated effort: 3 days
