# Agent 1 Pass 2 — Local Chatbot Application Layer

This pass connects both the customer PWA and integrated staff inbox to one shared IndexedDB application layer.

## Added

- Schema v2 with shared offline metadata and stores for conversations, messages, drafts, participants, customer summaries, attachments, reviews, support requests, canned responses and cached knowledge articles.
- Repository APIs for create, upsert, soft delete, UUID/server-ID lookup, pagination, filtering, search and sync-status changes.
- Application services for offline conversation creation, message lifecycle actions, drafts, attachments, human support, reviews and JSON export.
- Unified local search across chatbot content.
- UI bridge for connectivity, pending count, last-sync presentation, draft persistence and queued-message actions.
- Staff inbox runtime integration without modifying the chatbot builder.

## Boundary

This pass does not implement Laravel sync routes, sync orchestration, vault hardening, conflict policy, attachment chunk transport, knowledge download packs or service-worker changes. Those remain with Agents 2–4.
