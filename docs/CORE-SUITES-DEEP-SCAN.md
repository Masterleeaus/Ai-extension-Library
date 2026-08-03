# Core AI Suites — Deep Scan

## Evidence status

This report is based on static inspection of the materialised extension source. Counts, functions, routes, migrations, tables, providers, and integration relationships are detected evidence—not proof that every service provider boots or every path is reachable. PHP-file and Blade-view counts overlap because Blade templates are PHP files. Runtime architecture tests remain required.

## Executive finding

The archive contains three distinct but increasingly overlapping AI layers:

1. **AIChatPro** is the internal human-facing AI workspace and extensible tool-enabled chat surface.
2. **Chatbot** is the external/customer conversation platform, offline PWA runtime, staff inbox, team communication layer, and—after Titan Zero convergence—a broad five-tier AI orchestration and governance runtime.
3. **AIAgent** is the autonomous workflow engine for triggers, actions, channels, memory, knowledge, and cross-extension tool execution.

They are not interchangeable. The cleanest target architecture is to preserve all three while assigning strict authority boundaries: AIChatPro owns interactive knowledge work, Chatbot owns customer conversation/device/channel runtime, and AIAgent owns durable autonomous workflow execution. WorkCore remains authoritative for operational business records.

---

## AIChatPro 3.7

### Measured structure

- 47 total files; 42 PHP files; 14 Blade/view files; 7 migrations; 14 detected routes.
- PHP and view counts are overlapping facets, not additive totals.
- One connector table plus detected chat/message schema extensions.
- No bundled automated tests detected in this extension folder.

### Main capabilities

- Internal chat workspace with streamed generation and new-chat creation.
- Message-level follow-up suggestions generated asynchronously.
- Pluggable connector registry with provider-neutral tool definitions for OpenAI, Anthropic, and Gemini formats.
- Connector plan gating, user ownership policies, selected-access scopes, pause/resume, credential lifecycle, token invalidation events, and user notifications.
- Tool dispatch and function-call routing through `ConnectorToolService` and `AiChatProService`.
- Conditional image-edit streaming through OpenAI or Fal when compatible image capability is installed.
- Admin customisation and typography settings.

### Add-on ecosystem

The following are detected add-ons or integrations around AIChatPro. This list must not be interpreted as AIChatPro hard-depending on every package:

- **Deep Research:** OpenAI/Gemini research jobs, persisted research sessions/results, and source-rich research UI.
- **File Chat:** file-aware conversation attachment and analysis surface.
- **Folders:** conversation organisation and folder persistence.
- **Skills:** reusable public/private AI skills, assignment, import/export, and provider-neutral tool schemas.
- **Entity Highlight:** clickable response entities with detail drawer.
- **Highlight to Ask:** selected-text follow-up prompting.
- **Smart Image:** image search, message-linked images, tool definitions, and Perplexity/Serper integrations.
- **Canvas:** editable long-form/code canvas attached to chat.
- **Temporary Chat:** ephemeral/non-persistent sessions.
- **Realtime Chat / Voice:** OpenAI Realtime and ElevenLabs voice surfaces.
- **Chat Settings, Sharing, Focus Mode:** configuration, sharing, and presentation controls.

### Important detected functions

- Connector registration and discovery: `register`, `get`, `enabled`, `findByFunctionName`.
- Provider tool conversion: `openAiTools`, `anthropicTools`, `geminiTools`.
- Tool execution: `handle`, `callFunction`, `generateImage`.
- Connector lifecycle: `destroy`, `preConsent`, `accessShow`, `accessUpdate`, `togglePause`, `revokeToken`.
- Chat delivery: `index`, `getMessageSuggestions`, `buildStreamedOutput`.

### Assessment

AIChatPro is the correct interactive workspace. Its connector abstraction is one of the strongest reusable components, but it should not become the autonomous scheduler or customer-channel authority. The major gaps are absent bundled tests, an unfinished uninstall path, display-name/marketplace checks instead of stable capability contracts, and insufficient proof that connector credentials are consistently vault-backed.

---

## AIAgent 1.1

### Measured structure

- 169 total files; 147 PHP files; 53 Blade/view files; 20 migrations; 37 detected routes.
- PHP and view counts overlap.
- Nine primary detected persistence areas: workflows, runs, channels, memories, messages, conversations, knowledge sources, copilot messages, and avatars.
- No bundled automated tests detected in this extension folder.

### Main capabilities

- Visual workflow builder with prompt-to-workflow generation.
- Trigger types: schedule, generic webhook, and inbound channel message.
- Action types: AI call, send message, memory, path/branch, report generation, and nested workflow execution.
- Workflow engine, action registry, action dispatcher, delayed actions, status/runs/logs, and trigger-processing console commands.
- Multi-channel conversation inbox and unread-message handling.
- Built-in MagicAI and Telegram connector paths; detected add-ons for WhatsApp, Slack, and Gmail.
- Knowledge sources and memory injection into AI calls.
- Workflow copilot with history and model selection.
- Cross-extension Auto Tool Calling bridges for Chatbot, MarketingBot, and SocialMediaAgent.
- Plan limits, policies, avatar presets, conversation export, and channel health/webhook refresh.

### Important detected functions

