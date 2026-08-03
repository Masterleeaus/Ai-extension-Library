# AI Extensions Upgrade Plan

## Purpose

This folder turns the existing deep scan of `extensions/` into an executable, evidence-based upgrade programme for the AI extension ecosystem.

The repository currently contains 78 selected AI extensions and 4,204 source and asset files. The three largest authority centres are:

1. **AIChatPro** — internal human-facing AI workspace and connector/tool surface.
2. **Chatbot** — customer conversation, channel, PWA/offline, generative UI, governance, and immediate AI orchestration runtime.
3. **AIAgent** — durable autonomous workflow, triggers, delayed jobs, channels, memory, and cross-extension tool execution.

The upgrade programme preserves these products while removing duplicated infrastructure and establishing hard authority boundaries.

## Architectural rules

1. **UnifiedMemory is contextual memory, not canonical domain storage.** Documents, chunks, embeddings, calls, transcripts, bookings, carts, orders, and credentials retain dedicated domain stores.
2. **Tools, skills, agents, workflows, connectors, and providers remain distinct registry entity types.** They may share discovery infrastructure but not contracts or lifecycles.
3. **WorkCore remains authoritative for operational business records.** AI extensions must write through governed WorkCore gateways rather than create competing CRM, job, invoice, inventory, or payment authorities.
4. **Security is Phase 0.** Tenant resolution, authorization, webhook verification, replay prevention, SSRF protection, idempotency, rate limits, secret handling, and audit receipts precede broad migration.
5. **Legacy extension identities and public routes remain stable during migration.** Existing controllers become thin compatibility adapters over shared engines.
6. **Shared engines are bounded domains, not one new monolith.** Knowledge, voice, customer identity, booking, commerce, connectors, governance, and workflows own separate schemas and interfaces.

## Evidence from the scan

- `Chatbot` contains 1,548 files, 93 migrations, 201 detected routes, and 51 tests. It already spans conversation runtime, channels, offline sync, five-tier AI, governance, skills, provider profiles, and operational app bridges.
- `AIAgent` contains 169 files, 20 migrations, 37 routes, and no detected bundled tests. It is the correct authority for durable workflows but needs stronger webhook, retry, idempotency, permission, timeout, and budget controls.
- `AIChatPro` contains 47 files and a reusable provider-neutral connector registry, but no bundled automated tests.
- `PhoneCallAgent`, `ChatbotVoice`, and `ChatbotVoiceCall` duplicate voice configuration, training, session, transcript, provider, and call-history concerns.
- Tool execution appears independently in AIChatPro connectors, AIAgent actions/tool calling, Chatbot tools and Tier-3 agents, PhoneCallAgent, MarketingBot, SocialMediaAgent, and bridge extensions.
- The canonical Chatbot extension is the only package with meaningful bundled tests; the other selected extensions have no detected test trees.

## Programme documents

- [`00-DEEP-SCAN-FINDINGS.md`](00-DEEP-SCAN-FINDINGS.md) — evidence, duplicated capabilities, authority conflicts, risks, and upgrade opportunities.
- [`01-TARGET-ARCHITECTURE-AND-ROADMAP.md`](01-TARGET-ARCHITECTURE-AND-ROADMAP.md) — target bounded contexts, authority map, phases, dependencies, and completion criteria.
- [`02-KNOWLEDGE-ENGINE-FOUNDATION.md`](02-KNOWLEDGE-ENGINE-FOUNDATION.md) — first production vertical slice: secure ingestion, assignments, hybrid retrieval, citations, and legacy adapters.

## Recommended implementation order

1. Phase 0 — shared contracts, tenant context, permissions, event envelope, credentials, idempotency, audit, webhook and ingestion security.
2. Phase 1 — Knowledge Engine foundation and migration of one training controller.
3. Phase 2 — shared tool execution contracts, action receipts, permission checks, and registry governance.
4. Phase 3 — Voice Engine consolidation by capability, not whole-provider switching.
5. Phase 4 — customer identity and cross-channel interaction timeline.
6. Phase 5 — booking, commerce, and connector runtimes as separate bounded domains.
7. Phase 6 — progressive extension migration, integration tests, and internal modularisation of Chatbot.

## First proof milestone

The first production proof is complete when:

```text
One source is submitted once
→ securely validated
→ asynchronously ingested
→ versioned and chunked
→ indexed for keyword and vector retrieval
→ assigned to two different agents
→ retrieved through one SearchKnowledgeBase tool
→ returned with stable source citations
→ legacy training routes continue to work through adapters
→ cross-tenant access is impossible
→ retries do not duplicate data
```

This milestone validates tenancy, security, contracts, queues, events, deduplication, assignments, retrieval, citations, registry access, and backward compatibility without an unsafe all-at-once rewrite.
