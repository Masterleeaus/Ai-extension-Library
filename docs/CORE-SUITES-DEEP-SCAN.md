# Core AI Suites — Deep Scan

## Executive finding

The archive contains three distinct but increasingly overlapping AI layers:

1. **AIChatPro** is the internal human-facing AI workspace and extensible tool-enabled chat surface.
2. **Chatbot** is the external/customer conversation platform, offline PWA runtime, staff inbox, team communication layer, and—after Titan Zero convergence—a full five-tier AI orchestration and governance runtime.
3. **AIAgent** is the autonomous workflow engine for triggers, actions, channels, memory, knowledge, and cross-extension tool execution.

They are not interchangeable. The cleanest target architecture is to preserve all three while giving each one a strict authority boundary: AIChatPro owns interactive knowledge work, Chatbot owns conversation/device/channel runtime, and AIAgent owns durable autonomous workflow execution. WorkCore remains authoritative for operational business records.

---

## AIChatPro 3.7

### Measured structure

- 47 files; 42 PHP files; 14 view files; 7 migrations; 14 detected routes.
- One connector table plus message/chat schema extensions.
- No bundled automated tests.

### Main capabilities

- Full internal chat workspace with streamed generation and new-chat creation.
- Message-level follow-up suggestions generated asynchronously.
- Pluggable connector registry with provider-neutral tool definitions for OpenAI, Anthropic, and Gemini.
- Connector plan gating, user ownership policies, selected-access scopes, pause/resume, credential storage, token invalidation events, and user notifications.
- Tool dispatch and function-call routing through `ConnectorToolService` and `AiChatProService`.
- Conditional image-edit streaming through OpenAI or Fal when the image-chat extension is installed.
- Admin customisation and typography settings.

### Add-on suite

- **Deep Research:** OpenAI/Gemini research jobs, persisted research sessions/results, source-rich research UI.
- **File Chat:** file-aware conversation attachment and retrieval surface.
- **Folders:** conversation organisation and folder persistence.
- **Skills:** reusable, user-defined AI skills with versioned records and chat injection.
- **Entity Highlight:** clickable response entities with detail drawer.
- **Highlight to Ask:** selected-text follow-up prompting.
- **Smart Image:** image search, message-linked images, Gemini/OpenAI tool definitions, Perplexity/Serper search integration.
- **Canvas:** editable long-form/code canvas attached to chat.
- **Temporary Chat:** non-persistent/ephemeral sessions.
- **Realtime Chat / Voice:** OpenAI realtime and ElevenLabs voice surfaces.
- **Chat Settings, Sharing, Focus Mode:** presentation, sharing, and distraction-reduction controls.

### Important functions

- Connector registration and discovery: `register`, `get`, `enabled`, `findByFunctionName`.
- Provider tool conversion: `openAiTools`, `anthropicTools`, `geminiTools`.
- Tool execution: `handle`, `callFunction`, `generateImage`.
- Connector lifecycle: `destroy`, `preConsent`, `accessShow`, `accessUpdate`, `togglePause`, `revokeToken`.
- Chat delivery: `index`, `getMessageSuggestions`, `buildStreamedOutput`.

### Assessment

AIChatPro is a good modular user workspace. Its connector abstraction is the most reusable component. It should not become the autonomous scheduler or the customer-channel authority. Its largest weaknesses are absent tests, an unimplemented uninstall path, and add-on coupling through marketplace-name checks rather than a formal capability contract.

---

## AIAgent 1.1

### Measured structure

- 169 files; 147 PHP files; 53 views; 20 migrations; 37 detected routes.
- Nine primary persistence tables for workflows, runs, channels, memories, messages, conversations, knowledge sources, copilot messages, and avatars.
- No bundled automated tests.

### Main capabilities

- Visual workflow builder with prompt-to-workflow generation.
- Trigger types: schedule, generic webhook, and inbound channel message.
- Action types: AI call, send message, memory, path/branch, report generation, and nested workflow execution.
- Workflow engine, action registry, action dispatcher, delayed actions, status/runs/logs, and trigger-processing console commands.
- Multi-channel conversation inbox and unread-message handling.
- Built-in MagicAI and Telegram connectors; add-ons for WhatsApp, Slack, and Gmail.
- Knowledge sources and memory injection into AI calls.
- Workflow copilot with history and model selection.
- Cross-extension Auto Tool Calling for Chatbot, MarketingBot, and SocialMediaAgent.
- Plan limits, policies, avatar presets, conversation export, and channel health/webhook refresh.

### Important functions

- Workflow execution: `execute`, `dispatch`, `run`, `process`, `handle` across `WorkflowEngine`, `ActionDispatcher`, triggers, and actions.
- Registry and extension: action and connector `register`, `resolve`, and `all` methods.
- Memory: repository CRUD, `inject`, and memory action execution.
- Tool calling: tool schema construction, model call, function execution, and result continuation.
- Workflow authoring: `generateFromPrompt`, `availableActions`, `availableModels`, `toggleStatus`, `runs`, avatar upload.
- Channel operations: create/update, refresh webhook, status checks, inbound webhook handling, outbound message formatting.

