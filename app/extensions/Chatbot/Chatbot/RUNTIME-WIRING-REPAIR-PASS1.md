# Titan AI Runtime Wiring Repair — Pass 1

This pass repairs the authoritative worker-discovery and execution boundary.

## Changes

- Canonical ID and compatibility-alias resolution for worker routing.
- Duplicate worker-ID detection instead of silent overwrites.
- Tier and definition lookup for every discovered worker.
- Runtime diagnostics endpoint at `GET /api/v2/chatbot/ai/runtime/diagnostics`.
- Optional permission-snapshot enforcement before governed execution.
- Canonical manager, assistant and agent IDs added to execution context.
- Full worker definitions added to governed execution context.

## Safety

Permission snapshots are enforced only when `available_permissions` is supplied by the authenticated host context. Existing Laravel policies remain authoritative otherwise.
