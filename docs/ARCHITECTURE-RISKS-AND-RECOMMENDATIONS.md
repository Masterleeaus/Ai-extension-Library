# Architecture, Risks, and Recommendations

## Extraction decisions

- Selected **78 extensions** as directly AI-powered, AI-enabling, conversational, autonomous, model/provider, creative, growth, or business-intelligence components.
- Retained the larger `Chatbot` folder as canonical.
- Excluded `TitanZeroChatbot` from materialisation because it is an older near-duplicate: zero unique files, one changed service provider, and nine files missing relative to `Chatbot`.
- Seventeen non-AI utility/payment/menu/onboarding/integration extensions remain outside the selected tree. Their metadata remains in the full archive scan so dependencies can be recovered deliberately; the compact selected-extension inventory contains only the 78 materialised AI extensions.
- Original paths and binary bytes are preserved in the MiniUp lossless transport dataset and verified by SHA-256 during reconstruction.

## Documentation and scan limits

The catalogue is based on static inspection of manifests, PHP classes, migrations, routes, dependencies, TODO markers, provider references, and selected execution patterns. Static detection does not prove that a route is reachable, that a service provider boots successfully, or that dynamically registered behaviour is complete. Runtime boot tests, route registration tests, migration tests, provider contract tests, and host integration tests remain required.

The compact CSV and JSON inventories contain manifest-derived metadata and size counts. They do not contain the detailed methods, routes, tables, dependencies, or scanner findings described in the catalogue pages.

Known upstream manifest defects are preserved in the compact inventories:

- `AzureOpenai` identifies itself as `Example`.
- `AiViralClips` identifies itself as `Example`.
- `AiVideoPro` identifies itself as `AI Avatar Pro`, despite implementing the AI video generation capability documented in the catalogue.

These should be corrected in the extension manifests before marketplace publication. Catalogue headings use capability-derived names so that the documentation remains understandable.

## Strongest reusable engines

1. **AIChatPro connector registry:** provider-neutral tool schemas, plan gates, access scoping, credential lifecycle, and dispatch.
2. **AIAgent workflow engine:** triggers, actions, branching, delayed work, channels, memory, and copilot-assisted authoring.
3. **Chatbot offline sync protocol:** device registration, changes, cursors, tombstones, acknowledgements, conflicts, and reconciliation.
4. **Chatbot governance:** risk classification, councils, human approval, receipts, rollback, permissions, persona/skill/memory governance.
5. **Five-tier orchestration:** intent routing, chain planning, delegation, worker selection, confidence fallback, and vertical specialists.
6. **Model Council:** concurrent multi-model answers, synthesis, preference capture, and response persistence.
7. **Creative spatial engines:** annotation-guided editing, masked transformation, editable template generation, and timeline video composition.
8. **Channel and staff inbox runtime:** customer identity, tags, realtime handoff, multi-channel messaging, and internal team chat.

## Confirmed structural risks

### 1. Capability duplication

Tool execution exists independently in AIChatPro connectors, AIAgent actions/tool calling, Chatbot field-service tools, Tier-3 agents, phone-call tools, MarketingBot, and SocialMediaAgent. Without a shared contract, permissions, schemas, retries, receipts, and idempotency will drift.

### 2. Chatbot scope concentration

The canonical Chatbot package has 1,548 files, 93 migrations, 201 detected routes, an offline runtime, staff inbox, team chat, channels, app shell, five-tier AI, WorkCore bridging, governance, skills, models, and booking/ecommerce compatibility. It should be treated as a modular platform even if distributed as one extension.

### 3. Missing test coverage

Only the canonical Chatbot package contains a meaningful bundled test tree. The other 77 selected extensions have no detected test files. This is the largest reliability gap in the archive.

### 4. Extension-name coupling and manifest integrity

