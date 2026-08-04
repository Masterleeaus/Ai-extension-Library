# Deep Audit Repair Pass 8 — Offline and PWA Integrity

Confirmed defects repaired:

1. `offline-store.js` queried non-existent camelCase IndexedDB indexes (`chatbotId`, `conversationId`) while `device-db.js` defines snake_case indexes. This broke local conversation, message and attachment reads.
2. `sync/adapter.js` created a second IndexedDB database and second outbox, producing competing local sources of truth. It now uses `TitanDeviceDB`.
3. The outbox registered the `titan-chatbot-outbox` background-sync tag while the service worker only handled `workcore-outbox`. The service worker now handles both and wakes both sync clients.
4. Device DB schema v5 adds shared `syncRecords` and `syncInbox` stores for durable interrupted-pull recovery without introducing a parallel database.

No unsynchronised outbox records are cleared by these changes.
