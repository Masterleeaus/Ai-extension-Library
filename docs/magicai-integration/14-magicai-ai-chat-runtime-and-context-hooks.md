---
title: "MagicAI 11 AI Chat Runtime and Context Hooks"
scan_number: 14
date: "2026-08-03"
magicai_version: "11.00"
status: "Completed"
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/14-magicai-ai-chat-runtime-and-context-hooks.md"
---

# Scan 14 — MagicAI 11 AI Chat Runtime and Context Hooks

## Executive conclusion

MagicAI 11 contains a feature-rich AI Chat shell with web and Passport API routes, categories and assistants, conversation persistence, streaming, realtime chat, files, PDF context, images, web search, Brand Voice, skills, connectors, CRM Assistant hooks, exports, team sharing and temporary chats.

That interface and transport layer is valuable for Titan Zero, but the current implementation is not safe enough to become WorkCore's operational conversation authority.

The final integration boundary is:

> **MagicAI provides the chat interface, streaming presentation and provider adapters.**  
> **WorkCore owns operational conversations, company context, memory, permissions, tools, approvals, retention and audit.**

The WorkCore Operations Assistant should use a MagicAI-styled interface while storing and orchestrating operational conversations in WorkCore's company-scoped AI tables. Provider keys must never be delivered to browsers or mobile clients.

## Critical findings

1. Multiple web and API endpoints load chats or messages by numeric ID without verifying ownership.
2. A user can potentially read, append to or stream against another user's conversation.
3. Global API search returns chat-history records without user scoping.
4. Export endpoints retrieve all messages for a supplied conversation ID without ownership checks.
5. An unauthenticated PDF-context endpoint retrieves embedded document chunks by chat ID.
6. Realtime chat sends the actual OpenAI API key to browser/mobile clients as three Base64 fragments.
7. Base64 fragmentation is reversible and provides no meaningful protection.
8. Realtime transcript saving trusts client-supplied AI output and usage values.
9. Direct client-to-provider realtime bypasses server-side model policy, privacy, usage verification and audit.
10. Chat action routes frequently lack the plan middleware applied to the chat page.
11. CRM Assistant context can be invoked through the shared stream path without a confirmed CRM entitlement check.
12. Chat context accepts request-controlled assistant types, CRM scope, model, brand records, files, skills and connectors.
13. Retrieved PDF, web and connector content lacks a strong untrusted-content boundary.
14. Connector/extension tools do not pass through one common WorkCore approval gateway in the host path.
15. MagicAI conversation and vector tables contain no WorkCore company context.
16. Team-sharing rules are inconsistent and lack explicit conversation-level permissions.
17. Chat records cascade-delete with users and categories.
18. No complete legal-hold, retention, archive or company-residency model exists.
19. WorkCore conversations are strongly company and actor scoped.
20. WorkCore native AI remains unreachable because of the loading/configuration defects in Scan 13.
21. WorkCore conversation and memory content remains plaintext.
22. WorkCore approval resume is not bound to the original requesting actor or an explicit executor policy.

# 1. Runtime size and concentration

Five central MagicAI chat files contain more than 10,000 lines:

| File | Lines |
|---|---:|
| Web AIChatController | 2,887 |
| API AIChatController | 1,067 |
| API AIRealTimeChatController | 1,235 |
| GeneratorController | 1,432 |
| StreamService | 3,756 |
| **Total** | **10,377** |

This concentration has produced duplicated web/API/realtime logic, inconsistent ownership checks, inconsistent entitlements and duplicated provider routing.

# 2. Route architecture

MagicAI's authenticated dashboard exposes routes for:

```text
chat list and page
send and stream
open chat/chatbot areas
start normal, file and chatbot chats
search and history
rename, pin, delete and clear
transcription
PDF, Word and TXT export
temporary/low-chat persistence
shared generator streaming
```

Only selected page/list routes use `CheckTemplateTypeAndPlan`. Many action routes do not. Every operation must enforce the same entitlement and feature settings as the UI page.

Passport API groups include:

```text
/api/aichat/*
/api/airealtimechat/*
```

The two API controllers duplicate much of the same functionality and should be consolidated behind one authorization-aware service.

MagicAI also contains dormant core `/chatbot-api/*` routes. Scan 02 established that their provider is not active in this build. If activated, they require a separate public-chatbot security review.