Many packages detect one another using marketplace registration strings with inconsistent casing and slug conventions. Several manifests also contain placeholder or mismatched names. This is brittle, harms marketplace display, and prevents safe version negotiation. Runtime discovery should use stable package identifiers, semantic versions, and capability declarations rather than display names.

### 5. Provider and credential surface

The ecosystem references OpenAI, Anthropic, Gemini, DeepSeek, xAI, OpenRouter, Perplexity, Serper, Telegram, Meta, Slack, Gmail, Twilio, ElevenLabs, Calendly/Cal.com, Azure, and several media providers. Credentials should be represented as vault references, never copied into extension configuration or database fields without envelope encryption.

### 6. Webhook and autonomous-action risk

AIAgent, Chatbot channels, PhoneCallAgent, social automation, and media-provider callbacks expose inbound webhooks and autonomous actions. Every endpoint needs signature validation, replay prevention, tenant resolution, idempotency, rate limiting, audit receipts, and explicit action permissions.

### 7. Route and callback collisions

Several independently installable extensions declare the same route shapes:

- `FluxPro`, `NanoBanana`, and `SeeDreamV4` each declare `ANY generator/webhook/fal-ai`.
- `ModelCouncil` and `MultiModel` each declare `POST /accept-response`.

Laravel route ownership will depend on registration order unless these routes are namespaced or consolidated. Enabling colliding packages without a deterministic route contract can silently direct callbacks to the wrong controller. Add a repository-level route collision test and require unique route names, package prefixes, or one shared provider callback dispatcher.

### 8. Shell/process execution

VideoEditor legitimately invokes FFmpeg via `proc_open` and `exec`. Inputs and paths must be strictly allow-listed, arguments escaped, jobs isolated, time/memory/output limited, and command templates tested against injection.

### 9. Migration and namespace collision

The Chatbot package embeds compatibility runtimes and custom autoload maps. It already avoids shadowing the host WorkCore domain unless a legacy flag is enabled and the host class is absent. This safeguard must remain mandatory and should be backed by boot-time architecture diagnostics.

### 10. Authority and storage conflation

Knowledge documents, embeddings, calls, transcripts, bookings, carts, orders, customer identities, credentials, tools, and skills must not be collapsed into a generic memory repository. Each bounded domain needs authoritative storage and explicit contracts. UnifiedMemory should hold derived context and references, not replace canonical domain records.

## Recommended repository layout after materialisation

```text
extensions/                 # Original, installable extension folders
  AIChatPro/
  AIAgent/
  Chatbot/
  ...
docs/                       # Deep scan, catalogue, inventory, audit notes
upgrade plan/               # Target architecture and staged implementation documents
scripts/                    # Lossless restore and verification
.github/workflows/          # Dataset materialisation workflow
```

Do not reorganise the extension folder internals until the host marketplace loader, namespaces, service-provider discovery, migration paths, and asset publishing paths have been tested.

## Priority implementation sequence

1. Materialise and checksum-verify the source tree.
2. Add root architecture tests that validate manifests, boot every extension service provider in dependency order, and fail on route-name or route-method/path collisions.
3. Introduce tenant-aware `CapabilityDefinition`, `ToolDefinition`, `ExecutionContext`, `ExecutionResult`, `ActionReceipt`, and immutable event-envelope contracts.
4. Establish bounded engines for knowledge, voice, customer identity, booking, commerce, connectors, skills, and workflows; do not use UnifiedMemory as their canonical store.
5. Route all operational writes through governance and WorkCore gateways.
6. Add webhook security, replay protection, idempotency, credential-vault, and provider-callback conformance tests.
7. Add per-extension smoke tests, migration rollback tests, route authorisation tests, and dependency compatibility tests.
8. Split Chatbot internally into versioned modules without changing its external marketplace identity.
9. Consolidate provider/model profiles and usage accounting across AIChatPro, Chatbot, and AIAgent.
10. Migrate progressively through feature flags, compatibility adapters, shadow reads, measurable retrieval or execution comparisons, and reversible cutovers.
