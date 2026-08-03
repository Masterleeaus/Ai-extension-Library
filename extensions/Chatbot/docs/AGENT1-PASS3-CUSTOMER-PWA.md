# Agent 1 Pass 3 — Customer PWA Local-First Integration

This pass connects the existing customer-facing chatbot UI to the shared IndexedDB repositories without replacing the builder.

## Added behaviour

- Reads cached conversations before requesting the server session.
- Falls back to cached conversations when the session request fails.
- Reads cached messages before requesting the server thread.
- Merges canonical server conversations/messages into local records by server ID.
- Creates a local conversation immediately when offline.
- Creates each outgoing message locally before network delivery.
- Stores selected attachments locally before delivery.
- Shows queued, failed, conflict and other lifecycle states.
- Supports retry, edit and cancellation controls for local messages.
- Restores and autosaves per-conversation drafts.
- Cancels the matching outbox operation after successful legacy API delivery to prevent duplicate replay.

## Boundaries

No Laravel sync routes, schema migration framework, encryption changes, chunk upload protocol, knowledge pack infrastructure or service-worker changes are included.