# 3. Storage model

`user_openai_chat` stores user, category, title, team/chatbot references, URLs, document information, provider thread/file IDs, pin/empty state and chat type.

`user_openai_chat_messages` stores user input, provider response, rendered output, images, PDF paths, realtime state, suggestions, Model Council fields, skills and connector metadata.

Missing operational dimensions include:

```text
WorkCore company
company membership
actor subject
branch/territory
device
data classification
retention policy
legal hold
encryption key version
```

MagicAI chat tables should therefore not become WorkCore's operational authority.

# 4. Conversation ownership failures

Confirmed unscoped or partially scoped flows include:

- Opening a web chat area
- Opening selected chatbot areas
- Sending/appending messages
- Streaming using an existing conversation
- Loading a selected message
- Changing titles in selected paths
- Pinning/saving selected chats
- File/PDF chat paths
- API message access in selected conversation paths
- General API chat-history search

An authenticated user who discovers another chat or message ID can potentially read history, append a message, cause a provider call using victim context or alter the stored response.

## Required resolver

Create one mandatory service:

```text
ConversationAccessResolver
```

Inputs:

```text
actor
conversation public ID
expected chat context
requested operation
active team or WorkCore company
```

It returns a conversation only when the actor has the required ownership/share permission. Direct controller calls to `UserOpenaiChat::find()` or `whereId()` should be prohibited.

# 5. Search and export disclosure

The general API search queries chat titles without current-user scoping. The realtime API duplicates the defect.

PDF, Word and TXT export methods also select messages using only the submitted chat ID.

Additional export defects:

- PDF generation returns a test view before its download code.
- Word export uses a fixed `document.docx` filename.
- TXT export uses a fixed `document.txt` filename.
- Concurrent exports can collide.
- Exports contain no audit, classification, redaction or retention metadata.

Use authorized conversation public IDs and unique temporary paths with automatic cleanup and export auditing.

# 6. Unauthenticated PDF/vector disclosure

The route:

```text
POST pdf/getContent
```

is outside the authenticated dashboard group.

It accepts a prompt and chat ID, then queries `pdf_data` and returns semantically similar document chunks.

`pdf_data` contains only:

```text
chat_id
content
vector
```

It has no user, company, document foreign key or access token.

A caller can potentially enumerate chat IDs and retrieve uploaded document content.

## Required containment

1. Remove public access.
2. Require an authenticated or signed external-chat context.
3. Resolve document authority through conversation/company ownership.
4. Add document, user/company and chunk foreign keys.
5. Use public IDs.
6. Rate-limit and audit retrieval.
7. Purge orphaned chunks.
8. Apply data-classification policy.

# 7. Files and retrieval

MagicAI can save files locally or to S3, parse PDF text, create embeddings and store plaintext chunks.

Gaps include:

- No WorkCore company scope
- No owner on vector chunks
- No complete malware/DLP/classification process in the older PDF path
- No encryption of extracted text
- No retention or residency routing
- Public/local URLs may remain available
- Embedding provider selection ignores WorkCore company AI policy

WorkCore attachments must remain in company-scoped storage. Chat receives only authorized retrieval chunks.

# 8. Prompt-injection boundaries

MagicAI can inject the following into one conversation history:

```text
category instructions
previous messages
CRM Assistant context
social-agent context
Brand Voice and products
skills
PDF/vector content
web-search content
connector results
current user prompt
```

The runtime does not centrally distinguish trusted policy from untrusted data.

Recommended trust labels:

```text
platform_policy
company_policy
agent_instruction
user_message
tool_result
retrieved_document
external_web_content
connector_content
```

External content and documents are evidence only. They can never override permission, privacy, approval or tool policy.

# 9. CRM Assistant and shared stream

CRM Assistant context is added when the CRM provider is registered, the actor is authenticated and the request uses the CRM Assistant template type.

The shared stream path does not visibly enforce:

```text
CRM plan entitlement
crm_enabled
crm_assistant_enabled
CRM workspace permission
WorkCore company mapping
```

The request-controlled CRM scope defaults to `all`.

Before injecting CRM context, the server must validate provider health, feature settings, effective plan, actor permission, workspace/company mapping and an allowlisted scope.

# 10. Skills, connectors and tools

MagicAI skills can contribute both system instructions and callable functions. Connector services can be invoked by the stream runtime, with results injected back into the model.

