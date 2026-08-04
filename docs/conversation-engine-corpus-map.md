---
title: Titan Conversation Engine Corpus Map
status: Evidence-backed synthesis
generated: 2026-08-03
source_branch: main
source_commit: ca90081cb999b5cd89ff41af28aa4019c92568e3
source_document_count: 31
recommended_future_design: workcore/titan-conversation-engine-deep-design.md
---

# Titan Conversation Engine Corpus Map

## Executive verdict

The repository does not contain one complete standalone conversation-engine design. It contains almost all required pieces, distributed across product, extension and integration documents.

The converged architecture is:

```text
AIChatPro             internal thinking, investigation and control
Chatbot/PWA           customer and field-worker interaction
Chatbot Agent         human inbox and escalation
AI Agents             execution, workflows and automation
CRM Assistant         pre-sale relationship and sales assistance
Operations Assistant  operational customer, site, work and finance assistance
MagicAI AI runtime     provider execution and broad model catalogue
WorkCore              company policy, operational authority and governed consequences
```

The missing layer is a canonical company-scoped conversation domain that lets those surfaces share participants, messages, channel identities, context, consent, handoff, memory references, approvals, agent runs, provider execution, delivery state, correlation and audit without making any one interface the system of record.

## Strongest evidence

| Source | Main contribution |
|---|---|
| [`reports/2026-08-02-titan-zero-aichatpro-chatbot-agents-deep-scan.md`](./reports/2026-08-02-titan-zero-aichatpro-chatbot-agents-deep-scan.md) | Original multi-surface architecture, existing Agent-to-Chatbot bridge, missing AIChatPro-to-Agent bridge, correlation, memory and Connector Vault gaps |
| [`magicai-integration/09-magicai-crm-host-contract.md`](./magicai-integration/09-magicai-crm-host-contract.md) | Critical chat ownership defects, typed assistant context, entitlement enforcement, action-card security and CRM/Operations separation |
| [`magicai-integration/12-magicai-customer-lifecycle-and-workcore-conversion.md`](./magicai-integration/12-magicai-customer-lifecycle-and-workcore-conversion.md) | Source conversation, channel identity, consent, preferred channel, external mapping, correlation/causation and lifecycle boundaries |
| [`magicai-integration/13-magicai-ai-providers-and-model-routing.md`](./magicai-integration/13-magicai-ai-providers-and-model-routing.md) | MagicAI provider execution versus WorkCore purpose routing, privacy, budgets, credentials, failover and usage attribution |
| [`workcore-business-app-ideas.md`](./workcore-business-app-ideas.md) | Titan Customer Hub product boundary and shared Communications Engine |
| [`titan-quotes-deep-design.md`](./titan-quotes-deep-design.md) | Domain-owned quote interactions and explicit shared-communications extraction |
| [`2026-08-02-magicai-11-operations-hooks-and-menu-integration.md`](./2026-08-02-magicai-11-operations-hooks-and-menu-integration.md) | Operations Assistant scopes, action cards and governed WorkCore tools |
| [`magicai-integration/05-magicai-authentication-and-user-identity.md`](./magicai-integration/05-magicai-authentication-and-user-identity.md) | Durable actor, customer, worker, portal and device identity |
| [`magicai-integration/06-magicai-teams-companies-and-tenancy.md`](./magicai-integration/06-magicai-teams-companies-and-tenancy.md) | WorkCore Company as tenant authority |
| [`magicai-integration/07-magicai-roles-permissions-and-access-control.md`](./magicai-integration/07-magicai-roles-permissions-and-access-control.md) | Entitlement, permission, record access, approval and governed-action enforcement |
| [`magicai-integration/12-magicai-workcore-native-extension-reconciliation-rescan.md`](./magicai-integration/12-magicai-workcore-native-extension-reconciliation-rescan.md) | Current implementation status and unresolved security/readiness defects |

## Existing surfaces and authorities

### AIChatPro

Best suited to internal reasoning, investigation, drafting and control. It already has conversations, messages, streaming, files, connected sources, memory, skills, research, Canvas and vision/document modes.

### Chatbot/PWA

