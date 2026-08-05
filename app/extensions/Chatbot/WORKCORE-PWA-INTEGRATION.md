# WorkCore PWA Integration

This package adds a client-side WorkCore edge runtime without copying Laravel domain models or server business rules into the chatbot extension.

## Added

- Versioned IndexedDB database (`titan-workcore-device`) with records, job packs, drafts, outbox, conflicts, attachments, metadata and sync logs.
- Typed WorkCore command contract aligned to `/api/v1/sync/operations` and `/api/v1/workcore/actions`.
- Stable operation IDs and idempotency keys.
- Queued, sending, sent, failed, needs-review, conflict and cancelled states.
- HTTP 409/412 conflict capture with local/server snapshots.
- Exponential retry delay, manual retry, cancellation and online reconnection sync.
- Background Sync wake-up hooks.
- OPFS-first attachment storage with IndexedDB fallback and SHA-256 checksums.
- WorkCore local record and offline job-pack repositories.
- Tenant, user and device context bootstrap.
- Floating offline queue and manual Sync now interface.
- Full local-data reset API.
- Backwards-compatible `window.chatbotPwa.queueTask()` facade.

## Public client API

```js
await TitanWorkCore.client.configure({ company_id, user_id, api_base });
await TitanWorkCore.client.queue('operations.work-order.change-status', payload, {
  resource_key: 'work_order:123',
  base_revision: 4,
  base_etag: '"work-order-123-v4"'
});
await TitanWorkCore.client.sync();
await TitanWorkCore.client.saveJobPack(pack);
await TitanWorkCore.attachments.save(file, { resource_key: 'work_order:123' });
```

## Server boundary

The Laravel WorkCore server remains authoritative for permissions, status policies, scheduling, finance, payroll, inventory balances, compliance and final conflict decisions. The PWA stores selected projections and submits commands only.

## Server additions still required

The unified WorkCore server currently exposes operation submission and status endpoints. For full offline job-pack pull synchronisation it should also expose:

- `GET /api/v1/workcore/offline/job-packs`
- `GET /api/v1/workcore/offline/changes?cursor=...`
- `POST /api/v1/workcore/offline/attachments`

The client contracts already reserve these endpoints. Until they exist, local job packs can be supplied by existing authenticated application responses through `TitanWorkCore.client.saveJobPack()`.