A skill cannot override company policy. Required instruction hierarchy:

```text
platform safety
→ company AI policy
→ WorkCore permissions and approvals
→ agent policy
→ skill instructions
→ user request
→ retrieved external content
```

Every operational connector/tool call must use:

```text
company context
actor permission
tool allowlist
risk policy
verified approval
idempotency
audit
```

# 11. Realtime credential exposure

Web and mobile realtime paths return the actual provider key split into three Base64 fragments.

This allows reconstruction and use outside Titan Zero, bypassing credits, company model policy, retention and audit.

Replace this with:

```text
server-side realtime proxy
or
short-lived provider ephemeral token
or
approved company-owned local/device credential
```

Permanent platform or user keys must never reach the client. Potentially exposed production keys should be rotated.

# 12. Client-authoritative realtime transcript

The realtime save endpoint accepts client-supplied user text, AI response and usage values, then stores them as authoritative provider output.

The server cannot prove which model generated the response, whether it was changed or whether the usage is accurate.

The server must own provider sessions, transcripts, usage receipts, tool events and completion status. Clients may submit audio or user input, not authoritative AI output or billing data.

# 13. Team sharing

Some MagicAI listing paths include user-owned and team-shared conversations, while other paths filter only by user. There is no complete explicit share model.

Operational conversation sharing requires records containing:

```text
company
conversation
owner actor
share target
read/write permission
created by
expiry
revocation
reason
```

MagicAI Team membership is a SaaS-seat relationship and must not automatically reveal all WorkCore conversations.

# 14. Retention

MagicAI chats/messages are hard or cascade deleted with conversations, users and categories.

The scheduled chat-clearing command runs only in demo mode and retains the newest 22 non-special chats globally, not per user.

Production chat requires company policies for:

```text
retention period
archive
legal hold
soft deletion
purge eligibility
exports
attachments
vector chunks
provider files/threads
audit retention
```

# 15. WorkCore native conversation strengths

WorkCore's AI schema provides:

```text
company-scoped conversations
actor ownership
ordered messages
idempotent orchestration runs
steps
approval requests
user/company memories
```

Positive controls include:

- Public IDs
- Company and actor validation
- Actor-scoped listing
- Idempotency key required for turns
- Unique company/idempotency constraint
- Row locking for message sequence
- Step and tool-call limits
- Tool catalogue filtering
- Capability and permission gates
- Expiring one-time approvals
- Company-scoped runs and usage
- Dedicated company-memory permission

This is the correct operational conversation authority.

# 16. WorkCore context window and memory

`ContextWindowBuilder` combines:

```text
agent system prompt
explicit WorkCore memory
an authority statement
recent conversation history
```

Default limits are 40 messages, 48,000 characters and 20 memories.

The authority statement correctly says tool outputs are evidence and WorkCore actions/read models remain authoritative.

Remaining limitations:

- Character limits only approximate tokens.
- Long content is skipped rather than summarized.
- Context provenance is not structured.
- No attachment/RAG contract exists.
- Message content is not classified before outbound transmission.
- Memory is plaintext and can become stale or malicious.

Company-memory writes require `workcore.ai.manage_company_memory`, and apparent secrets are rejected. Memory still needs encryption, versioning, review and retention policy.

# 17. WorkCore approval-resume defect

WorkCore approval decisions require dedicated approval permission and approvals are checked for status, expiry and one-time consumption.

The resume flow does not require the current resuming actor to be:

```text
the original requester
or
an explicitly authorized executor
```

Any same-company actor who knows the approval public ID, has tool access and can resolve that tool may potentially resume another actor's paused run. The tool is then executed as the resuming actor.

Approval records need explicit requester, approver and executor policy. Resume must enforce it before execution.

# 18. WorkCore retention and identity gaps

WorkCore conversations, runs, approvals and memories still reference MagicAI user rows directly. Several foreign keys cascade on user deletion, conflicting with the durable actor-subject design from Scan 05.

Message and memory content is not encrypted. No complete archive, delete, export, sharing, legal-hold or attachment route set exists.

These must be completed before operational chat launch.

# 19. Operations Assistant architecture

Recommended route family:

```text
dashboard.user.operations.assistant.*
```

Recommended ownership:

