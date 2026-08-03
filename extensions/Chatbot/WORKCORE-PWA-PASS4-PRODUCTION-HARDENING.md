# WorkCore PWA Pass 4 — Production hardening

Adds an AES-256-GCM device vault with PBKDF2-wrapped master key, automatic/manual locking, persistent-storage requests, quota warnings, stale sync recovery, attachment-integrity verification, secure logout/reset guards, unsynchronised-work unload warnings, browser self-tests, and service-worker migration version 9.

## Important boundaries
- The vault API is available for sensitive records and credentials; existing plaintext local records are not silently migrated or encrypted.
- Secure reset refuses to erase unsynchronised work unless `force: true` is explicitly supplied.
- Browser APIs remain progressive enhancements and are checked at runtime.
