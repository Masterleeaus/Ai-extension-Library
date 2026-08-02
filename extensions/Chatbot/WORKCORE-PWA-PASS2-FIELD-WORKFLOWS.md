# WorkCore PWA Pass 2 — Field-worker workflows

This cumulative build adds an offline-first field workspace on top of Pass 1 synchronisation.

## Added
- Today's jobs and downloaded job-pack browser
- Offline job detail view
- Arrived, start and complete job commands
- Checklist completion commands and local drafts
- Dynamic job forms and queued submissions
- Time-entry capture
- Incident reporting
- Inventory-consumption capture
- Local-first evidence attachment capture
- Navigation handoff
- Pending/server-confirmed state preservation through the existing outbox
- Deep-link support for `?view=workcore-jobs`
- Action-specific notification URL support

## Boundary
The PWA renders server-provided projections and queues typed commands. Laravel WorkCore remains authoritative for permissions, validation, status transitions, inventory balances, compliance and final conflict resolution.