Best suited to stable customer and field-worker interaction. It already has conversations, histories, channels, media, tickets, knowledge, analytics and human handoff.

### Chatbot Agent

Human inbox, assignment and escalation. It is not the same thing as AI Agent automation.

### AI Agents

Workflow definitions, triggers, delayed jobs, tools, conversations, memory and external/internal channels. Agent tools already access Chatbot conversations; the AIChatPro-to-Agent bridge remains missing.

### CRM Assistant

Pre-sale contacts, organisations, leads/deals and sales workflow. Current host hooks are string-based and insufficiently scoped. User ID plus free-form scope cannot safely resolve WorkCore company, membership, branch, record access, device, assurance or correlation.

### Operations Assistant

Operational customer/site, work order, scheduling, workforce, compliance, inventory, readiness and finance preparation. Writes must use WorkCore governed actions.

### Titan Customer Hub

The strongest proposed customer-conversation application. Its central records are customer identity, channel identity, conversation, message, case, consent, preference, portal session and escalation.

### Specialist applications

Domain meaning stays with the owning app:

- Titan Quotes: questions, objections, revisions and acceptance interaction.
- Titan Configurator: design comments and selection decisions.
- Titan Inspect: evidence requests and inspection responses.
- Titan Academy: expert prompts, learner feedback and assessment dialogue.

The shared engine correlates and transports those interactions; it does not take their decisions away.

### MagicAI AI runtime

MagicAI should own stateless execution adapters and the broad model catalogue. It should return normalised model/provider results and usage receipts.

### WorkCore AI policy layer

WorkCore should own company policy, purpose-specific routing, local/private preference, privacy, residency, credentials policy, budgets, permissions, approvals, failover, circuit breaking, operational usage attribution and tool governance.

## Canonical authority boundary

### Conversation Engine owns

- canonical conversation identity;
- participants and channel identities;
- ordered messages and interaction events;
- thread/reply relationships;
- private attachment references and provenance;
- delivery, read, failure, bounce, retry and suppression state;
- typed context links to CRM, WorkCore and Titan records;
- consent and communication-policy snapshots;
- assignment, queue, SLA, case and handoff state;
- human and agent participation state;
- source system, workspace, record and source-conversation IDs;
- correlation and causation IDs;
- assistant, agent, model and tool-run references;
- approval references bound to action payloads;
- scoped memory references;
- retention, correction, export, redaction and legal-hold state;
- conversation-specific audit history.

### Conversation Engine does not own

- WorkCore operating companies or memberships;
- leads/deals where the selected CRM is authoritative;
- customers, properties, workers, jobs or schedules;
- quotes, prices, terms or acceptance;
- inspection findings or evidence truth;
- invoices, payments, allocation or accounting;
- asset identity/condition;
- SOP truth or competency decisions;
- subscriptions, shared-credit balances or model catalogue;
- the operational action ultimately executed by a tool.

## Proposed core records

| Record | Purpose |
|---|---|
| `Conversation` | Stable company-scoped interaction container |
| `ConversationParticipant` | Human, customer, contact, worker, agent, system, device or integration participation |
| `ChannelIdentity` | Email, phone, social handle, chatbot session, portal or internal identity |
| `ContactPoint` | Normalised address/number/handle with verification, purpose and status |
| `Message` | Immutable ordered content/interaction record |
| `MessageAttachment` | Private media/document reference with provenance and retention |
| `DeliveryAttempt` | Provider submission, delivery, read, failure, bounce and retry |
| `ContextLink` | Typed link to lead, customer, property, job, quote, invoice, asset, inspection or incident |
| `ConversationCase` | Queue, owner, priority, SLA, escalation and resolution |
| `Handoff` | Agent-human, team-team, assistant-assistant or channel handoff |
| `ConsentSnapshot` | Purpose, channel, quiet hours, notice, legal basis and opt-out at send time |
| `CaptureReference` | Source channel/workspace/record/conversation used for contact or lead capture |
| `AssistantContext` | Typed assistant key, company, actor, domain scope and allowed tools |
| `AgentRunReference` | Associated workflow/agent execution |
| `ModelRunReference` | Provider/model execution and normalised usage receipt |
| `ToolRunReference` | Proposed/completed tool action, payload hash, risk and result |
| `ApprovalReference` | Verified approval tied to company, actor, action and payload |
| `MemoryReference` | Link to personal, company, customer, job, property, asset or agent memory |
| `ConversationCorrelation` | Shared correlation/causation across surfaces and domains |
| `ConversationAuditEvent` | Append-only access, state, moderation, export and retention history |

