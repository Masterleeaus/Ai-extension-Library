# Target Architecture and Upgrade Roadmap

## Target architecture

```text
Titan AI Foundation
├── Tenant Context and Authorization
├── Unified Registry
├── Unified Event Bus
├── Unified Memory
├── Credential Vault
├── Usage and Budget Engine
├── Audit and Observability
└── Feature Flags and Migration Controls

Bounded Domain Engines
├── Knowledge Engine
├── Tool Execution and Governance
├── Voice Engine
├── Customer Identity Engine
├── Booking Engine
├── Commerce Engine
├── Connector Runtime
├── Skill Runtime
└── Workflow Engine

Product Surfaces
├── AIChatPro
├── Chatbot
├── AIAgent
├── PhoneCallAgent
├── MarketingBot
├── SocialMediaAgent
└── Creative and specialist extensions

Operational Authority
└── WorkCore
```

## Authority map

| Concern | Authority |
|---|---|
| Internal interactive AI workspace | AIChatPro |
| Customer-facing conversations and immediate channel interaction | Chatbot |
| Offline PWA and sync protocol | Chatbot sync runtime |
| Durable schedules, retries, delayed work, and autonomous workflows | AIAgent workflow engine |
| Canonical operational business records | WorkCore |
| Documents, chunks, embeddings, retrieval, and citations | Knowledge Engine |
| Calls, sessions, transcripts, recordings, usage, and analysis | Voice Engine |
| Cross-channel customer identities, contact points, consent, and merge history | Customer Identity Engine |
| Appointments and provider synchronization | Booking Engine |
| Catalogs, carts, checkout, orders, and inventory references | Commerce Engine |
| External messaging authentication, webhooks, normalization, and delivery | Connector Runtime |
| Contextual facts, preferences, summaries, commitments, and references | Unified Memory |
| Tools, skills, agents, workflows, providers, and connectors discovery | Unified Registry |
| Risk, permissions, approval, receipts, rollback, budgets, and audit | Shared Governance |

## Shared contracts

### Execution context

Every tool, workflow action, provider call, queue job, event consumer, and domain service receives an explicit context containing:

- tenant ID;
- user and actor identity;
- agent and conversation identity;
- customer identity when known;
- correlation and causation IDs;
- permissions and policy version;
- channel and locale;
- budget and usage constraints;
- request idempotency key.

Services must not rely exclusively on browser authentication state because queues, webhooks, commands, and event consumers run outside normal requests.

### Registry entity types

The registry must preserve distinct entity types:

| Type | Responsibility |
|---|---|
| Tool | Executable operation with typed input/output and side-effect policy |
| Skill | Instructions, domain strategy, examples, and tool-use policy |
| Agent | Runtime identity with goals, tools, skills, permissions, and budgets |
| Workflow | Durable or repeatable sequence of triggers, decisions, and actions |
| Connector | External-system authentication, inbound/outbound capabilities, and health |
| Provider | Implementation of a domain capability such as embeddings, telephony, TTS, booking, or commerce |

### Event envelope

Every event carries:

- immutable event ID;
- event type and schema version;
- tenant ID;
- aggregate type and ID;
- occurred-at timestamp;
- actor identity;
- correlation and causation IDs;
- idempotency key;
- compact payload and metadata.

Events contain identifiers and compact metadata only. They do not serialize Eloquent models, credentials, full documents, full transcripts, or recordings.

## Phase 0 — security and foundation

### Deliverables

- TenantContext and tenant-aware repositories.
- Authorization policies for tools, assignments, ingestion, workflows, connectors, and administrative actions.
- EventEnvelope and idempotent event consumers.
- Credential vault references with envelope encryption and rotation metadata.
- Webhook verifier contract with timestamp, signature, replay, and tenant resolution checks.
- SSRF-safe fetcher for URL ingestion.
- MIME, magic-byte, archive, macro, and malware controls for files.
- Tool/action permissions, budgets, rate limits, timeout policy, and receipts.
- Audit logging, secret redaction, retention policy, and correlation tracing.
- Feature flags, migration state, and rollback switches.

### Exit criteria

- No autonomous webhook can execute an action without verification and tenant resolution.
- No tool can execute without explicit permissions and budget context.
- Replayed webhook and ingestion requests are idempotent.
- Secrets are absent from logs, events, queue payloads, and tool results.
- Cross-tenant repository tests pass.

## Phase 1 — Knowledge Engine

### Deliverables

- Canonical `knowledge_*` schema with immutable document versions.
- Private object storage for large raw and normalized content.
- Typed text, file, URL, website, API, and integration source commands.
- Durable queue pipeline with resumable stages and granular progress.
- Source, content, chunk, and embedding deduplication.
- Keyword and vector-store adapters.
- Hybrid fusion, reranking, permission filters, and context-budget selection.
- Stable citations and query logs with retention controls.
- Knowledge-base assignments to agents, chatbots, workflows, teams, and users.
- `search_knowledge` registry tool returning evidence rather than generating the final answer.
- Compatibility adapters for one legacy training controller, then progressive migration.

### Exit criteria

- One source is ingested once and assigned to at least two independent agents.
- Both agents retrieve the same evidence with stable citations.
- Tenant isolation, retries, cancellation, stale-job recovery, and provider-degradation tests pass.
- A legacy training UI continues to function unchanged through an adapter.

## Phase 2 — tool execution and governance

### Deliverables

