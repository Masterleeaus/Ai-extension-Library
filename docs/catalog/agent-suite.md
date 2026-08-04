# Autonomous Agents & Workflow Automation

Functions are detected public executable methods; dynamically registered behaviour may add more. Cross-extension relationships are static integration signals unless verified by manifests and boot tests. See [Documentation Audit](../DOCUMENTATION-AUDIT.md).

## AI Agent (`AIAgent`) — v1.1

Durable autonomous workflow engine with visual workflow authoring, prompt-to-workflow generation, schedules, webhooks, inbound channel triggers, branching, delayed actions, AI calls, reports, memory, knowledge sources, conversations, copilot guidance, and cross-extension tool execution.

- **Shape:** 169 files; 11 controllers; 7 services; 9 models; 20 migrations; 37 detected routes.
- **Engine functions:** action/connector registration, `dispatch`, `execute`, workflow-run lifecycle, delayed jobs, trigger processing, path evaluation, nested workflows, and report generation.
- **Authoring functions:** `generateFromPrompt`, `availableActions`, `availableModels`, `availableSocialMediaAgents`, `availableSocialMediaPlatforms`, `toggleStatus`, `runs`, `uploadAvatar`.
- **Memory/knowledge functions:** memory CRUD, injection, knowledge-source CRUD, and workflow-copilot chat/history.
- **Channel functions:** `setChannel`, `send`, `registerWebhook`, `handle`, status checks, webhook refresh, inbox messaging, unread counts, and conversation export.
- **Tables:** detected workflow, run, channel, memory, message, conversation, knowledge-source, copilot-message, and avatar tables under `ext_ai_agent_*`.
- **Built-in/detected channels:** MagicAI and Telegram; add-ons extend Gmail, Slack, and WhatsApp.
- **Review:** no bundled tests detected. Webhook authentication, replay prevention, tenant resolution, idempotency, retries, timeout and budget policy, action permissions, vault-backed secrets, receipts, and failure recovery need production hardening. AIAgent knowledge sources should migrate to the dedicated Knowledge Engine rather than become another canonical store.

## AI Agent: Gmail (`AIAgentGmail`) — v1.0

Gmail OAuth connector and AIAgent tools for reading, searching, composing, replying, and managing email threads/messages.

- **Shape:** 33 files; 4 controllers; 1 model; 1 migration; 7 detected routes.
- **Functions:** `execute`, tool metadata/schema methods, connector settings, `messages`, `threads`, OAuth `redirect`/callback, update, and revoke.
- **Table:** `ext_ai_agent_connectors`.
- **Provider:** Google OAuth and Gmail API.
- **Review:** OAuth state validation, tenant/user binding, token encryption, least-privilege scopes, refresh/revoke handling, tool permissions, message redaction, and audit receipts require executable tests.

## AI Agent: Slack Channel (`AIAgentSlackChannel`) — v1.0

Slack Web API channel adapter for inbound events and outbound messages.

- **Shape:** 7 files; 1 controller; 1 detected webhook route.
- **Functions:** `setChannel`, `send`, `registerWebhook`, `handle`.
- **Required integration:** AIAgent channel contract.
- **Review:** Slack signing-secret validation, timestamp tolerance, replay protection, URL verification, retries, deduplication, and rate-limit handling must be tested.

## AI Agent Tool: Marketing Bot (`AIAgentToolMarketingBot`) — v1.0

Auto Tool Calling bridge exposing MarketingBot campaign and conversation operations to AIAgent.

- **Shape:** 11 files.
- **Functions:** `execute`, `getCategory`, `getLabel`, `getDescription`, `getIcon`, `getConfigSchema`.
- **Required integrations:** AIAgent and MarketingBot capability surfaces.
- **Review:** migrate to the shared Tool contract with typed input/output, permissions, budget, idempotency, receipts, structured errors, and explicit side-effect declarations.

## AI Agent Tool: Social Media Agent (`AIAgentToolSocialMediaAgent`) — v1.0

Auto Tool Calling bridge exposing SocialMediaAgent operations to AIAgent.

- **Shape:** 12 files.
- **Functions:** `execute`, `getCategory`, `getLabel`, `getDescription`, `getIcon`, `getConfigSchema`.
- **Required integrations:** AIAgent and SocialMediaAgent capability surfaces.
- **Review:** publishing and account-changing operations require approval policy, platform permissions, idempotency, dry-run support, receipts, and rollback or compensation rules.

## AI Agent: WhatsApp Channel (`AIAgentWhatsappChannel`) — v1.0

Meta Cloud API WhatsApp adapter with webhook verification and inbound/outbound message handling.

- **Shape:** 6 files; 1 controller; 2 detected webhook routes.
- **Functions:** `setChannel`, `send`, `registerWebhook`, `verify`, `handle`.
- **Required integration:** AIAgent channel contract.
- **Review:** verify Meta signatures rather than relying only on challenge-token verification; add replay protection, tenant resolution, message deduplication, media limits, retries, rate-limit handling, and audit logs.