## Identity and capture

The engine must distinguish:

```text
Titan Zero subscriber
MagicAI platform user
CRM contact or organisation
WorkCore operational customer/contact
portal user
field worker
human support agent
AI agent/system actor
device
channel identity
```

An inbound channel identity does not automatically become a confirmed customer.

```text
no match                     create lead/capture-review record
one strong open-lead match   enrich lead
one confirmed customer match attach enquiry/interaction
multiple possible matches    identity-resolution queue
external mapping match       update idempotently
```

Probable matches remain distinct from confirmed identities and preserve source/confidence.

## Context and surface projection

A conversation is company scoped and may link to:

```text
actor and membership
CRM workspace
lead/opportunity
customer/contact
property/service site
job/work order
quote/version
invoice/payment request
asset
inspection/finding
incident/claim
worker/team
service agreement/plan
knowledge source
agent workflow/run
```

The same conversation can be projected through AIChatPro, Chatbot/PWA, Chatbot Agent, AI Agent, CRM Assistant, Operations Assistant, Titan Customer Hub and specialist applications. Projection is controlled by role, company permission, context, privacy classification and domain policy.

A unified visible assistant may route between CRM and Operations, but it must resolve each request to a typed domain context and authority-specific tools. One UI does not imply one authority.

## Model and provider execution

Recommended flow beneath a conversation or agent turn:

```text
resolve company, actor, assistant purpose and data class
→ enforce model/entity enabled state
→ enforce plan entitlement, company policy and actor permission
→ reserve token/cost budget
→ resolve purpose-specific provider profile
→ apply privacy, residency and redaction policy
→ resolve credential explicitly
→ execute through stateless MagicAI adapter
→ return normalised output and usage receipt
→ settle WorkCore usage and MagicAI credits
→ record provider/model/fallback attempts and audit
```

User preference cannot override company policy. Local/private providers are first-class. Provider credentials must never be inferred from global authentication state or stored in mutable global configuration.

## Agent and tool execution

```text
message/event
→ resolve company, participant and typed context
→ retrieve permitted knowledge/memory
→ generate response or tool proposal
→ classify risk
→ request verified approval when required
→ execute through authority-specific governed tool
→ append result and domain references
→ update handoff/case state
→ audit and emit versioned events
```

Tool names should show authority, for example:

```text
magicai_crm_create_contact
magicai_crm_mark_deal_won
workcore_create_operational_customer
workcore_create_work_order
workcore_prepare_invoice
```

No assistant or agent writes directly to WorkCore models.

## Action cards and approvals

An action card is only presentation. Its executable proposal binds:

```text
company
requesting actor
approving actor
action key
payload hash
risk
permission
approval status
expiry
one-time execution token
correlation
```

A raw update URL or arbitrary non-empty confirmation string is not approval.

## Consent and communication purpose

The engine must distinguish:

```text
service delivery
appointment reminders
billing
safety
review request
rebooking
marketing
```

Every send evaluates recipient/contact-point verification, purpose, preferred channel, service versus marketing basis, quiet hours, suppression, opt-out, notice version, frequency limits and any lawful safety/operational override. Marketing opt-out does not automatically block required service, safety or billing messages.

## Memory

Keep separate scopes:

- personal;
- company/business;
- lead/customer;
- job;
- property;
- asset;
- agent/workflow.

Conversation history is not unrestricted memory. Retrieval is company partitioned, purpose limited, source linked, correctable and visibility controlled. AI summaries never become operational truth automatically.

## Connector Vault

One encrypted connection should expose separate grants:

```text
AIChatPro search/read
Chatbot conversation-specific access
Agent draft/proposal access
Agent approved execution
administrator configuration/audit
```

