# Deep Scan Findings

## Scope

This report converts the existing static scan of all 78 selected extensions into upgrade-oriented findings. It focuses on duplicated infrastructure, authority conflicts, security exposure, migration risk, and reusable engines.

## Ecosystem map

### AIChatPro and workspace add-ons

AIChatPro is the internal interactive AI workspace. Its strongest reusable component is the connector registry and provider-neutral tool conversion layer. Add-ons extend deep research, file chat, folders, skills, entity highlighting, image search, canvas, temporary sessions, realtime voice, sharing, and focus modes.

Primary upgrade concern: add-ons discover one another through marketplace names and feature checks rather than formal capabilities and version constraints.

### Chatbot and conversation runtime

The canonical Chatbot package is a platform-scale extension with 1,548 files, 93 migrations, 201 detected routes, and 51 tests. It includes:

- customer chat and session APIs;
- knowledge training from text, files, URLs, PDFs, spreadsheets, and Q&A;
- staff inbox, customer identity, tags, labels, realtime handoff, and team chat;
- WhatsApp, Messenger, Instagram, Telegram, internal, and web channels;
- PWA/device registration, offline sync, changes, cursors, tombstones, acknowledgements, and conflict resolution;
- five-tier AI orchestration, skills, tools, vertical specialists, and WorkCore mapping;
- risk classification, model council, human approval, receipts, rollback, permissions, memory, model profiles, budgets, and evaluation;
- generative UI and template-aware app bridges.

Primary upgrade concern: the package contains several bounded domains behind one marketplace identity. Internal modularisation is required, but its external extension identity and installation paths must remain stable until host compatibility is proven.

### AIAgent and automation add-ons

AIAgent is the durable workflow authority. It owns schedules, webhooks, inbound channel triggers, branching, delayed actions, nested workflows, reports, memory, knowledge sources, channels, conversations, and cross-extension Auto Tool Calling.

Its add-ons add Gmail, Slack, WhatsApp, and bridges to Chatbot, MarketingBot, and SocialMediaAgent.

Primary upgrade concern: no bundled automated tests were detected. Webhook authentication, replay prevention, idempotency, action permissions, retry classification, timeout policy, budget control, and vault-backed secrets need conformance tests before broad autonomous use.

### Voice extensions

Three overlapping voice surfaces exist:

- `PhoneCallAgent` — inbound telephone agents using Twilio or ElevenLabs, with training, phone numbers, calls, transcripts, tags, booking tools, simulation, exports, and webhooks.
- `ChatbotVoice` — external embedded voice chatbots with voice selection, knowledge training, conversations, histories, frames, and provider lifecycle.
- `ChatbotVoiceCall` — OpenAI realtime voice calls attached to chatbot sessions.

Primary upgrade concern: voice transport, realtime model sessions, speech recognition, synthesis, recordings, transcripts, provider attempts, usage, and post-call analysis are conflated or duplicated. Twilio, ElevenLabs, and OpenAI are not interchangeable providers; consolidation must occur by capability.

## Confirmed duplication clusters

### 1. Knowledge ingestion and retrieval

Repeated responsibilities appear across Chatbot training, ChatSetting, ChatbotVoice, PhoneCallAgent, AIAgent knowledge sources, MarketingBot embeddings, File Chat, and other add-ons:

- text/file/URL submission;
- document parsing;
- chunking;
- embedding generation;
- source deletion;
- prompt injection or retrieval;
- training status UI;
- provider selection and token accounting.

Required response: a dedicated Knowledge Engine with canonical `knowledge_*` storage, async jobs, versioning, assignments, hybrid retrieval, citations, and compatibility adapters. UnifiedMemory may store derived memories and source references only.

### 2. Tool and action execution

Independent tool systems exist in:

- AIChatPro connector tools;
- AIAgent actions and Auto Tool Calling;
- Chatbot field-service tools and Tier-3 agents;
- PhoneCallAgent booking and model tools;
- MarketingBot tools;
- SocialMediaAgent tools;
- bridge extensions exposing one extension to another.

Required response: shared definitions for tool identity, typed input/output schemas, execution context, permissions, budgets, retries, idempotency, side effects, receipts, citations, and error classification. Skills, agents, workflows, connectors, and providers remain separate registry types.

### 3. Provider and credential management