```text
MagicAI UI shell
    composer, responsive layout and streaming presentation

WorkCore conversation store
    company/actor-scoped messages

WorkCore orchestrator
    context, policy, tools, approvals and audit

MagicAI Entity adapter
    provider execution

WorkCore read/action registries
    operational data and writes
```

Operational messages should not be copied into `user_openai_chat` unless a deliberate read-only projection is required.

# 20. Operations Assistant turn flow

```text
authenticated MagicAI user
→ active platform account
→ active WorkCore company and membership
→ AI entitlement and permission
→ registered device where required
→ resolve/create WorkCore conversation
→ append user message
→ authorized context providers
→ privacy/residency policy
→ budget and credit reservation
→ MagicAI provider adapter
→ validate tool calls
→ pause for verified approval
→ governed action
→ append result and settle usage
→ stream presentation
→ audit
```

The client receives rendered events, never provider credentials.

# 21. Context providers

Operational context must come from authorized read models, for example:

```text
Current Job
Today's Schedule
Customer and Site
Worker Assignment
Compliance Readiness
Inventory Availability
Invoice-Ready
Incident Context
Knowledge
```

Each declares required permission, record scope, allowed fields, sensitivity, cache policy and retention policy.

# 22. Attachments and offline/device chat

WorkCore attachment records should contain company, conversation, uploader, hash, malware state, classification, retention, extraction and embedding state.

Offline field chat should store an encrypted local draft, synchronize it when connectivity returns, then revalidate company, membership and job access server-side before AI execution.

For approved local models, the device may execute using a registered company/local profile and later synchronize usage/audit. It must never receive a platform provider key.

Offline tool writes remain governed queued actions and require server validation.

# 23. Immediate containment actions

1. Disable or protect `pdf/getContent`.
2. Remove provider-key fragments from all responses.
3. Rotate possibly exposed provider keys.
4. Add ownership checks to every chat/message endpoint.
5. Scope global chat search.
6. Protect exports.
7. Add plan and feature middleware to action routes.
8. Disable CRM Assistant stream injection without entitlement.
9. Validate category, assistant and scope values.
10. Disable operational connector writes until governed approval is integrated.

# 24. Required MagicAI fixes

## Critical

1. Add one ownership-aware conversation resolver.
2. Replace direct chat/message ID lookup in controllers.
3. Scope all search results.
4. Secure exports.
5. Remove unauthenticated PDF retrieval.
6. Add tenant/owner keys to vector data.
7. Stop returning provider keys.
8. Proxy realtime or use short-lived ephemeral credentials.
9. Stop trusting client AI responses and usage.
10. Enforce plan/feature gates on every action.
11. Enforce CRM entitlement/workspace scope.
12. Protect shared generator context.
13. Add connector/tool approval gateway.

## High

14. Consolidate duplicated controllers.
15. Add typed chat contexts and provenance.
16. Mark external content untrusted.
17. Add explicit conversation sharing.
18. Add company-aware operational-chat support.
19. Add unique export files and cleanup.
20. Add attachment malware/DLP/classification controls.
21. Add retention, archive and legal hold.
22. Redact provider/tool errors.
23. Add security regression tests.

# 25. Required WorkCore fixes

## Critical

1. Load and configure native AI correctly.
2. Use Passport-compatible middleware.
3. Bind MagicAI provider adapter and credential resolver.
4. Fix approval-resume actor/executor binding.
5. Replace user cascades with durable actor references.
6. Encrypt or envelope-protect sensitive conversation content.
7. Add retention and legal hold.
8. Apply privacy policy before provider transmission.
9. Add Operations Assistant tool registries.
10. Add streaming transport.

## High

11. Add archive/delete/export routes.
12. Add explicit company sharing.
13. Add attachments and RAG.
14. Add provenance-aware context.
15. Add token-aware summarization.
16. Add memory review/versioning.
17. Add offline/device synchronization.
18. Add sensitive-field projection.
19. Add approval-card UI.
20. Add full conversation tests.

# 26. Required tests

## Ownership and export

- User cannot open, append to, rename, pin, delete or export another user's chat.
- User cannot stream using another user's history.
- Message ID must belong to the authorized conversation.
- Search returns only authorized chats.
- Team sharing respects explicit permission.

## Documents and RAG

