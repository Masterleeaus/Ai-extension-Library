# Chatbot & Conversation Runtime

Functions are detected public executable methods; dynamically registered behaviour may add more. Cross-extension relationships are integration signals unless a hard prerequisite is confirmed by manifest constraints and boot tests. See [Documentation Audit](../DOCUMENTATION-AUDIT.md).

## AI Agent Tool: Chatbot (`AIAgentToolChatbot`) — v1.0

Bridge that exposes chatbot and conversation operations to AIAgent Auto Tool Calling.

- **Shape:** 12 files.
- **Functions:** `execute`, `getCategory`, `getLabel`, `getDescription`, `getIcon`, `getConfigSchema`.
- **Required integration:** AIAgent and Chatbot capability surfaces; references to MarketingBot/SocialMediaAgent bridges reflect a family of tool adapters, not proof that those packages are hard prerequisites.
- **Review:** should migrate to one shared tenant-aware tool contract with validated schemas, permissions, idempotency, budgets, receipts, and structured errors.

## Chatbot (`Chatbot`) — v6.9.0 Unified AI Shell

Canonical Titan Zero chatbot and business-interaction runtime. It includes customer chat, staff inbox, team chat, channel integrations, PWA/offline sync, generative UI, five-tier AI, WorkCore bridges, skills/tools, governance, approvals, receipts, rollback, memory, provider profiles, and operational app templates.

- **Shape:** 1,548 files; 60 controllers; 64 services; 56 models; 93 migrations; 201 detected routes; 51 detected test files.
- **Conversation functions:** session and conversation creation, messages, file upload, support handoff, email collection, page visits, reviews, exports, canned responses, customer identity, tags, and knowledge training.
- **Channel functions:** WhatsApp, Messenger, Instagram, Telegram, internal/web chat, provider-neutral outbound messaging, webhook processing, and staff handoff.
- **Team functions:** conversations, memberships, messages, read state, typing state, channels, join/leave/archive, and realtime broadcast authorisation.
- **Offline functions:** `bootstrap`, `push`, `pull`, `acknowledge`, `status`, device registration/revocation, tombstones, cursors, operations, conflicts, and conflict resolution.
- **Titan AI functions:** `execute`, intent routing, five-tier orchestration, chain planning, delegation graph, dynamic worker selection, confidence fallback, worker routes, tool/skill matching, and WorkCore mapping.
- **Governance functions:** risk classification, council review, human approvals, governed execution, permissions, receipts, rollback, personas, skills, memory validation/decay, budget controls, and WorkCore fact verification.
- **Generative UI functions:** catalogue, examples, specification validation, repair, normalisation, builder registry, and response composition.
- **Persistence:** 56 detected created tables spanning chatbot, team chat, sync, compatibility booking/ecommerce/tagging, skills, model council, governance, agents, provider profiles, runs, tool runs, usage, idempotency, and events.
- **Canonical decision:** retained instead of `TitanZeroChatbot`; it contains nine additional Titan Train/PWA files and no files missing relative to the duplicate in the scan.
- **Review:** broad scope should be split internally into versioned modules without changing its marketplace identity. Operational records must remain authoritative in WorkCore. Canonical knowledge, voice, booking, commerce, customer identity, and credentials require dedicated bounded storage rather than a generic memory table.

## Chatbot Agent (`ChatbotAgent`) — v2.10

Human-agent inbox and conversation-management layer with realtime notifications, canned responses, message rewriting, assignment, pinning, closure, and channel-aware controls.

- **Shape:** 30 files; 3 controllers; 3 services; 20 detected routes.
- **Functions:** `dispatch`, `index`, `store`, `assign`, `notification`, `cannedResponses`, `rewriteMessage`, `closed`, conversation naming, pinning, unread counts, and channel filtering.
- **Detected optional integrations:** customer tags, reviews, Telegram, WhatsApp, Instagram, Messenger, and the Chatbot conversation runtime.
- **Provider:** Ably/Pusher-compatible realtime delivery.
- **Review:** relationship direction and graceful degradation must be tested with optional add-ons disabled.

