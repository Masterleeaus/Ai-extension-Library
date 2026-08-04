# Agent 3 Threat Model

## Protected assets

Offline identity, permission snapshots, pending operations, conflict evidence, sensitive metadata, audit events, and vault secrets.

## Addressed threats

- PIN disclosure: PIN is used only for PBKDF2 derivation and is not persisted.
- Offline database inspection: sensitive values can be stored through AES-256-GCM vault records.
- Ciphertext modification/corruption: GCM authentication rejects altered records.
- Nonce reuse: a fresh random 96-bit nonce is generated per encrypted item.
- Stale authority: cached permissions and sessions expire; high-risk actions remain provisional.
- Lost/revoked device: revocation locks the vault and invalidates the local session when received.
- Destructive reset: pending data requires confirmation and a recovery backup is created first.
- Silent conflicts: conflict records preserve both versions, actors, timestamps, changed fields and resolution history.
- Sensitive logs: audit details redact password, PIN, secret, token, key, authorization and credential fields.

## Residual risks

- A compromised browser runtime can read data while the vault is unlocked.
- PIN-only security depends on PIN entropy and device security.
- Lockout state is memory-only and resets when the page process restarts.
- Remote logout and revocation only apply after Agent 2 delivers the server event.
- Browser storage can still be evicted unless Agent 4 obtains persistent storage.