- Shared `ToolDefinition`, `ExecutionContext`, `ToolResult`, and `ActionReceipt` contracts.
- Input and output schema validation.
- Provider-neutral schema conversion for OpenAI, Anthropic, Gemini, and future models.
- Permission, risk, approval, timeout, retry, budget, idempotency, and rollback policy.
- Unified usage ledger and per-provider cost attribution.
- Conformance adapters for AIChatPro connectors, AIAgent actions, Chatbot tools, PhoneCallAgent tools, MarketingBot, and SocialMediaAgent.
- Registry dependency and semantic version constraints replacing extension-name checks.

### Exit criteria

- The same governed tool executes safely from AIChatPro, Chatbot, and AIAgent.
- Side effects produce receipts and can be traced by correlation ID.
- Denied, failed, retried, approved, and rolled-back executions are tested.

## Phase 3 — Voice Engine

### Capability boundaries

- `TelephonyProvider`
- `RealtimeTransportProvider`
- `SpeechRecognitionProvider`
- `ConversationProvider`
- `SpeechSynthesisProvider`
- `RecordingProvider`

Twilio, ElevenLabs, and OpenAI may participate in one composed call pipeline; failover occurs per capability rather than swapping an entire provider stack blindly.

### Deliverables

- Canonical call, session, participant, transcript segment, recording, event, provider-attempt, usage, consent, redaction, and analysis records.
- Shared call lifecycle and transcript events.
- Capability health checks and controlled fallback.
- Recording retention and consent policy.
- Post-call summarization and memory-promotion policy.
- Adapters for PhoneCallAgent, ChatbotVoice, and ChatbotVoiceCall.

### Exit criteria

- A call can be initiated, transcribed, metered, audited, and summarized through the shared engine.
- Full transcripts remain canonical in the Voice Engine; only derived facts are promoted to Unified Memory.
- Provider outages degrade by capability with traceable attempts.

## Phase 4 — customer identity

### Deliverables

- Customer, identity, contact point, channel profile, external mapping, consent, tag, interaction, merge candidate, and merge audit records.
- Reversible identity merges with provenance and confidence.
- Shared customer lookup tools for chat, voice, email, WhatsApp, booking, commerce, campaigns, and social channels.
- Data retention, deletion, and consent enforcement.

### Exit criteria

- The same person can be recognized across at least two channels without destructive automatic merging.
- Every merge is explainable, reversible, and tenant-scoped.

## Phase 5 — booking, commerce, and connectors

### Booking Engine

Owns availability, bookings, attendees, provider mappings, synchronization, cancellation, and rescheduling. Calendly and Cal.com become adapters.

### Commerce Engine

Owns catalog search, pricing, carts, checkout, orders, inventory references, and provider mappings. Shopify and WooCommerce become adapters. Operational order records continue to resolve through WorkCore authority rules.

### Connector Runtime

Owns external authentication, credential references, webhook registration, inbound normalization, outbound dispatch, health, retries, rate-limit telemetry, and dead-letter handling. Slack, WhatsApp, Gmail, Telegram, Teams, Instagram, and Messenger remain channel adapters.

### Exit criteria

- Chat and voice can call the same booking and commerce operations.
- Connectors use one verified inbound pipeline and one observable outbound delivery pipeline.
- Commerce, booking, and connector records remain in separate bounded schemas.

## Phase 6 — progressive migration and Chatbot modularisation

### Migration strategy

1. Introduce shared engines behind feature flags.
2. Backfill legacy data into canonical stores.
3. Compare counts, hashes, relationships, and permissions.
4. Shadow-read legacy and new systems.
5. Evaluate result agreement, retrieval quality, and latency.
6. Use temporary dual writes only where rollback safety requires them.
7. Migrate one extension at a time.
8. Freeze legacy writes after stable cutover.
9. Retain rollback during the defined migration window.
10. Archive legacy tables according to retention policy.

### Chatbot internal modules

Keep the `Chatbot` marketplace identity while creating internal versioned modules around:

- conversation and staff inbox;
- channels;
- team chat;
- offline sync;
- Titan AI orchestration;
- governance;
- generative UI and app shell;
- compatibility adapters.

## Testing programme

### Root architecture tests

- boot every manifest and service provider in dependency order;
- detect duplicate routes, migrations, namespaces, and service bindings;
- verify semantic dependency constraints;
- verify no extension shadows WorkCore authority classes.

### Shared contract conformance

- tool and action conformance;
- provider health and fallback;
- webhook verification and replay prevention;
- tenant isolation;
- credential and log redaction;
- queue retry, cancellation, heartbeat, stale recovery, and dead-letter behavior;
- migration and rollback;
- permission and budget enforcement.

### Cross-suite integration paths

```text
AIChatPro chat
→ governed tool
→ approval
→ WorkCore write
→ action receipt
→ Chatbot sync
```

```text
Customer voice call
→ transcript segment
→ intent/tool request
→ governed booking action
→ confirmation
→ derived memory
```

```text
Knowledge source
→ secure ingestion
→ assignment
→ Chatbot retrieval
→ AIAgent retrieval
→ citation verification
```

## Indicative effort

| Workstream | Focused effort |
|---|---:|
| Phase 0 foundation and security | 2–4 weeks |
| Knowledge Engine and first migration | 8–13 weeks |
| Shared tool execution and governance | 4–7 weeks |
| Voice Engine | 6–10 weeks |
| Customer identity | 3–6 weeks |
| Booking Engine | 3–6 weeks |
| Commerce Engine | 4–8 weeks |
| Connector Runtime | 3–6 weeks |
| Full progressive migration and integration testing | continuous |

Parallel teams can reduce calendar time, but not total engineering effort. One developer should expect a multi-month programme.
