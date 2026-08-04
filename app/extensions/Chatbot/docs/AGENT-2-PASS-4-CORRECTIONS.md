# Agent 2 Pass 4 — Bidirectional Capture and Recovery

This pass closes the highest-priority gaps found during the post-completion audit.

## Added

- Eloquent observers for conversations, messages and customers so normal server-side creates, updates and deletions enter the sync change journal.
- Observer suppression while Agent 2 applies client-originated operations, preventing duplicate change records.
- Tombstones for server-originated deletions.
- Explicit client bootstrap flow and per-scope bootstrap completion hooks.
- Durable server-change inbox adapter hooks before applying and acknowledging pulled changes.
- Weak-connection batch reduction using the browser Network Information API when available.
- Persistent pull session records and session UUIDs in pull responses.
- Conflict-resolution transport hooks that apply keep-local or merged records, journal the canonical update and return blocked operations to reviewable state.
- Complete rollback of offline-aware columns in the sync migration.

## Adapter additions

The client engine remains backward compatible. For crash-safe inbox recovery, Agent 1 should provide:

- `storeInboxChanges(changes)`
- `getPendingInboxChanges()`
- `markInboxChangesApplied(syncSequences)`
- `isBootstrapComplete(scope)`
- `setBootstrapComplete(scope, complete)`

If inbox methods are absent, the engine falls back to the existing direct `applyServerChanges` behaviour.