## Chatbot Booking (`ChatbotBooking`) — v1.0.0

Adds booking entry points to the external chatbot and a Calendly-aware scheduling surface.

- **Shape:** 11 files; 1 controller; 1 model; 1 detected route.
- **Function:** `index`.
- **Required integration:** Chatbot rendering/runtime.
- **Review:** current scope appears closer to an embed/configuration add-on than an authoritative booking engine. Availability, create, cancel, reschedule, provider sync, customer mapping, and audit records belong in a dedicated Booking Engine or WorkCore integration.

## Chatbot Customer Tag (`ChatbotCustomerTag`) — v1.0.0

Customer-tag CRUD and conversation-to-tag assignment.

- **Shape:** 10 files; 1 controller; 1 model; 2 migrations; 5 detected routes.
- **Functions:** `index`, `store`, `edit`, `update`, `destroy`.
- **Tables:** `ext_chatbot_customer_tags`, `ext_chatbot_conversation_customer_tag`.
- **Review:** tags should eventually attach to a shared customer identity rather than remaining channel-specific conversation metadata.

## Chatbot Ecommerce (`ChatbotEcommerce`) — v1.0.0

Model-tool-driven cart operations and generative-UI commerce responses inside chatbot sessions.

- **Shape:** 17 files; 2 controllers; 1 service; 1 model; 2 migrations; 5 detected routes.
- **Functions:** `handleToolCall`, `handleAnthropicToolCall`, `handleGeminiToolCall`, UI variants, tool-definition methods, and cart add/update/get/checkout.
- **Table:** `ext_chatbot_carts`.
- **Detected integrations:** Chatbot and ChatbotBooking.
- **Review:** two TODO/FIXME markers. Chatbot may present commerce, but catalogues, prices, carts, checkout, orders, inventory, payments, and reconciliation should remain authoritative in WorkCore or a dedicated Commerce Engine.

## Chatbot Review (`ChatbotReview`) — v1.0.0

Configurable review requests and review-submission hooks.

- **Shape:** 7 files; 1 service.
- **Functions:** `requestReview`, `submitReview`.
- **Required integration:** Chatbot conversation runtime.

## External Voice Chatbot (`ChatbotVoice`) — v2.3

External voice-chatbot agents with embedded frames, voice selection, knowledge training, conversations, histories, and provider lifecycle management.

- **Shape:** 57 files; 5 controllers; 1 service; 5 models; 5 migrations; 16 detected routes.
- **Functions:** `query`, `avatars`, `createAgent`, `updateAgent`, `deleteAgent`, `getVoices`, `addKnowledgebase`, balance checks, training, and frame/session operations.
- **Tables:** `ext_voice_chatbots`, `ext_voicechabot_conversations`, `ext_voicechatbot_histories`, `ext_voicechatbot_trains`.
- **Review:** overlaps ElevenLabsVoiceChat, ChatbotVoiceCall, and PhoneCallAgent. Knowledge ingestion should delegate to the Knowledge Engine. Voice consolidation must separate telephony, transport, realtime conversation, STT, TTS, recording, transcript storage, metering, and provider attempts.

## Chatbot Voice Call (`ChatbotVoiceCall`) — v1.1

Starts, ends, and records transcript events for voice calls attached to chatbot sessions, with admin settings.

- **Shape:** 12 files; 2 controllers; 5 detected routes.
- **Functions:** `start`, `end`, `transcript`, `index`, `update`.
- **Provider:** OpenAI voice/realtime service references.
- **Review:** complete calls and ordered transcript segments need dedicated Voice Engine storage with speaker identity, timestamps, consent, redaction, recording links, provider attempts, and auditability. Only derived summaries or commitments should be promoted into UnifiedMemory.

> The canonical Chatbot package contains the only meaningful bundled test tree detected in this category. No bundled tests were detected in the eight selected add-ons; tests may exist elsewhere upstream.