## MarketingBot (`MarketingBot`) — v3.0

Campaign-oriented conversational marketing system with contacts, lists, segments, embeddings, campaigns, WhatsApp/Telegram channels, message analytics, usage limits, templates, scheduled delivery, and human-agent conversation handling.

- **Shape:** 177 files; 21 controllers; 22 services; 16 models; 24 migrations; 28 detected routes.
- **Functions:** CSV/contact import, `parseWhatsapp`, `parseTelegram`, `generateEmbedding`, campaign creation/execution, audience segmentation, conversation assignment, analytics, templates, channel webhooks, and realtime inbox operations.
- **Tables:** campaign analytics, contacts, contact lists/segments, campaign embeddings, campaigns, channels, conversations, messages, templates, usage, and related pivots under `ext_marketing_*`/`ext_contact_*`.
- **Providers:** OpenAI, Telegram, Meta/WhatsApp, and Ably/Pusher-compatible realtime delivery.
- **Review:** no bundled tests detected; five TODO/FIXME markers. Customer identities, consent, suppression lists, campaign permissions, knowledge/embeddings, channel connectors, and durable scheduling need clear authority boundaries rather than being silently duplicated.

## Phone Call Agent (`PhoneCallAgent`) — v1.0

Inbound AI voice-agent system using Twilio and/or ElevenLabs paths, with agent configuration, phone-number import/assignment, voice selection, training data, call history, transcripts, tags, booking tools, simulation, exports, and provider webhooks.

- **Shape:** 83 files; 6 controllers; 5 services; 5 models; 12 migrations; 31 detected routes.
- **Functions:** `createAgent`, `updateAgent`, `deleteAgent`, `syncBookingTools`, `getConversationSignedUrl`, phone-number list/import/assign/release, `configureTwilioNumber`, `buildInboundTwiml`, `completeWithTools`, knowledge-base add/delete, training, call pin/delete/export, and webhook handlers.
- **Tables:** agents, training records, calls, transcripts, call tags, and tag pivots under `ext_phone_call_agent_*`.
- **Detected providers/integrations:** Twilio, ElevenLabs, OpenAI, Anthropic, Gemini, DeepSeek, Calendly, and Cal.com.
- **Review:** no bundled tests detected; two TODO/FIXME markers. Twilio, ElevenLabs, and OpenAI are not interchangeable whole-stack providers. Consolidation must model telephony, transport, conversation, STT, TTS, recording, and storage separately. Training should delegate to the Knowledge Engine; bookings to the Booking Engine; complete calls/transcript segments to the Voice Engine.

## Social Media Agent (`SocialMediaAgent`) — v1.10

AI social-content agent workspace with agents, chat, web research/scraping, post planning, generation, image generation, scheduling, calendar, status polling, and integration with AIChatPro and SocialMedia.

- **Shape:** 117 files; 5 controllers; 9 services; 2 models; 9 migrations; 40 detected routes.
- **Functions:** `getModel`, `generateImageForPost`, `checkStatus`, `setModel`, `scrapeWebsite`, `setMaxPages`, `setTimeout`, agent/post CRUD, chat, research, calendar, generation, publishing handoff, and status retrieval.
- **Detected integrations:** SocialMedia, AIChatPro, Deep Research, File Chat, Canvas, Temp Chat, MultiModel, and OpenAI Realtime Chat.
- **Providers:** OpenAI, Serper, and Fal.
- **Review:** web scraping requires SSRF controls, redirect and private-IP checks, content/size/time limits, and tenant policy. Scheduled publishing should delegate durable execution and retry semantics to AIAgent or a shared workflow runtime.

## Social Media Automation (`SocialMediaAutomation`) — v1.1

Rule-based comment and direct-message automation across social platforms with conditions, actions, replies, execution logs, signatures, pending jobs, and permission checks.

- **Shape:** 64 files; 3 controllers; 3 services; 5 models; 7 migrations; 14 detected routes.
- **Functions:** `platformRequirements`, `checkPermissions`, `getAccountPermissions`, `executionService`, platform payload processors, `verifySignature`, automation CRUD, action/reply configuration, logs, and webhook execution.
- **Tables:** `ext_sm_automations`, `ext_sm_automation_actions`, `ext_sm_automation_replies`, `ext_sm_automation_logs`, `ext_sm_pending_automations`.
- **Required integration:** SocialMedia platform/account capabilities.
- **Providers:** Meta, X/Twitter, LinkedIn, and TikTok.
- **Review:** signature verification is a positive signal but requires provider-specific conformance tests, replay prevention, tenant resolution, idempotency, rate limits, action permissions, and abuse controls.

> No bundled automated tests were detected in these ten selected extension folders; tests may exist elsewhere upstream.