Each capability declares schema, read/write scope, role, approval policy, privacy class, tenant ownership, audit and retention.

## Handoff

A handoff preserves trigger, source messages/attachments, source-linked summary, confirmed and uncertain identity/context, unresolved questions, proposed actions, urgency/risk, prior agent/tool attempts, assigned team/SLA, consent/channel constraints and customer-visible acknowledgement.

## Security and reliability

- One ownership-aware chat resolver; no raw unscoped chat-ID lookup.
- Every read/write company scoped and fail closed.
- Assistant streaming checks provider health, feature setting, plan, permission, company/workspace and allowed scope.
- Credentials encrypted and centrally revoked.
- Webhooks verify signature, timestamp and replay.
- Public sessions use hashed, scoped, expiring, revocable tokens.
- Sensitive payloads redacted from logs and provider errors.
- Original evidence attachments remain private and immutable.
- Durable queues, bounded retries, dead letters and provider-event deduplication.
- Explicit queued/submitted/delivered/read/failed/bounced/suppressed states.
- Offline drafts, resumable uploads, ordered local events and visible sync/conflict state.
- No silent last-write-wins for identity, assignment, approval or domain decisions.

## Current implementation status

WorkCore now exists as one native parent extension plus five activation add-ons. Package construction and installation verification have improved significantly.

Conversation production-readiness remains blocked by:

- invalid permission resolver access-level path;
- Passport/Sanctum mismatch;
- arbitrary-string high-risk confirmation;
- fail-open tenant reads when context is absent;
- missing first-class WorkCore menus and Assistant surfaces;
- missing MagicAI plan-entitlement projection;
- WorkCore native AI provider not reliably loaded/configured;
- no production credential resolver;
- no stateless MagicAI Entity execution adapter;
- incomplete privacy filtering before provider transmission;
- incomplete operational tool registries.

## Confirmed architectural gaps

1. No canonical Conversation Engine deep design.
2. Separate conversation/message concepts across AIChatPro, Chatbot, CRM Assistant and Agents.
3. Missing AIChatPro-to-Agent bridge.
4. Critical central chat ownership gaps.
5. Incomplete assistant entitlement/settings/company/scope enforcement.
6. No unified participant, contact-point and channel identity.
7. No complete uncertain-match/identity-resolution workflow.
8. No shared source-conversation/external-mapping contract.
9. Correlation/causation contract not implemented.
10. General consent, purpose, preferences and suppression model missing.
11. Fragmented memory scopes.
12. Duplicated connector credentials/grants.
13. No unified handoff/case state.
14. Inconsistent delivery/webhook/retry semantics.
15. Domain conversations risk becoming separate inboxes.
16. Approval not uniformly tied to cards, messages, tools and payloads.
17. Offline reconciliation, retention, correction and legal hold need one model.
18. Provider execution is not stateless or fully company/purpose governed.
19. Titan Customer Hub has no dedicated deep design.
20. Native packaging exists, but security and first-class host integration remain incomplete.

## Recommended next document

Create `workcore/titan-conversation-engine-deep-design.md` with:

1. thesis and authority boundary;
2. CRM authority modes;
3. participant, contact-point and identity resolution;
4. conversation/message/case/attachment domain;
5. state machines;
6. typed assistant and surface registry;
7. channels and Connector Vault;
8. context, correlation and external mapping;
9. model/provider execution and usage receipts;
10. agents, tools, action cards and approvals;
11. assignment, handoff, escalation and SLA;
12. memory and knowledge retrieval;
13. consent, purpose, preferences and suppression;
14. evidence and provenance;
15. offline queue, delivery and reconciliation;
16. security, privacy, retention, correction and legal hold;
17. APIs, commands, events and webhooks;
18. migration from existing chat tables;
19. observability and evaluation;
20. testing, release gates, build sequence and packaging.

## Final conclusion

The repository points to one coherent result:

> **One company-scoped conversation identity, many role-specific surfaces, explicit customer/channel identity, purpose-governed model execution, domain-owned decisions, governed AI actions and preserved human authority.**
