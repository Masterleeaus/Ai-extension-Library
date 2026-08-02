# Titan AI Governance — Final Hardening Pass

## Repairs

- Approval execution now uses a locked `executing` claim state.
- WorkCore failures move approvals to retryable `execution_failed` rather than leaving them permanently approved.
- Stale execution claims are recovered after a configurable lease.
- Approval and governance configuration views require `govern-ai-actions`.
- Governed mutations require tenant, user, permission and stable idempotency context.
- Rollback accepts only successfully executed actions, requires a canonical authoritative ID and sends a stable rollback idempotency key.
- Added migration fields for execution attempts, errors and receipt failure reasons with complete rollback.

## Host integration requirements

- The host user model must implement permissions for `govern-ai-actions`, `rollback-ai-actions`, and each WorkCore tool permission.
- Callers must pass `tenant_id`, `user_id`, `idempotency_key`, and either `granted_permissions` or a `permission_checker` into governed tool execution.
- WorkCore mutation handlers must enforce the same permissions and honour idempotency keys.
- Real Laravel database/container and browser tests still require installation into the host application; the extension ZIP alone does not include that runtime.