### Assessment

AIAgent is the correct durable automation engine. Its workflow/action/trigger abstractions are more valuable than its UI. The main engineering gap is safety hardening: webhook authentication, idempotency, retries, timeout/budget policy, action-level permissions, secret references, and tests should be formalised before broad production use. Where Chatbot now contains Tier-3 agents, those should delegate durable or delayed work to AIAgent rather than duplicate workflow execution.

---

## Chatbot 6.9.0 Unified AI Shell

### Measured structure

- 1,548 files; 1,106 PHP files; 158 views; 93 migrations; 201 detected routes; 51 test files.
- 56 created tables spanning chatbot, sync, team chat, booking/ecommerce/tagging, skills, model council, AI governance, provider profiles, executions, usage, and domain events.
- Canonical version includes nine Titan Train/PWA files missing from `TitanZeroChatbot`.

### Conversation and channel runtime

- Customer-facing embedded chatbot and session/conversation APIs.
- Knowledge-base training from text, files, URLs, PDFs, spreadsheets, and Q&A.
- Conversation history, file upload, support handoff, review/feedback, email collection, page-visit analytics, export, and canned responses.
- Staff inbox with customer identity, tags, labels, realtime settings, and multi-channel conversation management.
- Team chat with membership, messages, read state, typing, channels, archive/leave, and broadcast authorisation.
- Provider-neutral messaging core for WhatsApp, Messenger, Instagram, Telegram, internal chat, and web chat.

### Offline/device runtime

- PWA shell and service worker.
- Device registration and revocation.
- Bootstrap/push/pull/acknowledge sync protocol.
- Change log, cursors, sessions, operations, acknowledgements, tombstones, conflicts, and conflict resolution.
- Sync observers on conversations, histories, and customers.

### Titan AI runtime

- Unified runtime entrypoint and intent gateway.
- Five-tier orchestration, assistant chain planning, delegation graph, dynamic worker selection, confidence fallback, route registry, and permission guard.
- Tier-1 strategic/functional managers.
- Tier-2 general assistants plus field-service vertical specialists for residential, commercial, pool, pressure washing, and car services.
- Tier-3 atomic agents/actions with capability catalogue and WorkCore tool mapping.
- Shared skills and field-service tool registries with match APIs.
- Provider/model profiles, runs, tool runs, usage ledger, versioned agents, policy versions, and action idempotency.

### Governance

- Risk classification and model council review.
- Human approval queue, approval execution, receipts, rollback, and conflicts.
- Governed tools, permissions, personas, skills, evaluations, and persistent memory with validation/decay.
- WorkCore gateway and fact verification.
- Model budget guard and structured inference client.

### Generative UI and app shell

- Generative UI builder registry, normaliser, validator, repair, examples, and response composer.
- Template-aware operational app catalogue and manifests.
- WorkCore app bridge and mappings for Titan Analytics, Dispatch, Front Desk, Go, Hub, Locker, Marketing, Money, Social, and Teams.
- Titan Train workspace and PWA module present only in the canonical folder.

### Assessment

Chatbot has evolved far beyond a chatbot extension into a business interaction operating system. Its capability is substantial, but so is its consolidation risk: embedded compatibility runtimes, broad namespace autoload maps, 93 migrations, mixed customer/UI/channel/AI/governance responsibilities, and duplicated execution concepts. The code already attempts to prevent WorkCore namespace shadowing with a feature flag and host-class check; that boundary should become a hard architectural rule. Preserve this canonical folder, but gradually split the five-tier runtime, governance, channel core, sync engine, and app shell into internally versioned modules.

---

## Recommended authority map

| Concern | Authoritative system |
|---|---|
| Human interactive AI workspace | AIChatPro |
| External/customer conversations | Chatbot |
| Device/PWA/offline synchronisation | Chatbot sync runtime |
| Staff inbox and team conversation | Chatbot |
| Immediate conversational delegation | Chatbot Titan AI runtime |
| Durable schedules, retries, delayed jobs, and cross-channel workflows | AIAgent |
| Operational records, CRM, jobs, invoices, inventory, staff, bookings | WorkCore |
| Tool permissions, approval, receipts, rollback | Shared Titan AI governance layer |
| Provider/model routing | Shared provider registry, consumed by all three |

## Consolidation priorities

1. Extract a shared capability/tool contract used by AIChatPro connectors, Chatbot tools, and AIAgent actions.
2. Make all operational writes pass through WorkCore and governance gateways.
3. Delegate durable work from Chatbot Tier-3 agents to AIAgent workflows.
4. Replace extension-name checks with capability discovery and semantic version constraints.
5. Add cross-suite integration tests for chat → tool → approval → WorkCore write → receipt → sync.