- Anonymous caller cannot retrieve PDF chunks.
- Chat-ID enumeration reveals nothing.
- Retrieval requires company and record permission.
- Deleted/restricted documents disappear from retrieval.
- Malicious document instructions cannot override policy.
- Retrieval records provenance.

## Realtime

- Client never receives permanent provider credentials.
- Ephemeral credential is short-lived and restricted.
- Server records authoritative transcript and usage.
- Falsified client output is rejected.
- Realtime follows company model policy and tool approvals.

## Context and tools

- Direct CRM Assistant request without entitlement is denied.
- Invalid CRM scope is denied.
- Category and Brand Voice ownership is enforced.
- Skills cannot override company policy.
- High-risk connector/tool writes require approval.
- External data is treated as untrusted evidence.

## WorkCore

- Conversation is company and actor scoped.
- Idempotent turns replay safely.
- Sequence remains unique under concurrency.
- Another actor cannot resume a paused run without executor policy.
- Expired/consumed approval cannot execute.
- Company memory requires dedicated permission.
- Retention purge consistently handles messages, vectors and attachments.

# 27. Decisions established by Scan 14

1. MagicAI chat tables will not be WorkCore's operational conversation authority.
2. MagicAI UI and streaming presentation remain valuable.
3. WorkCore owns operational conversations, memory, tools and approvals.
4. Provider keys never reach clients.
5. Every chat/message access uses one authorization resolver.
6. PDF/vector retrieval is company and record scoped.
7. Retrieved content is untrusted evidence.
8. Context is typed and provenance-aware.
9. CRM Assistant and Operations Assistant remain distinct internally.
10. Tool writes always use governed actions.
11. Approval resume requires explicit executor binding.
12. Conversations require durable actor identity and retention policy.
13. Offline chat queues intent, not credentials or unvalidated writes.
14. Native WorkCore AI remains disabled until Scan 13 and Scan 14 blockers are fixed.
15. Existing MagicAI chat security issues require immediate containment.

# 28. Next scan

```text
15-magicai-ai-agent-builder-and-runtime.md
```

The paid AI Agent extension package is required for a complete deep scan. If unavailable, the next report should map the confirmed host contract and WorkCore agent kernel while marking extension behaviour unverified.

# Evidence files

## MagicAI

```text
routes/panel.php
routes/api.php
app/Providers/ChatbotServiceProvider.php
app/Http/Controllers/AIChatController.php
app/Http/Controllers/Api/AIChatController.php
app/Http/Controllers/Api/AIRealTimeChatController.php
app/Http/Controllers/OpenAi/GeneratorController.php
app/Http/Controllers/ExportChatController.php
app/Http/Controllers/ChatPdfController.php
app/Services/Stream/StreamService.php
app/Services/VectorService.php
app/Models/UserOpenaiChat.php
app/Models/UserOpenaiChatMessage.php
app/Models/OpenaiGeneratorChatCategory.php
app/Models/PdfData.php
app/Models/ChatbotHistory.php
app/Console/Commands/Clear/ClearUserOpenAIChatCommand.php
```

## WorkCore

```text
app/Domains/WorkCore/System/AI/
app/Domains/WorkCore/System/AI/Http/Controllers/NativeAiConversationController.php
app/Domains/WorkCore/System/AI/Http/Controllers/NativeAiApprovalController.php
app/Domains/WorkCore/System/AI/Http/Controllers/NativeAiMemoryController.php
app/Domains/WorkCore/System/AI/Orchestration/AgentOrchestrator.php
app/Domains/WorkCore/System/AI/Orchestration/ContextWindowBuilder.php
app/Domains/WorkCore/System/AI/Orchestration/MemoryService.php
app/Domains/WorkCore/System/AI/Orchestration/Persistence/DatabaseConversationStore.php
app/Domains/WorkCore/System/AI/Orchestration/Persistence/DatabaseMemoryStore.php
app/Domains/WorkCore/System/AI/Security/NativeAiAccessGate.php
app/Domains/WorkCore/System/AI/routes/api.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120099_create_tz_ai_orchestration_tables.php
```

# Evidence limitations

This was a static source scan. It did not execute chat routes, exfiltrate a live key, run realtime sessions, upload hostile PDFs, run connector extensions, boot WorkCore AI, execute approvals or test browser rendering/XSS.

Paid AI Chat Pro, CRM, Chatbot, channel and connector extension internals remain unverified because their packages were not included.