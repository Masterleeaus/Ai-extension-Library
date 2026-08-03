# Chatbot & Conversation Runtime

Functions are detected public executable methods; dynamically registered behaviour may add more.

## AI Agent Tool: Chatbot (`AIAgentToolChatbot`) — v1.0

Bridges AIAgent to chatbot and conversation operations through Auto Tool Calling.

- **Shape:** 12 files.
- **Functions:** `execute`, `getCategory`, `getLabel`, `getDescription`, `getIcon`, `getConfigSchema`.
- **Dependencies:** AIAgent, Chatbot, and the MarketingBot/SocialMediaAgent tool bridges.

## Chatbot (`Chatbot`) — v6.9.0 Unified AI Shell

Canonical Titan Zero chatbot and business interaction runtime. It includes customer chat, staff inbox, team chat, channel integrations, PWA/offline sync, generative UI, the five-tier AI runtime, WorkCore bridges, skills/tools, governance, approvals, receipts, rollback, memory, provider profiles, and operational app templates.

- **Shape:** 1,548 files; 60 controllers; 64 services; 56 models; 93 migrations; 201 detected routes; 51 test files.
- **Conversation functions:** conversation/session creation, messages, file upload, support handoff, email collection, page visits, reviews, exports, canned responses, customer identity, tags, and knowledge-base training.
- **Channel functions:** WhatsApp, Messenger, Instagram, Telegram, internal/web chat, provider-neutral outbound messaging, webhook processing, and staff handoff.
- **Team functions:** conversations, memberships, messages, read state, typing state, channels, join/leave/archive, and realtime broadcast authorisation.
- **Offline functions:** `bootstrap`, `push`, `pull`, `acknowledge`, `status`, device registration/revocation, tombstones, cursors, operations, conflicts, and conflict resolution.
- **Titan AI functions:** `execute`, intent routing, five-tier orchestration, chain planning, delegation graph, dynamic worker selection, confidence fallback, worker routes, tool/skill matching, and WorkCore mapping.
- **Governance functions:** risk classification, council review, human approvals, governed tool execution, permissions, receipts, rollback, personas, skills, memory validation/decay, budget controls, and WorkCore fact verification.
- **Generative UI functions:** catalogue, examples, spec validation, repair, normalisation, builder registry, and response composition.
- **Persistence:** 56 created tables spanning chatbot, team chat, sync, booking, ecommerce, tags, skills, model council, AI governance, agents, model/provider profiles, runs, tool runs, usage, idempotency, and events.
- **Canonical decision:** retained instead of `TitanZeroChatbot`; it contains nine additional Titan Train/PWA files and no missing files relative to the duplicate.
- **Review:** nine TODO/FIXME markers; broad scope should be split internally into versioned modules without changing marketplace identity.

## Chatbot Agent (`ChatbotAgent`) — v2.10

Human-agent inbox and conversation management layer with realtime notifications, canned responses, message rewriting, assignment, pinning, closure, and channel-aware conversation controls.

- **Shape:** 30 files; 3 controllers; 3 services; 20 routes.
- **Functions:** `dispatch`, `index`, `store`, `assign`, `notification`, `cannedResponses`, `rewriteMessage`, `closed`, conversation naming, pinning, unread counts, and channel filtering.
- **Dependencies:** Chatbot, customer tags, reviews, Telegram, WhatsApp, Instagram, and Messenger add-ons.
- **Provider:** Ably/Pusher-compatible realtime delivery.

## Chatbot Booking (`ChatbotBooking`) — v1.0.0

Adds booking entry points to the external chatbot and Calendly-aware scheduling integration.

- **Shape:** 11 files; 1 controller; 1 model; 1 route.
- **Function:** `index`.
- **Dependencies:** Chatbot.

## Chatbot Customer Tag (`ChatbotCustomerTag`) — v1.0.0

Customer-tag CRUD and conversation-to-tag assignment.

- **Shape:** 10 files; 1 controller; 1 model; 2 migrations; 5 routes.
- **Functions:** `index`, `store`, `edit`, `update`, `destroy`.
- **Tables:** `ext_chatbot_customer_tags`, `ext_chatbot_conversation_customer_tag`.

## Chatbot Ecommerce (`ChatbotEcommerce`) — v1.0.0

Model-tool-driven cart operations and generative-UI commerce responses inside chatbot sessions.

- **Shape:** 17 files; 2 controllers; 1 service; 1 model; 2 migrations; 5 routes.
- **Functions:** `handleToolCall`, `handleAnthropicToolCall`, `handleGeminiToolCall`, UI variants of each handler, `getToolDefinitions`, `getAnthropicToolDefinitions`, cart add/update/get/checkout.
- **Table:** `ext_chatbot_carts`.
- **Dependencies:** Chatbot and ChatbotBooking.
- **Review:** two TODO/FIXME markers; ecommerce authority should remain outside Chatbot when WorkCore owns orders and payments.

## Chatbot Review (`ChatbotReview`) — v1.0.0

Configurable review requests and review submission hooks.

- **Shape:** 7 files; 1 service.
- **Functions:** `requestReview`, `submitReview`.
- **Dependency:** Chatbot.

## External Voice Chatbot (`ChatbotVoice`) — v2.3

External voice-chatbot agents with embedded frames, voice selection, knowledge-base training, conversations, histories, and provider lifecycle management.

- **Shape:** 57 files; 5 controllers; 1 service; 5 models; 5 migrations; 16 routes.
- **Functions:** `query`, `avatars`, `createAgent`, `updateAgent`, `deleteAgent`, `getVoices`, `addKnowledgebase`, balance checks, training, and frame/session operations.
- **Tables:** `ext_voice_chatbots`, `ext_voicechabot_conversations`, `ext_voicechatbot_histories`, `ext_voicechatbot_trains`.

## Chatbot Voice Call (`ChatbotVoiceCall`) — v1.1

Starts, ends, and records transcripts for voice calls attached to chatbot sessions, with admin settings.

- **Shape:** 12 files; 2 controllers; 5 routes.
- **Functions:** `start`, `end`, `transcript`, `index`, `update`.
- **Provider:** OpenAI voice/realtime services.

> The canonical Chatbot package has bundled tests; the eight add-ons above do not.
