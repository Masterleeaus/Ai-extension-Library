# Autonomous Agents & Workflow Automation

Functions are detected public executable methods; dynamically registered behaviour may add more.

## AI Agent (`AIAgent`) — v1.1

Durable autonomous workflow engine with visual workflow authoring, prompt-to-workflow generation, schedules, webhooks, inbound channel triggers, branching, delayed actions, AI calls, reports, memory, knowledge sources, conversations, copilot guidance, and cross-extension tool execution.

- **Shape:** 169 files; 11 controllers; 7 services; 9 models; 20 migrations; 37 routes.
- **Engine functions:** action/connector registration, `dispatch`, `execute`, workflow run lifecycle, delayed jobs, trigger processing, path evaluation, nested workflows, and report generation.
- **Authoring functions:** `generateFromPrompt`, `availableActions`, `availableModels`, `availableSocialMediaAgents`, `availableSocialMediaPlatforms`, `toggleStatus`, `runs`, `uploadAvatar`.
- **Memory/knowledge functions:** memory CRUD, injection, knowledge-source CRUD, and workflow copilot chat/history.
- **Channel functions:** `setChannel`, `send`, `registerWebhook`, `handle`, status checks, webhook refresh, inbox messaging, unread counts, and conversation export.
- **Tables:** workflows, workflow runs, channels, memories, messages, conversations, knowledge sources, copilot messages, and avatars under the `ext_ai_agent_*` namespace.
- **Built-in providers:** MagicAI and Telegram; add-ons extend Gmail, Slack, and WhatsApp.
- **Review:** no bundled tests; webhook security, idempotency, permissions, retry policy, timeout/budget controls, and vault-backed secrets need production hardening.

## AI Agent: Gmail (`AIAgentGmail`) — v1.0

Gmail OAuth connector and AIAgent tools for reading, searching, composing, replying, and managing email threads/messages.

- **Shape:** 33 files; 4 controllers; 1 model; 1 migration; 7 routes.
- **Functions:** `execute`, tool metadata/schema functions, connector settings, `messages`, `threads`, OAuth `redirect`/callback, update, and revoke.
- **Table:** `ext_ai_agent_connectors`.
- **Provider:** Google OAuth and Gmail API.

## AI Agent: Slack Channel (`AIAgentSlackChannel`) — v1.0

Slack Web API channel adapter for inbound events and outbound messages.

- **Shape:** 7 files; 1 controller; 1 webhook route.
- **Functions:** `setChannel`, `send`, `registerWebhook`, `handle`.
- **Dependency:** AIAgent.

## AI Agent Tool: Marketing Bot (`AIAgentToolMarketingBot`) — v1.0

Auto Tool Calling bridge exposing MarketingBot campaign and conversation operations to AIAgent.

- **Shape:** 11 files.
- **Functions:** `execute`, `getCategory`, `getLabel`, `getDescription`, `getIcon`, `getConfigSchema`.
- **Dependencies:** AIAgent and MarketingBot.

## AI Agent Tool: Social Media Agent (`AIAgentToolSocialMediaAgent`) — v1.0

Auto Tool Calling bridge exposing SocialMediaAgent operations to AIAgent.

- **Shape:** 12 files.
- **Functions:** `execute`, `getCategory`, `getLabel`, `getDescription`, `getIcon`, `getConfigSchema`.
- **Dependencies:** AIAgent and SocialMediaAgent.

## AI Agent: WhatsApp Channel (`AIAgentWhatsappChannel`) — v1.0

Meta Cloud API WhatsApp adapter with verification and inbound/outbound message handling.

- **Shape:** 6 files; 1 controller; 2 webhook routes.
- **Functions:** `setChannel`, `send`, `registerWebhook`, `verify`, `handle`.
- **Dependency:** AIAgent.

## MarketingBot (`MarketingBot`) — v3.0

Campaign-oriented conversational marketing system with contacts, lists, segments, embeddings, campaigns, WhatsApp/Telegram channels, message analytics, usage limits, templates, scheduled delivery, and human-agent conversation handling.

- **Shape:** 177 files; 21 controllers; 22 services; 16 models; 24 migrations; 28 routes.
- **Functions:** CSV/contact import, `parseWhatsapp`, `parseTelegram`, `generateEmbedding`, campaign creation/execution, audience segmentation, conversation assignment, analytics, templates, channel webhooks, and realtime inbox operations.
- **Tables:** campaign analytics, contacts, contact lists/segments, campaign embeddings, campaigns, channels, conversations, messages, templates, usage, and related pivots under `ext_marketing_*`/`ext_contact_*`.
- **Providers:** OpenAI, Telegram, Meta/WhatsApp, and Ably/Pusher-compatible realtime delivery.
- **Review:** no bundled tests; five TODO/FIXME markers.

## Phone Call Agent (`PhoneCallAgent`) — v1.0

Inbound AI voice-agent system using Twilio or ElevenLabs, with agent configuration, phone-number import/assignment, voice selection, training data, call history, transcripts, tags, booking tools, simulation, exports, and provider webhooks.

- **Shape:** 83 files; 6 controllers; 5 services; 5 models; 12 migrations; 31 routes.
- **Functions:** `createAgent`, `updateAgent`, `deleteAgent`, `syncBookingTools`, `getConversationSignedUrl`, phone-number list/import/assign/release, `configureTwilioNumber`, `buildInboundTwiml`, `completeWithTools`, knowledge-base add/delete, training, call pin/delete/export, and webhook handlers.
- **Tables:** agents, training records, calls, transcripts, call tags, and tag pivots under `ext_phone_call_agent_*`.
- **Providers:** Twilio, ElevenLabs, OpenAI, Anthropic, Gemini, DeepSeek, Calendly, and Cal.com.
- **Review:** no bundled tests; two TODO/FIXME markers.

## Social Media Agent (`SocialMediaAgent`) — v1.10

AI social-content agent workspace with agents, chat, web research/scraping, post planning, generation, image generation, scheduling, calendar, status polling, and integration with AIChatPro and SocialMedia.

- **Shape:** 117 files; 5 controllers; 9 services; 2 models; 9 migrations; 40 routes.
- **Functions:** `getModel`, `generateImageForPost`, `checkStatus`, `setModel`, `scrapeWebsite`, `setMaxPages`, `setTimeout`, agent/post CRUD, chat, research, calendar, generation, publishing handoff, and status retrieval.
- **Tables:** `ext_social_media_agents`, `ext_social_media_agent_posts`.
- **Dependencies:** SocialMedia, AIChatPro, Deep Research, File Chat, Canvas, Temp Chat, MultiModel, and OpenAI Realtime Chat.
- **Providers:** OpenAI, Serper, and Fal.

## Social Media Automation (`SocialMediaAutomation`) — v1.1

Rule-based comment and direct-message automation across social platforms with conditions, actions, replies, execution logs, signatures, pending jobs, and permission checks.

- **Shape:** 64 files; 3 controllers; 3 services; 5 models; 7 migrations; 14 routes.
- **Functions:** `platformRequirements`, `checkPermissions`, `getAccountPermissions`, `executionService`, platform payload processors, `verifySignature`, automation CRUD, action/reply configuration, logs, and webhook execution.
- **Tables:** `ext_sm_automations`, `ext_sm_automation_actions`, `ext_sm_automation_replies`, `ext_sm_automation_logs`, `ext_sm_pending_automations`.
- **Dependency:** SocialMedia.
- **Providers:** Meta, X/Twitter, LinkedIn, and TikTok.

> No bundled automated tests were detected in these ten extensions.