The repository references OpenAI, Anthropic, Gemini, DeepSeek, xAI, OpenRouter, Perplexity, Serper, Meta, Telegram, Slack, Gmail, Twilio, ElevenLabs, Calendly/Cal.com, Azure, and multiple media providers.

Required response: encrypted credential vault references, tenant-scoped provider profiles, health checks, capability declarations, rate-limit telemetry, usage ledgers, and explicit provider fallback policy. Secrets must never be copied into events, logs, tool results, or extension configuration snapshots.

### 4. Channel and webhook handling

Inbound webhooks and outbound messaging exist across Chatbot channels, AIAgent channels, PhoneCallAgent, MarketingBot, and SocialMediaAutomation.

Required response: shared webhook verification and delivery infrastructure covering signature verification, tenant resolution, timestamp and replay checks, idempotency, normalized inbound events, retry/dead-letter handling, audit receipts, and rate limits. Channel-specific payload parsing remains in adapters.

### 5. Customer identity and interaction history

Customer information appears in chatbot conversations, tags, voice callers, Gmail senders, WhatsApp identities, booking attendees, ecommerce customers, campaign contacts, and social identities.

Required response: a dedicated customer identity domain before cross-channel personalization. It must preserve separate external identities, consent, merge candidates, confidence, provenance, and reversible merges.

### 6. Booking and commerce

Booking is split across ChatbotBooking, PhoneCallAgent tools, Calendly, and Cal.com. Ecommerce appears in ChatbotEcommerce and provider-specific handlers while WorkCore is intended to own operational records.

Required response: separate Booking and Commerce engines with provider adapters. UnifiedMemory may store contextual facts such as a preferred appointment time, but bookings, carts, orders, payment states, and provider mappings remain canonical domain records.

## Strong reusable engines

1. AIChatPro connector registry and provider tool conversion.
2. AIAgent workflow, trigger, action, channel, and delayed execution abstractions.
3. Chatbot offline sync protocol and conflict handling.
4. Chatbot governance: risk, approval, receipts, rollback, permissions, memory controls, and model budgets.
5. Chatbot five-tier intent routing, delegation, confidence fallback, and vertical specialists.
6. Model Council multi-model adjudication and synthesis.
7. Creative spatial engines for annotations, masks, editable templates, and timeline composition.
8. Chatbot staff inbox, customer tags, realtime handoff, and provider-neutral channel messaging.

The upgrade programme should extract contracts and internal modules around these engines rather than rewrite them from scratch.

## Highest risks

### Critical

- Cross-tenant leakage through missing tenant filters in queues, retrieval, events, or webhook resolution.
- Unsigned or replayable inbound webhooks triggering autonomous actions.
- Operational writes bypassing WorkCore and governance receipts.
- Secrets copied into extension settings, logs, queue payloads, or events.
- Tool calls executing without explicit action-level permissions, budgets, or idempotency.

### High

- No bundled tests across 77 of 78 selected extensions.
- Duplicate knowledge and voice records becoming inconsistent during migration.
- Extension-name coupling and inconsistent slugs preventing reliable dependency resolution.
- Chatbot namespace and migration collisions with host WorkCore classes.
- Long-running ingestion or media jobs without cancellation, heartbeat, bounded resources, or stale-job recovery.

### Medium

- Duplicate usage accounting and provider fallback rules.
- Incompatible tool schemas across OpenAI, Anthropic, and Gemini pathways.
- Conversation, call, and workflow histories using incompatible retention policies.
- UI progress indicators not reflecting durable backend state.

## Upgrade principles

- Preserve extension marketplace identities while moving ownership into shared internal engines.
- Introduce typed commands and results rather than generic arrays.
- Put `tenant_id` on every authoritative domain record and event.
- Keep large canonical source content in private object storage with immutable version records.
- Use events containing identifiers and compact metadata, not Eloquent models or full documents.
- Migrate incrementally with feature flags, backfill, shadow reads, quality comparison, reversible cutover, and time-bounded dual writes where necessary.
- Build conformance tests for every extension that implements shared contracts.
- Treat the canonical Chatbot package as an internally modular platform even while distributed as one extension.

## Immediate conclusion

The repository does not need another broad rewrite. It needs a staged consolidation programme that first standardizes security, tenancy, execution contracts, and observability; then moves duplicated capabilities into bounded engines while retaining legacy routes and UI adapters.
