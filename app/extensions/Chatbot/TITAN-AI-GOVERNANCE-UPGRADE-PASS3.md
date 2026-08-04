# Titan AI Governance Upgrade — Pass 3

Implemented approval and rollback execution:

- Tenant-scoped pending approval dashboard and JSON endpoint.
- Transactional approve/reject operations with row locking.
- Approval expiry enforcement and duplicate-resolution prevention.
- Exact stored WorkCore action execution after approval.
- Idempotency key derived from the approval when none was supplied.
- Action receipt state updates for rejection and approved execution.
- Permission-gated rollback endpoint.
- Compensating WorkCore rollback operation from the stored rollback contract.
- Rollback result, actor and timestamp persistence.
- Generative UI approval-card marker for adaptive rendering.

Remaining main passes: 3.