- Workflow execution: `execute`, `dispatch`, `run`, `process`, `handle` across workflow, dispatcher, trigger, and action classes.
- Registry and extension: action and connector `register`, `resolve`, and `all` methods.
- Memory: repository CRUD, `inject`, and memory action execution.
- Tool calling: schema construction, model call, function execution, and result continuation.
- Workflow authoring: `generateFromPrompt`, `availableActions`, `availableModels`, `toggleStatus`, `runs`, and avatar upload.
- Channel operations: create/update, webhook refresh, status checks, inbound handling, and outbound formatting.

### Assessment

AIAgent is the correct durable automation engine. Its workflow/action/trigger abstractions are more valuable than its UI. Production gaps include webhook authentication, replay prevention, idempotency, retry and timeout policy, budget controls, action-level permissions, secret references, execution receipts, and tests. Chatbot Tier-3 agents should delegate durable, delayed, or retryable work to AIAgent rather than duplicate workflow execution.

---

## Chatbot 6.9.0 Unified AI Shell

### Measured structure

- 1,548 total files; 1,106 PHP files; 158 Blade/view files; 93 migrations; 201 detected routes; 51 detected test files.
- PHP and view counts overlap.
- 56 detected created tables spanning chatbot, sync, team chat, compatibility booking/ecommerce/tagging, skills, model council, governance, provider profiles, executions, usage, and events.
- The canonical version contains nine Titan Train/PWA files missing from `TitanZeroChatbot`.

### Conversation and channel runtime

- Customer-facing embedded chatbot and session/conversation APIs.
- Knowledge training paths for text, files, URLs, PDFs, spreadsheets, and Q&A.
- Conversation history, file upload, support handoff, review/feedback, email collection, page-visit analytics, export, and canned responses.
- Staff inbox with customer identity, tags, labels, realtime settings, and multi-channel management.
- Team chat with membership, messages, read state, typing, channels, archive/leave, and broadcast authorisation.
- Provider-neutral messaging paths for WhatsApp, Messenger, Instagram, Telegram, internal chat, and web chat.

### Offline/device runtime

- PWA shell and service worker.
- Device registration and revocation.
- Bootstrap/push/pull/acknowledge sync protocol.
- Change log, cursors, sessions, operations, acknowledgements, tombstones, conflicts, and conflict resolution.
- Detected sync observers on conversations, histories, and customers.

### Titan AI runtime

- Unified runtime entrypoint and intent gateway.
- Five-tier orchestration, chain planning, delegation graph, dynamic worker selection, confidence fallback, route registry, and permission guard.
- Tier-1 strategic/functional managers.
- Tier-2 general assistants and field-service vertical specialists.
- Tier-3 atomic agents/actions with capability catalogue and WorkCore tool mapping.
- Shared skills and field-service tool registries with matching APIs.
- Provider/model profiles, runs, tool runs, usage ledger, versioned agents, policy versions, and action-idempotency structures.

### Governance

- Risk classification and model-council review paths.
- Human approval queue, approval execution, receipts, rollback, and conflict structures.
- Governed tools, permissions, personas, skills, evaluations, and persistent-memory validation/decay.
- WorkCore gateway and fact-verification paths.
- Model budget guard and structured inference client.

### Generative UI and app shell

- Generative UI builder registry, normaliser, validator, repair, examples, and response composer.
- Template-aware operational app catalogue and manifests.
- WorkCore app bridge and mappings for Titan Analytics, Dispatch, Front Desk, Go, Hub, Locker, Marketing, Money, Social, and Teams.
- Titan Train workspace and PWA module present only in the canonical folder.

### Assessment

Chatbot has evolved beyond a conventional chatbot extension into a business-interaction platform. Its capability is substantial, but so is its concentration risk: compatibility runtimes, broad namespace/autoload maps, 93 migrations, mixed customer/UI/channel/AI/governance responsibilities, and duplicated execution concepts. The code attempts to avoid WorkCore namespace shadowing through a feature flag and host-class check; that boundary should become a tested architectural rule. Preserve the canonical external package identity while splitting the five-tier runtime, governance, channel core, sync engine, and app shell into internally versioned modules.

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
| Operational records, CRM, jobs, invoices, inventory, staff, bookings, payments, and orders | WorkCore |
| Tool permissions, approval, receipts, and rollback | Shared Titan AI governance layer |
| Provider/model routing and usage accounting | Shared provider registry consumed by all three |
| Canonical documents, chunks, embeddings, and retrieval | Dedicated Knowledge Engine—not UnifiedMemory |
| Canonical calls, transcripts, and recordings | Dedicated Voice Engine—not UnifiedMemory |

## Consolidation priorities

1. Introduce a shared, tenant-aware capability/tool execution contract for AIChatPro connectors, Chatbot tools, and AIAgent actions.
2. Keep tools, skills, agents, workflows, connectors, and providers as distinct registry types.
3. Make operational writes pass through WorkCore and governance gateways.
4. Delegate durable work from Chatbot Tier-3 agents to AIAgent workflows.
5. Replace extension display-name checks with stable package IDs, capability discovery, and semantic version constraints.
6. Add cross-suite integration tests for chat → tool → approval → WorkCore write → receipt → sync.
7. Add route-collision, service-provider boot, migration rollback, webhook-security, idempotency, and secret-redaction tests.
