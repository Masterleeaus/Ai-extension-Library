# Architecture, Risks, and Recommendations

## Extraction decisions

- Selected **78 extensions** as directly AI-powered, AI-enabling, conversational, autonomous, model/provider, creative, growth, or business-intelligence components.
- Retained the larger `Chatbot` folder as canonical.
- Excluded `TitanZeroChatbot` from materialisation because it is an older near-duplicate: zero unique files, one changed service provider, and nine files missing relative to `Chatbot`.
- Seventeen non-AI utility/payment/menu/onboarding/integration extensions remain outside the selected tree. Their metadata remains in the full scan inventory so dependencies can be recovered deliberately.
- Original paths and binary bytes are preserved in the MiniUp lossless transport dataset and verified by SHA-256 during reconstruction.

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

The canonical Chatbot package has 1,548 files, 93 migrations, 201 routes, an offline runtime, staff inbox, team chat, channels, app shell, five-tier AI, WorkCore bridging, governance, skills, models, and booking/ecommerce compatibility. It should be treated as a modular platform even if distributed as one extension.

### 3. Missing test coverage

Only the canonical Chatbot package contains a meaningful bundled test tree. The other 77 selected extensions have no detected test files. This is the largest reliability gap in the archive.

### 4. Extension-name coupling

Many packages detect one another using marketplace registration strings with inconsistent casing and slug conventions. This is brittle and prevents safe version negotiation.

### 5. Provider and credential surface

The ecosystem references OpenAI, Anthropic, Gemini, DeepSeek, xAI, OpenRouter, Perplexity, Serper, Telegram, Meta, Slack, Gmail, Twilio, ElevenLabs, Calendly/Cal.com, Azure, and several media providers. Credentials should be represented as vault references, never copied into extension configuration or database fields without envelope encryption.

### 6. Webhook and autonomous-action risk

AIAgent, Chatbot channels, PhoneCallAgent, and social automation expose inbound webhooks and autonomous actions. Every endpoint needs signature validation, replay prevention, tenant resolution, idempotency, rate limiting, audit receipts, and explicit action permissions.

### 7. Shell/process execution

VideoEditor legitimately invokes FFmpeg via `proc_open` and `exec`. Inputs and paths must be strictly allow-listed, arguments escaped, jobs isolated, time/memory/output limited, and command templates tested against injection.

### 8. Migration and namespace collision

The Chatbot package embeds compatibility runtimes and custom autoload maps. It already avoids shadowing the host WorkCore domain unless a legacy flag is enabled and the host class is absent. This safeguard must remain mandatory and should be backed by boot-time architecture diagnostics.

## Recommended repository layout after materialisation

```text
extensions/                 # Original, installable extension folders
  AIChatPro/
  AIAgent/
  Chatbot/
  ...
docs/                       # Deep scan, catalogue, inventory
scripts/                    # Lossless restore and verification
.github/workflows/          # Dataset materialisation workflow
```

Do not reorganise the extension folder internals until the host marketplace loader, namespaces, service-provider discovery, migration paths, and asset publishing paths have been tested.

## Priority implementation sequence

1. Materialise and checksum-verify the source tree.
2. Add a root architecture test that boots every extension manifest and service provider in dependency order.
3. Introduce `CapabilityDefinition`, `ToolDefinition`, `ExecutionContext`, `ExecutionResult`, and `ActionReceipt` shared contracts.
4. Route all writes through governance and WorkCore gateways.
5. Add webhook security and idempotency conformance tests.
6. Add per-extension smoke tests, migration rollback tests, and route authorisation tests.
7. Split Chatbot internally into versioned packages without changing its external marketplace identity.
8. Consolidate provider/model profiles and usage accounting across AIChatPro, Chatbot, and AIAgent.
