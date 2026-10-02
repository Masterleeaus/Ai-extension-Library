# All Remaining Open Issues

Complete list of 50 open issues for the AI Extensions platform.

## Quick Index

- **ai-configuration**: 1 issues
- **ai-tuning**: 1 issues
- **architecture**: 1 issues
- **assets**: 1 issues
- **behavior-config**: 1 issues
- **branding**: 1 issues
- **business-operations**: 2 issues
- **compliance**: 1 issues
- **critical-path**: 6 issues
- **crm**: 1 issues
- **data-collection**: 1 issues
- **dispatch**: 1 issues
- **document-generation**: 1 issues
- **finance**: 1 issues
- **forms**: 1 issues
- **foundation**: 4 issues
- **high-priority**: 6 issues
- **i18n**: 1 issues
- **localization**: 1 issues
- **multi-language**: 1 issues
- **operations**: 1 issues
- **phase-0**: 7 issues
- **prompts**: 1 issues
- **properties**: 1 issues
- **security**: 4 issues
- **templates**: 1 issues
- **testing**: 2 issues
- **theming**: 1 issues
- **vertical-customization**: 6 issues
- **voice**: 1 issues
- **webhooks**: 1 issues
- **white-label**: 1 issues
- **workcore**: 6 issues
- **workforce**: 1 issues

---

## Issue #211: [Critical] Quarantine inbound WhatsApp media and remove base64 message payloads

**URL**: https://github.com/masterleeaus/ai-extensions/issues/211
**State**: OPEN

Parent: #6
Depends on: #24, #25, #31, #42, #44, #61, #64

## Confirmed defect

`extensions/AIAgentWhatsappChannel/System/Http/Controllers/Webhook/WhatsappWebhookController.php` downloads inbound WhatsApp media into memory, reads the complete response body, base64-encodes it, and embeds it directly in the `IncomingMessage` attachment array.

The current path has no explicit streaming byte limit, quarantine record, magic-byte verification, malware scan, durable attachment identity, decompression/resource controls, or queue/event payload boundary. The POST handler also lacks shared webhook signature/replay verification before media retrieval and downstream message handling.

## Risk

- Oversized remote media can exhaust PHP worker memory.
- Full base64 bodies can inflate queue, event, log, or model payloads by roughly one third.
- Claimed MIME types and filenames are trusted before content validation.
- Malicious or malformed files can reach downstream handlers without quarantine.
- Retries can repeatedly download and encode the same media.
- Raw request payloads and attachments have no durable tenant-scoped identity or retention state.

## Immediate containment

- Enforce a strict maximum response size before buffering any media.
- Reject media unless the inbound webhook has passed shared verification and tenant resolution.
- Disable forwarding base64 attachment bodies into message/event objects.
- Restrict accepted media to an explicit allow-list and reject mismatched content.

## Required implementation

- Stream downloads through the secure fetch/quarantine boundary from #42.
- Create a tenant-scoped durable attachment record with provider ID, source identity, byte count, detected MIME, hash, status, retention, and correlation ID.
- Validate provider URL/host policy, redirects, response size, MIME, magic bytes, filename, and supported type.
- Run malware/content safety scanning before promotion.
- Make retrieval idempotent by provider media ID and tenant/channel context.
- Store bytes in private object storage using server-generated names.
- Pass attachment IDs and bounded metadata through `IncomingMessage`, events, queues, tools, and model calls—not full binary/base64 content.
- Add cleanup, expiry, deletion, cancellation, retry, and stale-download behavior.
- Redact access tokens, signed URLs, message bodies, and attachment contents from logs and errors.
- Preserve user-visible image/PDF handling through a compatibility adapter.

## Acceptance criteria

- A valid signed WhatsApp media event creates one durable attachment under duplicate delivery/retry.
- Invalid, unsigned, expired, replayed, cross-tenant, oversized, unsupported, MIME-mismatched, or malicious media is rejected before downstream action execution.
- Queue/event payloads contain attachment identifiers and bounded metadata only.
- Worker memory usage does not scale with the complete remote object size.
- Attachment bytes are never publicly accessible before validation.
- Tenant A cannot retrieve or reference Tenant B attachments.
- Tests cover image and PDF success, duplicate delivery, size limits, MIME spoofing, malformed files, provider timeout, cancellation, cleanup, and secret/log redaction.

## Evidence baseline

Confirmed against `main` commit `519226756f62e32efa82bf17b6e46185fa279cac` and working branch `agent/ai-extensions-deep-scan-2026-08-04`.

---

## Issue #210: [Vertical Customization] Behavior Configuration Framework - AI Model Tuning

**URL**: https://github.com/masterleeaus/ai-extensions/issues/210
**State**: OPEN
**Labels**: vertical-customization, ai-tuning, behavior-config

## Summary
Create a behavior configuration framework allowing each vertical to tune AI model parameters, temperature, response style, and guardrails without code changes.

## Scope
- **Type**: Vertical customization framework
- **Features**: Parameter tuning, guardrails, response style, safety controls
- **Targets**: AiChatPro, Chatbot, AIAgent
- **Use**: All verticals and business domains

## Features
- Model parameter tuning (temperature, top_p, etc.)
- Response style customization (formal, casual, technical, etc.)
- Tone and personality settings
- Safety guardrails and content filters
- Domain-specific knowledge injection
- Maximum response length tuning
- Retry and fallback strategies
- Bias detection and mitigation
- Hallucination prevention settings
- Performance optimization parameters

## Acceptance Criteria
- [ ] Configuration framework implemented
- [ ] Parameter tuning UI created
- [ ] AiChatPro model tuning working
- [ ] Chatbot model tuning working
- [ ] AIAgent behavior configuration active
- [ ] Guardrails enforced
- [ ] Safety testing passed
- [ ] Tests passing (80%+ coverage)
- [ ] Documentation complete

## Integration Points
- Issue #187: WorkCore Foundation + AiChatPro
- Issue #193: WorkCore Foundation + Chatbot
- Issue #199: WorkCore Foundation + AIAgent

## Example Use Cases
- E-commerce: Upsell-focused response style
- Field Services: Professional and technical tone
- Real Estate: Conservative and trust-focused tone
- Fitness: Motivational and encouraging tone

---

## Issue #209: [Vertical Customization] Branding & Theming Framework - White-Label Customization

**URL**: https://github.com/masterleeaus/ai-extensions/issues/209
**State**: OPEN
**Labels**: vertical-customization, branding, theming, white-label

## Summary
Create a branding and theming framework allowing each vertical or tenant to customize the visual appearance and brand identity of AiChatPro, Chatbot, and AIAgent.

## Scope
- **Type**: Vertical customization framework
- **Features**: Theme builder, color schemes, logos, fonts, UI customization
- **Targets**: AiChatPro, Chatbot, AIAgent
- **Use**: All verticals and business domains

## Features
- Theme builder with visual preview
- Color palette management
- Logo and icon customization
- Font and typography customization
- CSS variable system
- Brand voice and tone settings
- White-label mode
- Multi-brand tenant support
- Theme versioning and rollback
- Mobile and desktop theming

## Acceptance Criteria
- [ ] Theme builder implemented
- [ ] AiChatPro theming working
- [ ] Chatbot theming working
- [ ] AIAgent branding applied
- [ ] White-label mode functional
- [ ] Multi-tenant theming working
- [ ] Mobile theming responsive
- [ ] Tests passing (80%+ coverage)
- [ ] Documentation complete

## Integration Points
- Issue #187: WorkCore Foundation + AiChatPro
- Issue #193: WorkCore Foundation + Chatbot
- Issue #199: WorkCore Foundation + AIAgent

## Example Use Cases
- E-commerce: Brand-specific color schemes
- Field Services: Company logo and colors
- Real Estate: Brokerage branding
- Fitness: Gym or trainer branding

---

## Issue #208: [Vertical Customization] Localization Framework - Language, Region & Cultural Adaptation

**URL**: https://github.com/masterleeaus/ai-extensions/issues/208
**State**: OPEN
**Labels**: vertical-customization, localization, i18n, multi-language

## Summary
Create a comprehensive localization framework allowing each vertical to adapt AI responses, templates, and content for different languages, regions, and cultural contexts.

## Scope
- **Type**: Vertical customization framework
- **Features**: Multi-language support, regional customization, cultural adaptation, RTL support
- **Targets**: AiChatPro, Chatbot, AIAgent
- **Use**: All verticals and business domains

## Features
- Multi-language translation management
- Language-specific prompt adaptation
- Regional pricing and currency
- Cultural calendar and holiday awareness
- Regional compliance requirements
- Right-to-left (RTL) language support
- Language-specific templates
- Translation management UI
- Automated translation suggestions
- Language-specific analytics

## Acceptance Criteria
- [ ] Localization framework implemented
- [ ] Multi-language support working
- [ ] AiChatPro localization active
- [ ] Chatbot localization active
- [ ] AIAgent localization active
- [ ] RTL support working
- [ ] Regional compliance checking
- [ ] Tests passing (80%+ coverage)
- [ ] Documentation complete

## Supported Languages (Phase 1)
- English, Spanish, French, German, Italian, Portuguese
- Japanese, Chinese (Simplified & Traditional), Korean
- Arabic (RTL), Hebrew (RTL)

## Integration Points
- Issue #187: WorkCore Foundation + AiChatPro
- Issue #193: WorkCore Foundation + Chatbot
- Issue #199: WorkCore Foundation + AIAgent

## Example Use Cases
- E-commerce: Multi-region pricing and product names
- Field Services: Regional service offerings
- Real Estate: Regional property terminology
- Fitness: Cultural calendar-aware scheduling

---

## Issue #207: [Vertical Customization] Forms Builder Framework - Domain-Specific Data Collection

**URL**: https://github.com/masterleeaus/ai-extensions/issues/207
**State**: OPEN
**Labels**: vertical-customization, forms, data-collection

## Summary
Create a vertical-specific forms builder extension allowing each vertical to design and deploy custom forms for data collection without coding.

## Scope
- **Type**: Vertical customization framework
- **Features**: Drag-and-drop form builder, field types, validation, branching logic
- **Targets**: AiChatPro, Chatbot, AIAgent
- **Use**: All verticals and business domains

## Features
- Drag-and-drop form builder
- Rich field types (text, email, phone, date, file, etc.)
- Conditional logic and field branching
- Multi-step forms and workflows
- Form validation and error handling
- Pre-filling from customer data
- Integration with WorkCore modules
- Mobile-responsive forms
- Form versioning and testing
- Analytics and completion metrics

## Acceptance Criteria
- [ ] Form builder implemented
- [ ] AiChatPro integration working
- [ ] Chatbot integration working
- [ ] AIAgent integration working
- [ ] Conditional logic functional
- [ ] Validation working
- [ ] Mobile responsiveness verified
- [ ] Tests passing (80%+ coverage)
- [ ] Documentation complete

## Integration Points
- Issue #187: WorkCore Foundation + AiChatPro
- Issue #193: WorkCore Foundation + Chatbot
- Issue #199: WorkCore Foundation + AIAgent
- Issue #184: WorkCoreWorkOperations (forms module)

## Example Use Cases
- E-commerce: Product inquiry forms
- Field Services: Job intake forms
- Real Estate: Property inquiry forms
- Fitness: Membership signup forms

---

## Issue #206: [Vertical Customization] Template Management Framework - Domain-Specific Templates

**URL**: https://github.com/masterleeaus/ai-extensions/issues/206
**State**: OPEN
**Labels**: vertical-customization, templates, document-generation

## Summary
Create a vertical-specific template management extension allowing each vertical to define and manage templates for responses, documents, forms, and communications.

## Scope
- **Type**: Vertical customization framework
- **Features**: Template library, inheritance, versioning, preview
- **Targets**: AiChatPro, Chatbot, AIAgent
- **Use**: All verticals and business domains

## Features
- Response templates for common scenarios
- Document templates (proposals, quotes, reports)
- Email and message templates
- Form templates and layouts
- Notification templates
- Template inheritance and composition
- Template versioning and rollback
- Template preview and testing

## Acceptance Criteria
- [ ] Template framework implemented
- [ ] Template library created
- [ ] AiChatPro integration working
- [ ] Chatbot integration working
- [ ] AIAgent integration working
- [ ] Document generation working
- [ ] Template inheritance working
- [ ] Tests passing (80%+ coverage)
- [ ] Documentation complete

## Integration Points
- Issue #187: WorkCore Foundation + AiChatPro
- Issue #193: WorkCore Foundation + Chatbot
- Issue #199: WorkCore Foundation + AIAgent

## Example Use Cases
- E-commerce: Order confirmation templates
- Field Services: Job quote templates
- Real Estate: Property listing templates
- Fitness: Training program templates

---

## Issue #205: [Vertical Customization] Prompt Customization Framework - Per-Vertical AI Prompts

**URL**: https://github.com/masterleeaus/ai-extensions/issues/205
**State**: OPEN
**Labels**: vertical-customization, prompts, ai-configuration

## Summary
Create a vertical-specific prompt customization extension allowing each vertical to define, manage, and deploy custom AI prompts across AiChatPro, Chatbot, and AIAgent without code changes.

## Scope
- **Type**: Vertical customization framework
- **Features**: Prompt templates, versioning, A/B testing, performance metrics
- **Targets**: AiChatPro, Chatbot, AIAgent
- **Use**: All verticals and business domains

## Features
- Prompt template library per vertical
- Context-aware prompt injection
- Prompt versioning and rollback
- A/B testing framework
- Performance metrics and analytics
- Role-based prompt customization
- Language and region-aware prompts
- Dynamic prompt composition

## Acceptance Criteria
- [ ] Prompt framework implemented
- [ ] Template library created
- [ ] AiChatPro integration working
- [ ] Chatbot integration working
- [ ] AIAgent integration working
- [ ] Versioning system functional
- [ ] A/B testing working
- [ ] Tests passing (80%+ coverage)
- [ ] Documentation complete

## Integration Points
- Issue #187: WorkCore Foundation + AiChatPro
- Issue #193: WorkCore Foundation + Chatbot
- Issue #199: WorkCore Foundation + AIAgent

## Example Use Cases
- E-commerce: Product inquiry prompts
- Field Services: Job description prompts
- Real Estate: Property inquiry prompts
- Fitness: Training plan prompts

---

## Issue #186: [WorkCore] WorkCoreWorkforceAssurance: Workforce, Compliance & NDIS

**URL**: https://github.com/masterleeaus/ai-extensions/issues/186
**State**: OPEN
**Labels**: workcore, high-priority, workforce, compliance

## Summary
Add WorkCoreWorkforceAssurance extension for workforce management, people management, attendance verification, rosters, compliance, assurance and NDIS-specific operations.

## Scope
- **Package**: `workcore-workforce-assurance`
- **Modules**: Workforce, People, AttendanceVerification, Rosters, Attendance, Compliance, Assurance, Credentials, NDIS
- **Dependencies**: WorkCore Shared Foundation (Issue #181)
- **Responsibility**: Workforce and people management, attendance tracking and verification, roster management, compliance monitoring, assurance auditing, credential management, NDIS support

## Features
- Workforce and people management
- Attendance verification and tracking
- Roster and scheduling management
- Compliance monitoring and reporting
- Assurance and audit trails
- Credential management
- NDIS-specific compliance support
- Background check integration

## Acceptance Criteria
- [ ] WorkCoreWorkforceAssurance extracted to `extensions/WorkCore_Platform/native-extensions/WorkCoreWorkforceAssurance`
- [ ] Workforce management operational
- [ ] Attendance verification functional
- [ ] Compliance tracking working
- [ ] NDIS support implemented
- [ ] Credential system operational
- [ ] Tests passing (80%+ coverage)
- [ ] Depends on Issue #181 (WorkCore Shared Foundation)
- [ ] Ready for integration with AI suites

## Integration with AI Suites
- [ ] AiChatPro HR operations (Issue #200)
- [ ] Chatbot HR assistant (Issue #201)
- [ ] AIAgent compliance automation (Issue #202)

## Resources
- [WorkCore Repository](extensions/WorkCore_Platform)
- [Workforce Assurance Module](extensions/WorkCore_Platform/native-extensions/WorkCoreWorkforceAssurance)

---

## Issue #185: [WorkCore] WorkCorePropertyOperations: Premises, Assets & Documents

**URL**: https://github.com/masterleeaus/ai-extensions/issues/185
**State**: OPEN
**Labels**: workcore, high-priority, properties, assets

## Summary
Add WorkCorePropertyOperations extension for premises management, asset tracking, document management and vertical operating profiles.

## Scope
- **Package**: `workcore-property-operations`
- **Modules**: Premises, Assets, Documents, VerticalOperations
- **Dependencies**: WorkCore Shared Foundation (Issue #181), TitanDocs (Issue #83)
- **Responsibility**: Property and premises management, asset tracking and maintenance, document management, vertical-specific configurations

## Features
- Premises and property management
- Asset registry and tracking
- Document management and collaboration
- Maintenance scheduling
- Vertical-specific operation profiles
- Property documentation

## Acceptance Criteria
- [ ] WorkCorePropertyOperations extracted to `extensions/WorkCore_Platform/native-extensions/WorkCorePropertyOperations`
- [ ] Premises management operational
- [ ] Asset tracking functional
- [ ] Document management working
- [ ] Vertical profiles configurable
- [ ] Tests passing (80%+ coverage)
- [ ] Depends on Issue #181 (WorkCore Shared Foundation)
- [ ] Docs integration with Issue #83 working
- [ ] Ready for integration with AI suites

## Integration with AI Suites
- [ ] AiChatPro property management (Issue #197)
- [ ] Chatbot asset information (Issue #198)
- [ ] AIAgent property automation (Issue #199)

## Resources
- [WorkCore Repository](extensions/WorkCore_Platform)
- [Property Operations Module](extensions/WorkCore_Platform/native-extensions/WorkCorePropertyOperations)

---

## Issue #184: [WorkCore] WorkCoreWorkOperations: Scheduling, Dispatch & Fleet

**URL**: https://github.com/masterleeaus/ai-extensions/issues/184
**State**: OPEN
**Labels**: dispatch, workcore, high-priority, operations

## Summary
Add WorkCoreWorkOperations extension for job management, scheduling, dispatch, recurring services, forms, repairs management and fleet operations.

## Scope
- **Package**: `workcore-work-operations`
- **Modules**: Operations, Scheduling, Dispatch, RecurringServices, Forms, Repairs, Fleet
- **Dependencies**: WorkCore Shared Foundation (Issue #181), TitanMapsIntelligence (Issue #61)
- **Responsibility**: Job and work order management, scheduling, field dispatch, recurring service management, forms and repairs, fleet management

## Features
- Job and work order management
- Advanced scheduling engine
- Field dispatch optimization
- Recurring service management
- Digital forms and inspections
- Repairs tracking and management
- Fleet and vehicle management

## Acceptance Criteria
- [ ] WorkCoreWorkOperations extracted to `extensions/WorkCore_Platform/native-extensions/WorkCoreWorkOperations`
- [ ] Scheduling engine operational
- [ ] Dispatch system functional
- [ ] Fleet management working
- [ ] Forms system integrated
- [ ] Tests passing (80%+ coverage)
- [ ] Depends on Issue #181 (WorkCore Shared Foundation)
- [ ] Maps integration with Issue #61 working
- [ ] Ready for integration with AI suites

## Integration with AI Suites
- [ ] AiChatPro operations integration (Issue #190)
- [ ] Chatbot scheduling assistant (Issue #193)
- [ ] AIAgent dispatch automation (Issue #196)

## Resources
- [WorkCore Repository](extensions/WorkCore_Platform)
- [Work Operations Module](extensions/WorkCore_Platform/native-extensions/WorkCoreWorkOperations)

---

## Issue #183: [WorkCore] WorkCoreCommercial: Finance, Payroll & Inventory

**URL**: https://github.com/masterleeaus/ai-extensions/issues/183
**State**: OPEN
**Labels**: workcore, high-priority, business-operations, finance

## Summary
Add WorkCoreCommercial extension for financial management, payroll, inventory, procurement, vault management and trust accounting operations.

## Scope
- **Package**: `workcore-commercial`
- **Modules**: Finance, Payroll, Inventory, Supply, TitanVault, TrustAccounting
- **Dependencies**: WorkCore Shared Foundation (Issue #181)
- **Responsibility**: Financial operations, payroll management, inventory tracking, procurement, secure vault operations, trust accounting

## Features
- Financial management and reporting
- Payroll and compensation
- Inventory and supply chain management
- Procurement operations
- Secure vault (TitanVault) for sensitive data
- Trust accounting for escrow and client accounts

## Acceptance Criteria
- [ ] WorkCoreCommercial extracted to `extensions/WorkCore_Platform/native-extensions/WorkCoreCommercial`
- [ ] Finance operations functional
- [ ] Payroll system working
- [ ] Inventory management operational
- [ ] Vault operations secured (Issue #145)
- [ ] Tests passing (80%+ coverage)
- [ ] Depends on Issue #181 (WorkCore Shared Foundation)
- [ ] Ready for integration with AI suites

## Integration with AI Suites
- [ ] AiChatPro financial integration (Issue #189)
- [ ] Chatbot commerce operations (Issue #192)
- [ ] AIAgent financial workflows (Issue #195)

## Resources
- [WorkCore Repository](extensions/WorkCore_Platform)
- [Commercial Module](extensions/WorkCore_Platform/native-extensions/WorkCoreCommercial)

---

## Issue #182: [WorkCore] WorkCoreBusinessNetwork: CRM, Catalogue & Knowledge

**URL**: https://github.com/masterleeaus/ai-extensions/issues/182
**State**: OPEN
**Labels**: crm, workcore, high-priority, business-operations

## Summary
Add WorkCoreBusinessNetwork extension for customer relationship management, product catalogue, support, knowledge base, reviews, territories and business intelligence integration.

## Scope
- **Package**: `workcore-business-network`
- **Modules**: CRM, Catalogue, Support, Knowledge, KnowledgeBase, Reviews, Territories, Feedback, Wizards, AI
- **Dependencies**: WorkCore Shared Foundation (Issue #181)
- **Responsibility**: Customer management, CRM operations, catalogue, support ticketing, knowledge management, reviews, territory management, business intelligence

## Features
- Customer and CRM management
- Product catalogue management
- Support ticketing and knowledge base
- Review and feedback management
- Territory assignment and intelligence
- Business wizard automation

## Acceptance Criteria
- [ ] WorkCoreBusinessNetwork extracted to `extensions/WorkCore_Platform/native-extensions/WorkCoreBusinessNetwork`
- [ ] CRM operations functional
- [ ] Catalogue management working
- [ ] Knowledge base operational
- [ ] Support system integrated
- [ ] Tests passing (80%+ coverage)
- [ ] Depends on Issue #181 (WorkCore Shared Foundation)
- [ ] Ready for integration with AI suites

## Integration with AI Suites
- [ ] AiChatPro CRM integration (Issue #188)
- [ ] Chatbot CRM capabilities (Issue #191)
- [ ] AIAgent business operations (Issue #194)

## Resources
- [WorkCore Repository](extensions/WorkCore_Platform)
- [Catalogue](extensions/WorkCore_Platform/native-extensions/WorkCoreBusinessNetwork)

---

## Issue #181: [WorkCore] WorkCore Shared Foundation: Tenancy, Permissions & Governance

**URL**: https://github.com/masterleeaus/ai-extensions/issues/181
**State**: OPEN
**Labels**: phase-0, foundation, workcore, high-priority

## Summary
Add WorkCore Shared Foundation extension to AI Extensions platform. This is the mandatory foundation for all WorkCore domain extensions providing tenancy, permissions, governed actions, read models, Rewind, outbox, host adapters, and configuration management.

## Scope
- **Package**: `workcore-shared-foundation`
- **Modules**: Shared system infrastructure
- **Dependencies**: None (foundation layer)
- **Responsibility**: Tenancy, permissions, governed actions, read models, Rewind, outbox, host adapters, configuration and historical migrations

## Integration Points
- TenantContext & Authorization (Issue #143)
- EventEnvelope & Idempotent Consumers (Issue #144)
- Credential Vault (Issue #145)
- Required by: All WorkCore domain extensions

## Acceptance Criteria
- [ ] WorkCore Shared Foundation extracted to `extensions/WorkCore_Platform/packages/workcore-shared-foundation`
- [ ] Tenancy isolation verified
- [ ] Permission model documented
- [ ] Governed actions implemented
- [ ] Rewind functionality working
- [ ] Outbox pattern implemented
- [ ] Tests passing (80%+ coverage)
- [ ] Integrated with TenantContext (#143)
- [ ] Integrated with EventEnvelope (#144)

## Integration with AI Suites
- [ ] AiChatPro platform AI integration (Issue #185)
- [ ] Chatbot PWA integration (Issue #186)
- [ ] AIAgent autonomous AI integration (Issue #187)

## Resources
- [WorkCore Repository](extensions/WorkCore_Platform)
- [WorkCore README](extensions/WorkCore_Platform/README.md)
- [Non-negotiable Architecture Rules](extensions/WorkCore_Platform/README.md#non-negotiable-architecture-rules)

---

## Issue #150: [URGENT] [Phase 0] Add Root Architecture Test Suite

**URL**: https://github.com/masterleeaus/ai-extensions/issues/150
**State**: OPEN
**Labels**: phase-0, critical-path, testing, architecture

## Summary
Add automated architecture validation to detect boot failures, collisions, and unsafe class shadowing.

## Problem
- No verification that all 78 extensions boot in dependency order
- No detection of duplicate routes, migrations, namespaces, service bindings
- No validation of semantic version constraints
- Chatbot's WorkCore compatibility shim (legacy mode) is manual; no automated detection if it's shadowing real host classes
- Breaking changes to extension contracts discovered only at runtime

## Solution
Build root architecture test suite that validates boot sequence, detects collisions, and enforces dependency constraints.

## Deliverables
- [ ] **Boot Sequence Test**
  - [ ] Boot every extension service provider in dependency order
  - [ ] Detect circular dependencies
  - [ ] Verify each provider registers cleanly
  - [ ] Check for missing dependencies

- [ ] **Collision Detection Tests**
  - [ ] Detect duplicate routes (same method + path)
  - [ ] Detect duplicate migrations (same timestamp + name)
  - [ ] Detect duplicate namespaces
  - [ ] Detect duplicate service container bindings
  - [ ] Report conflicts with clear remediation

- [ ] **WorkCore Class Shadowing Tests**
  - [ ] Detect if Chatbot shadows WorkCore domain classes
  - [ ] Verify legacy compatibility mode is opt-in
  - [ ] Warn if shadowing would occur

- [ ] **Semantic Version Constraint Tests**
  - [ ] Verify registry version constraints are satisfied
  - [ ] Detect version conflicts
  - [ ] Check semantic versioning compliance

- [ ] **Dependency Graph Validation**
  - [ ] Visualize extension dependency graph
  - [ ] Detect brittle extension-name coupling
  - [ ] Suggest migration to formal capability versioning

## Exit Criteria
- ✅ Test suite runs in CI/CD before any migrations
- ✅ Boot sequence validated for all 78 extensions
- ✅ Collisions detected and reported clearly
- ✅ No silent WorkCore shadowing allowed
- ✅ Dependency constraints validated

## Runs In
- CI/CD on every commit
- Pre-deployment verification

## Relates to
- #64 (Smoke and contract tests)
- #74 (Cross-suite integration paths)

## Effort
1 week

---

## Issue #148: [URGENT] [Phase 0] Add PhoneCallAgent Conformance Test Suite (25–40 tests)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/148
**State**: OPEN
**Labels**: voice, phase-0, critical-path, testing

## Summary
Add comprehensive test coverage for PhoneCallAgent webhook, call lifecycle, and booking integration.

## Problem
- **PhoneCallAgent has ZERO test coverage** — autonomous webhooks and booking actions unverified
- Webhook security and replay prevention untested
- Call state machine not validated
- Transcript/recording idempotency missing
- Booking action verification missing
- Multi-tenant isolation untested

## Solution
Build conformance test suite covering webhook security, call state machine, transcripts, and booking integration.

## Deliverables
- [ ] **Webhook Security Tests (5–7 tests)**
  - [ ] Valid webhook acceptance
  - [ ] Invalid signature rejection
  - [ ] Replay prevention
  - [ ] Tenant resolution
  - [ ] Rate limiting

- [ ] **Call State Machine Tests (5–7 tests)**
  - [ ] Inbound call acceptance
  - [ ] Outbound call initiation
  - [ ] Call state transitions
  - [ ] Call termination
  - [ ] Concurrent call handling
  - [ ] Call cancellation

- [ ] **Transcript & Recording Tests (4–6 tests)**
  - [ ] Transcript idempotency (no duplicates)
  - [ ] Recording deduplication
  - [ ] Transcript segment ordering
  - [ ] Recording retention policy
  - [ ] Consent tracking

- [ ] **Booking Action Tests (3–5 tests)**
  - [ ] Booking tool execution
  - [ ] Calendar synchronization
  - [ ] Availability verification
  - [ ] Booking confirmation
  - [ ] Cancellation handling

- [ ] **Usage & Analytics Tests (2–3 tests)**
  - [ ] Call duration tracking
  - [ ] Cost attribution
  - [ ] Usage reporting

- [ ] **Multi-Tenant Isolation Tests (3–4 tests)**
  - [ ] Cross-tenant data leakage prevention
  - [ ] Tenant-scoped call history
  - [ ] Tenant-scoped recordings

## Exit Criteria
- ✅ 25–40 tests pass
- ✅ Webhook security verified
- ✅ Call state machine validated
- ✅ Transcripts/recordings idempotent
- ✅ Booking actions verified
- ✅ Multi-tenant isolation confirmed

## Relates to
- #60 (Voice Engine)
- #64 (Smoke and contract tests)

## Effort
2 weeks

---

## Issue #146: [URGENT] [Phase 0] Add Webhook Verification & Replay Prevention Tests

**URL**: https://github.com/masterleeaus/ai-extensions/issues/146
**State**: OPEN
**Labels**: security, phase-0, critical-path, webhooks

## Summary
Establish webhook security baseline: signature verification, replay prevention, and tenant resolution.

## Problem
- Unsigned or replayable inbound webhooks triggering autonomous actions
- No verification that webhook sender is authentic
- No replay prevention (timestamp checks, nonce deduplication)
- No tenant resolution during webhook processing
- AIAgent, PhoneCallAgent, ChatbotVoice, and all 7 AIAgent add-ons lack webhook security tests

## Solution
Define shared `WebhookVerifier` contract and add comprehensive security tests.

## Deliverables
- [ ] Define `WebhookVerifier` contract:
  - Signature verification (HMAC-SHA256 or public key)
  - Timestamp validation (reject old requests)
  - Replay prevention (idempotency key or nonce ledger)
  - Tenant resolution (extract from headers or payload)
  - Rate limiting enforcement
- [ ] Implement verifier for each provider (Slack, WhatsApp, Telegram, etc.)
- [ ] Add test suite:
  - [ ] Valid signature acceptance
  - [ ] Invalid signature rejection
  - [ ] Timestamp validation (too old = reject)
  - [ ] Replay prevention (duplicate = reject)
  - [ ] Tenant resolution (multi-tenant isolation)
  - [ ] Rate limit enforcement

## Exit Criteria
- ✅ All webhook endpoints verify signature
- ✅ All webhooks check timestamp (reject > 5 min old)
- ✅ Replayed webhooks are rejected or deduplicated
- ✅ Webhook processing includes tenant resolution
- ✅ Webhook security tests pass for AIAgent, PhoneCallAgent, ChatbotVoice

## Affects (blocking)
- AIAgent, AIAgentGmail, AIAgentSlackChannel, AIAgentToolChatbot, AIAgentToolMarketingBot, AIAgentToolSocialMediaAgent, AIAgentWhatsappChannel
- PhoneCallAgent
- ChatbotVoice, ChatbotVoiceCall
- MarketingBot, SocialMediaAgent

## Relates to
- #64 (Smoke and contract tests)

## Effort
1–2 weeks

---

## Issue #145: [URGENT] [Phase 0] Implement Credential Vault References & Envelope Encryption

**URL**: https://github.com/masterleeaus/ai-extensions/issues/145
**State**: OPEN
**Labels**: security, phase-0, critical-path, foundation

## Summary
Replace secrets in configuration and events with vault references and envelope encryption.

## Problem
- Credentials copied into extension settings, logs, queue payloads, events
- Secrets exposed in tool results and database snapshots
- No credential rotation metadata
- API keys, OAuth tokens, webhook secrets stored unencrypted

## Solution
Implement vault reference contract and envelope encryption for all sensitive data.

## Deliverables
- [ ] Define `CredentialVaultReference` contract:
  - Vault name (e.g., AWS Secrets Manager, HashiCorp Vault)
  - Reference path or ID
  - Rotation metadata (rotated_at, next_rotation)
  - Access policy (tenant, service, permission level)
- [ ] Implement envelope encryption for credential storage
- [ ] Add secret redaction to logs and audit trails
- [ ] Create credential lifecycle tests
- [ ] Add rotation metadata to tracking

## Exit Criteria
- ✅ No secrets in logs, events, queue payloads, or tool results
- ✅ All API keys use vault references only
- ✅ Secrets absent from extension configuration snapshots
- ✅ Envelope encryption tests pass
- ✅ Secret redaction works in audit logs

## Relates to
- #54 (WorkCore gateways)
- #75 (Audit and observability)

## Effort
1 week

---

## Issue #144: [URGENT] [Phase 0] Implement EventEnvelope & Idempotent Event Consumers

**URL**: https://github.com/masterleeaus/ai-extensions/issues/144
**State**: OPEN
**Labels**: security, phase-0, critical-path, foundation

## Summary
Establish event structure and idempotency guarantees for durable, reliable event processing.

## Problem
- Queue jobs, webhooks, event consumers lack idempotency keys
- Duplicate events can trigger duplicate charges, duplicate actions, or inconsistent state
- No standardized event structure across extensions
- Event tracing is not correlated across services

## Solution
Define `EventEnvelope` contract with immutable event ID, correlation/causation IDs, and idempotency key. Implement idempotent event-consumer pattern.

## Deliverables
- [ ] Define `EventEnvelope` schema:
  - Immutable event ID (UUID)
  - Event type and schema version
  - Tenant ID
  - Aggregate type and ID
  - Occurred-at timestamp
  - Actor identity
  - Correlation and causation IDs
  - Idempotency key
  - Compact payload (no full models, credentials, or documents)
- [ ] Implement idempotency ledger (deduplication by idempotency key)
- [ ] Define event-consumer test fixtures
- [ ] Add event replay prevention tests

## Exit Criteria
- ✅ Every event carries immutable ID and idempotency key
- ✅ Event consumers are idempotent (retries are safe)
- ✅ Replayed webhook and ingestion requests produce identical results
- ✅ Correlation tracing works across services
- ✅ Idempotency tests pass for all event-consuming extensions

## Relates to
- #75 (Audit and observability)
- #64 (Smoke and contract tests)

## Effort
1–2 weeks

---

## Issue #143: [URGENT] [Phase 0] Implement TenantContext & Authorization Policies

**URL**: https://github.com/masterleeaus/ai-extensions/issues/143
**State**: OPEN
**Labels**: security, phase-0, critical-path, foundation

## Summary
Establish tenant isolation and authorization policies as the foundation for cross-extension security.

## Problem
- Cross-tenant data leakage risk through missing tenant filters in queues, retrieval, events, webhooks
- No authorization policy for tool execution, workflow actions, connector usage, or knowledge ingestion
- Current: tenant ID not enforced on authoritative domain records and events

## Solution
Implement shared `TenantContext` and authorization policy interfaces that all extensions must use.

## Deliverables
- [ ] Define `TenantContext` contract (tenant ID, user identity, actor, permissions, policy version)
- [ ] Define `AuthorizationPolicy` interfaces for tools, workflows, connectors, knowledge sources
- [ ] Add tenant filters to all query repositories and event consumers
- [ ] Add `tenant_id` to every authoritative domain record and event
- [ ] Implement tenant resolution for webhook processing
- [ ] Add cross-tenant isolation tests

## Exit Criteria
- ✅ All queries include tenant filter
- ✅ All events carry tenant ID
- ✅ Webhook processing verifies tenant resolution
- ✅ Cross-tenant repository tests pass
- ✅ No query results leak across tenant boundaries

## Relates to
- #54 (WorkCore gateways)
- #75 (Audit and observability)

## Effort
1–2 weeks

---

## Issue #76: [LOW] [Documentation] Make the extension audit reproducible, evidence-linked and generated from source

**URL**: https://github.com/masterleeaus/ai-extensions/issues/76
**State**: OPEN

Parent: #6
Depends on: #7, #12, #37, #64

## Problem

The existing `docs/` catalogue is a strong inventory, but it does not yet provide a reproducible source audit with commit provenance, exact evidence links, dependency graphs, route/table ownership or generated security matrices. Static generation must not be described as proof of runtime behaviour.

## Scope

Turn the static documentation into an evidence-backed architecture record generated from the repository and verified in CI, while clearly separating source-analysis evidence from complete-host runtime evidence.

## Required outputs

- scanned repository and commit SHA;
- generation timestamp and tool version;
- extension/category inventory;
- manifest and dependency graph;
- route/middleware/route-name ownership report;
- migration/table ownership report;
- namespace and service-binding report;
- provider and Credential Vault reference matrix;
- webhook verification/idempotency matrix;
- tenant/authorization matrix;
- canonical data-authority map;
- standalone versus embedded compatibility map;
- test classification and coverage by extension;
- TODO/FIXME and risky-process/network-operation report.

## Requirements

- Link every finding to exact repository files, classes, routes or migrations.
- Distinguish confirmed source defects, probable defects, architectural risks, runtime-unverified assumptions and recommendations.
- Separate source files from generated reports and historical merge artifacts.
- Generate machine-readable JSON/CSV plus Markdown summaries.
- Fail CI when generated architecture reports are stale.
- Add ADRs for authority boundaries and major migration decisions.
- Maintain a risk register with severity, owner, status, issue and remediation evidence.
- Link runtime assertions to complete-host tests from #64 and #74 rather than inferring them from static analysis.

## Acceptance criteria

- A clean checkout can regenerate the same inventory and ownership reports.
- Reports identify the scanned commit and have no unexplained count drift.
- Every critical/high finding links to a GitHub issue and exact evidence.
- Dependency, route and table ownership diagrams are generated, not manually guessed.
- CI detects stale generated documentation.
- New extensions automatically appear in the audit with required security/test fields.
- No generated report labels an extension production-ready without current runtime evidence.

---

## Issue #75: [URGENT] [Foundation] Add shared audit, observability, correlation tracing and retention controls

**URL**: https://github.com/masterleeaus/ai-extensions/issues/75
**State**: OPEN

Parent: #6
Depends on: #24, #31, #44, #51

## Problem

The ecosystem has extension-specific logs, receipts, usage records and diagnostics with inconsistent correlation, redaction and retention. Production operation requires one traceable view across requests, webhooks, tools, workflows, provider attempts, queues and domain writes.

## Scope

Create shared observability contracts and storage/telemetry adapters without centralizing canonical domain content.

## Requirements

- Generate and propagate correlation and causation IDs across HTTP, webhook, queue, event, tool, workflow and provider boundaries.
- Define structured audit events with tenant, actor, action, resource, policy and outcome.
- Add metrics for latency, errors, retries, queues, dead letters, provider health, budgets and rate limits.
- Add tracing spans for major shared-runtime operations.
- Enforce secret, personal-data and canonical-content redaction.
- Define retention, deletion and access policy by telemetry class.
- Separate operational telemetry from canonical transcripts/documents/messages.
- Add tenant-safe diagnostics and health endpoints.
- Alert on replay attempts, cross-tenant denials, credential failures, migration divergence and repeated tool failures.
- Make receipts linkable to events, provider attempts and WorkCore outcomes.

## Acceptance criteria

- A single correlation ID traces a request through tool/workflow/provider/domain boundaries.
- Secrets and restricted payloads are absent from logs, events and traces.
- Retention policies are enforced and testable.
- Tenant users cannot access another tenant's diagnostics.
- Critical security and migration conditions generate actionable alerts.
- Observability failure does not silently permit an unsafe side effect.
- Dashboards/report queries can distinguish denied, retryable, failed, timed-out and completed outcomes.

---

## Issue #74: [HIGH] [Testing] Add cross-suite integration paths for chat, workflows, knowledge, voice and WorkCore

**URL**: https://github.com/masterleeaus/ai-extensions/issues/74
**State**: OPEN

Parent: #6
Merged infrastructure: PR #110
Depends on: #11, #21, #22, #23, #47, #49, #54, #58, #60, #61, #63, #64, #65, #67, #69, #70, #71, #136

## Objective

Prove the target authority boundaries through complete-host end-to-end integration tests rather than isolated class or package contracts.

## Required production paths

### Governed operational action

```text
AIChatPro or Chatbot request
→ shared tool definition
→ permission/risk/approval
→ WorkCore action
→ action receipt
→ Chatbot/offline sync update
```

### Interaction Engine web/offline command

```text
AIWebChat or Chatbot client command
→ merged Interaction Engine authenticated request
→ tenant/actor authorization
→ governed tool or WorkCore gateway
→ event/outbox
→ delta/offline sync acknowledgement
```

### Durable workflow delegation

```text
Chatbot immediate orchestration
→ AIAgent durable workflow
→ delayed/retried action
→ status and receipt callback
```

### Shared knowledge

```text
Knowledge source
→ secure ingestion
→ assignment
→ Chatbot retrieval
→ AIAgent retrieval
→ stable citation verification
```

### Voice booking

```text
Verified call event
→ transcript segment
→ governed booking tool
→ Booking Engine
→ provider synchronization
→ confirmation
→ derived memory reference
```

### Commerce

```text
Conversation request
→ shared commerce contract
→ standalone ChatbotEcommerce policy
→ governed checkout/order handoff
→ WorkCore mapping
```

## Requirements

- Use realistic tenant, user, agent, conversation, chatbot, device and customer contexts.
- Test success, denial, approval, retry, replay, timeout, cancellation, provider outage and rollback.
- Assert correlation/causation IDs across every boundary.
- Assert no cross-tenant record can be retrieved or mutated.
- Assert no credentials or full canonical content appear in events/receipts.
- Include offline outbox, conflict, reconnect and delta-reconciliation behaviour.
- Verify browser/offline commands never authorize side effects locally.
- Run against supported provider fakes/contracts, not live credentials.
- Treat PR #110 package tests as prerequisites, not evidence that the complete host path passes.

## Acceptance criteria

- Every required path passes in CI with deterministic complete-host fixtures.
- Chatbot and AIWebChat use the same compatible Interaction Engine contracts without duplicate provider/binding ownership.
- Failures identify the exact boundary and correlation trace.
- Replayed messages, commands and events do not duplicate side effects.
- Legacy adapters and new engines can be compared during migration.
- Tests verify authority ownership and state transitions, not merely HTTP status codes.
- The suite becomes a release gate for Interaction Engine, shared-engine, AIAgent, AIChatPro and Chatbot changes.

---

## Issue #71: [URGENT] [Phase 0] Add feature flags, migration state, shadow reads and rollback controls

**URL**: https://github.com/masterleeaus/ai-extensions/issues/71
**State**: OPEN

Parent: #6
Depends on: #7, #24, #44

## Problem

The upgrade programme requires incremental migration across many extension identities and canonical stores. Without explicit migration state, feature flags and rollback controls, dual systems can drift or cut over irreversibly.

## Scope

Create shared migration-control infrastructure for bounded-domain engine rollouts.

## Requirements

- Tenant/global feature flags with typed definitions and ownership.
- Migration state per tenant, extension, capability and data domain.
- Backfill checkpoints, resumable cursors and reconciliation reports.
- Shadow-read support with agreement, latency and quality comparison.
- Time-bounded dual writes only where rollback requires them.
- Cutover, freeze, rollback and archive states.
- Kill switches for unsafe provider, webhook, tool and ingestion paths.
- Audit every flag/state change with actor and reason.
- Prevent incompatible state transitions.
- Add health gates and automated rollback thresholds where appropriate.
- Define retention windows for legacy tables and adapters.
- Expose diagnostics without leaking tenant data or secrets.

## Acceptance criteria

- A migrated capability can be enabled for one tenant without affecting others.
- Backfills resume safely after interruption and reconcile counts/hashes/relationships.
- Shadow-read differences are measurable before cutover.
- Rollback restores canonical reads/writes during the defined window.
- Dual-write mode has an owner, expiry and divergence alarm.
- Kill switches disable unsafe execution immediately.
- Tests cover every valid and invalid migration-state transition.

---

## Issue #69: [HIGH] [Phase 6] Execute progressive migrations with shadow validation and reversible cutover

**URL**: https://github.com/masterleeaus/ai-extensions/issues/69
**State**: OPEN

Parent: #6
Depends on: #7, #11, #24, #37, #44, #47, #49, #54, #55, #58, #60, #61, #63, #64, #65, #71

## Problem

The repository contains legacy tables, routes, embedded compatibility modules and extension-specific stores that cannot be replaced safely in one release. Broad rewrites risk data loss, broken marketplace identities, authority conflicts and irreversible cutovers.

## Boundary

Issue #71 owns the reusable feature-flag, migration-state, shadow-read and rollback infrastructure. This issue owns execution of real extension/domain migrations using that infrastructure.

## Migration sequence

1. Introduce the new contract or engine behind tenant/extension feature flags.
2. Backfill from one declared legacy authority.
3. Reconcile counts, hashes, ownership, relationships and permissions.
4. Shadow-read old and new paths.
5. Compare result agreement, quality, latency and cost.
6. Use time-bounded dual writes only where rollback safety requires them.
7. Cut over one extension/tenant cohort at a time.
8. Freeze legacy writes after stable observation.
9. Retain tested rollback during the published migration window.
10. Archive legacy data under retention policy.

## Requirements

- Define migration plans per tenant, extension and bounded domain using #71 state contracts.
- Produce auditable backfill, reconciliation and exception reports.
- Use idempotent resumable backfills with checkpoints.
- Define explicit conflict handling and manual repair workflows.
- Add adapter deprecation telemetry and removal criteria.
- Do not silently delete legacy data or public routes.
- Run cross-suite integration paths for chat, voice, knowledge, booking, commerce and WorkCore actions.

## Acceptance criteria

- One selected legacy training controller migrates to Knowledge Engine with unchanged UI contract and tested rollback.
- Backfill reruns do not duplicate canonical records.
- Count/hash/permission mismatches block cutover and produce actionable reports.
- Feature flags can disable new reads/writes without losing accepted operations.
- Dual-write periods are time-bounded, observable and removable.
- Rollback restores the prior path within the documented window.
- Each migrated extension has completion, observation, freeze and archival criteria.
- End-to-end tests in #74 prove the migrated production paths.

---

## Issue #68: [URGENT] [Phase 2] Formalize the shared Skill Runtime, package security and version lifecycle

**URL**: https://github.com/masterleeaus/ai-extensions/issues/68
**State**: OPEN

Parent: #6
Depends on: #7, #24, #28, #31, #40, #44, #49

## Problem

Skills exist in AIChatPro and the embedded Chatbot TitanAI runtime with overlapping models, imports, tool bridges, package validation and user/company assignment. Skills must remain distinct from tools and require a formal package and release lifecycle.

## Scope

Create a shared Skill Runtime that owns skill manifests, versions, resources, installation and execution policy while product surfaces retain their own UIs.

## Requirements

- Stable skill identity, semantic version and immutable release records.
- Typed manifest for instructions, domain strategy, examples, required tools, permissions and compatibility.
- Secure import staging for GitHub/archive sources.
- File allow-lists, archive traversal protections, size/resource limits and integrity hashes.
- Package provenance, signer/author metadata and verification status.
- Tenant/company/user installation and visibility policy.
- Explicit tool dependencies resolved through the Unified Registry.
- Version pinning, update policy, rollback and deprecation.
- Skill evaluation, usage and quality telemetry without copying secrets.
- Adapters for AIChatProSkills and Chatbot/SystemAIChat skills.
- Prevent a skill package from registering arbitrary executable PHP as a tool.

## Acceptance criteria

- A skill can be packaged once and installed in AIChatPro and Chatbot through adapters.
- Tool requirements are resolved by typed ID/version and permissions.
- Malformed, oversized or unsafe packages are rejected before installation.
- Installed versions remain reproducible and can be rolled back.
- Tenant/private/public visibility is enforced.
- Architecture tests preserve the distinction between skill instructions and executable tools.
- Existing bundled field-service skills migrate without feature loss.

---

## Issue #66: [URGENT] [Security] Isolate VideoEditor FFmpeg execution with strict resource and path controls

**URL**: https://github.com/masterleeaus/ai-extensions/issues/66
**State**: OPEN

Parent: #6
Depends on: #7, #24, #42, #64

## Problem

VideoEditor legitimately executes FFmpeg through `proc_open`, but media paths, work directories, process lifetime, stderr capture and worker resource consumption remain security and reliability boundaries. Escaped shell arguments alone do not prove safe isolation.

## Scope

Move media rendering into a hardened worker boundary with explicit command construction, allow-listed files/codecs and bounded resources.

## Requirements

- Execute FFmpeg as an argument vector where supported; avoid shell interpretation.
- Allow-list executable path, codecs, formats, filters and command templates.
- Resolve and validate every input/output path under tenant/job-owned directories.
- Reject symlinks, traversal, device files and unexpected filesystem roots.
- Apply CPU, memory, wall-clock, output-size, process-count and disk quotas.
- Run under a low-privilege isolated worker/container profile with no network unless explicitly required.
- Stream and cap stderr/stdout rather than accumulating unbounded output.
- Add heartbeat, cancellation, stale-process termination and orphan cleanup.
- Preserve job progress and idempotent completion state.
- Sanitize diagnostic errors before exposing them to users/logs.
- Use the shared secure media fetcher before any remote source enters the worker.

## Acceptance criteria

- Injection, traversal, symlink and unsupported-filter fixtures are rejected before process start.
- A malicious or malformed media file cannot exceed configured worker limits.
- Timed-out/cancelled jobs terminate child processes and clean partial outputs.
- Valid multi-track exports remain deterministic under retry.
- Workers cannot access another tenant's source or output directories.
- CI includes command-construction, resource-limit and cleanup regression tests.
- Production documentation defines the required worker/container security profile.

---

## Issue #65: [HIGH] [Phase 5] Define shared Commerce contracts, provider mappings and WorkCore authority boundaries

**URL**: https://github.com/masterleeaus/ai-extensions/issues/65
**State**: OPEN

Parent: #6
Related product roadmap: #59
Depends on: #24, #25, #28, #31, #44, #49, #51, #54, #58, #61, #64, #71

## Problem

Commerce behaviour appears in ChatbotEcommerce, provider-specific tool handlers and Chatbot schemas while WorkCore remains authoritative for operational business records. Shared contracts are required, but creating a second competing commerce store would conflict with the accepted standalone ChatbotEcommerce product boundary.

## Boundary

- `ChatbotEcommerce` remains the standalone installable commerce product and current implementation authority for its ecommerce-specific state.
- WorkCore owns operational business records assigned to WorkCore, including configured order, invoice, inventory and payment authorities.
- This issue defines contracts and mappings; it does not authorize destructive replacement of the standalone extension.
- Issue #23 owns adoption of these contracts by ChatbotEcommerce.

## Scope

- Define tenant-safe typed commands/results for catalog search, cart mutation, checkout preparation, order lookup and provider synchronization.
- Define explicit authority rules for carts, pricing snapshots, checkout sessions, marketplace state, orders, inventory, invoices and payments.
- Define provider-neutral Shopify and WooCommerce capability contracts.
- Use Customer Identity references rather than copied customer records.
- Require governed side effects, approval, receipts and idempotency.
- Define price/version snapshots and inventory conflict handling.
- Store only Credential Vault references and verified provider events.
- Provide adapters callable from chat, voice and workflows without bypassing ChatbotEcommerce policy.
- Keep UnifiedMemory limited to preferences and references, never carts or orders.

## Acceptance criteria

- One authority is declared for every commerce record and state transition.
- Chat, voice and workflows receive equivalent contract behaviour.
- Duplicate calls and provider callbacks do not duplicate checkout sessions, orders or charges.
- Provider and WorkCore mappings remain traceable and tenant-scoped.
- Price or availability changes are detected before checkout.
- No second competing canonical commerce store is introduced.
- Contract and authority tests cover retries, provider outage, stale prices, inventory conflicts and idempotency.

---

## Issue #64: [URGENT] [Phase 0] Add native smoke, security and contract tests for every selected extension

**URL**: https://github.com/masterleeaus/ai-extensions/issues/64
**State**: OPEN

Parent: #6
Depends on: #7, #24, #31, #44

## Problem

The canonical Chatbot package has meaningful bundled tests, but most selected extensions—including AIAgent and AIChatPro—have no native test trees. The Pass 3 standalone verifier is useful package evidence but cannot prove the complete host's provider boot, authorization, migrations, queues, schedules, webhooks or tenant isolation.

## Scope

Create a repository-wide extension test programme with a minimum native conformance baseline for all 78 selected extensions.

## Minimum per-extension suite

- manifest and provider boot smoke test;
- route authorization/public-route classification test;
- migration fresh/rollback/repeat-safety test;
- tenant-isolation test for authoritative records;
- credential and log-redaction test where applicable;
- queue/job retry and idempotency test where applicable;
- webhook signature/replay test where applicable;
- uninstall/disable/re-enable behavior test;
- compatibility adapter contract test;
- dependency/version resolution test.

## Requirements

- Run inside a representative complete MagicAI/WorkCore host fixture.
- Keep package-level lint/verification and host-level tests distinct.
- Provide reusable fixtures for providers, webhooks, queues, clocks and tenant contexts.
- Add mutation/failing fixtures proving architecture and security checks detect regressions.
- Produce machine-readable coverage/conformance reports per extension.
- Block production-readiness claims unless the required gates have fresh evidence.

## Acceptance criteria

- Every selected extension has at least one native boot test and applicable security/ownership tests.
- AIAgent and AIChatPro gain native unit and host-integration suites.
- CI reports missing mandatory tests as failures, not warnings.
- Cross-tenant, replay, retry, rollback and secret-leak fixtures are reproducible.
- Package verifier, PHP lint, architecture tests and host tests run in a documented dependency order.
- The repository can state exactly which extensions are verified, partially verified or unverified.

---

## Issue #63: [HIGH] [Phase 5] Build a canonical Booking Engine with provider adapters and governed actions

**URL**: https://github.com/masterleeaus/ai-extensions/issues/63
**State**: OPEN

Parent: #6
Depends on: #20, #24, #25, #28, #31, #44, #49, #51, #54, #58, #61, #64, #71

## Problem

Booking behaviour is split across ChatbotBooking, PhoneCallAgent tools, Chatbot governance coordination and Calendly/Cal.com integrations. Provider-specific records and tool behaviour risk becoming competing booking authorities.

## Scope

Create a separate Booking Engine that owns appointment state and provider synchronization while exposing governed operations to chat, voice and workflows. Issue #22 owns migration of the ChatbotBooking product surface.

## Canonical responsibilities

- services and appointment types;
- availability queries;
- bookings and attendees;
- status and lifecycle transitions;
- rescheduling and cancellation;
- provider mappings and synchronization;
- synchronization attempts/conflicts;
- audit and action receipts.

## Requirements

- Tenant-safe typed commands for availability, create, confirm, reschedule and cancel.
- Explicit WorkCore authority mapping for operational job/appointment records.
- Provider adapters for Calendly and Cal.com through the Connector Runtime.
- Idempotent provider synchronization and verified webhook handling.
- Customer Identity references for attendees.
- Governed side effects with permissions, approval, budgets and receipts.
- Consistent timezone, locale and daylight-saving handling.
- Conflict detection and reversible reconciliation.
- PhoneCallAgent booking tools use the same contracts.
- UnifiedMemory may store preferences, not canonical bookings.

## Acceptance criteria

- Chat and voice invoke the same booking operations and receive equivalent results.
- Duplicate requests and provider callbacks do not duplicate appointments.
- Provider conflicts are visible and repairable.
- Cancellation/rescheduling remains synchronized and auditable.
- Cross-tenant booking access and provider mappings are impossible.
- Tests cover timezone edges, retries, provider outage, stale availability and conflicting updates.

---

## Issue #61: [HIGH] [Phase 5] Build the shared Connector Runtime and migrate Gmail, Slack and WhatsApp adapters

**URL**: https://github.com/masterleeaus/ai-extensions/issues/61
**State**: OPEN

Parent: #6
Depends on: #24, #25, #31, #40, #44, #49, #51, #58, #64, #71

## Problem

External-system authentication, webhook registration, inbound normalization, outbound dispatch, retries, rate limits, health and dead-letter behaviour are duplicated across Gmail, Slack, WhatsApp, Telegram, Meta, social, voice and provider-specific extensions.

This issue is the canonical Connector Runtime authority. Closed issue #57 is a duplicate and must not be used as a dependency.

## Runtime responsibilities

- connector instance installation and lifecycle;
- Credential Vault references and OAuth refresh;
- webhook registration and verified inbound normalization;
- observable outbound dispatch;
- retries, rate-limit handling and dead letters;
- provider/channel health and delivery receipts;
- consent, quiet-hour and customer-identity enforcement.

## Requirements

- Define typed inbound and outbound message/event contracts.
- Keep provider-specific payload parsing in adapters.
- Add connector capability manifests and semantic compatibility.
- Use the shared verification boundary from #25 for inbound requests.
- Add message correlation, provider-attempt and external-ID mappings.
- Add bounded payload/media handling and cancellation where supported.
- Add pause, revoke, reconnect and credential-rotation flows.
- Preserve consent, channel preference and Customer Identity references.
- Migrate Gmail, Slack and WhatsApp as the first conformance adapters.
- Preserve existing configuration, inboxes and channel workflows through adapters.

## Acceptance criteria

- Gmail, Slack and WhatsApp pass the same connector conformance suite.
- Repeated inbound events and outbound requests are idempotent.
- Outbound retries are observable and do not duplicate confirmed delivery.
- Connector outages and rate limits surface health state and controlled backoff.
- Revoked credentials stop dispatch immediately.
- Credentials never enter normalized events or delivery receipts.
- Cross-tenant connector IDs, messages and webhook events are rejected.
- Migration reconciliation and rollback are tested.

---

## Issue #60: [HIGH] [Phase 3] Build the capability-composed Voice Engine and migrate PhoneCallAgent

**URL**: https://github.com/masterleeaus/ai-extensions/issues/60
**State**: OPEN

Parent: #6
Depends on: #20, #24, #25, #31, #44, #47, #49, #51, #55, #58, #64, #71

## Problem

PhoneCallAgent, ChatbotVoice, ChatbotVoiceCall and related realtime/voice extensions duplicate call configuration, provider sessions, training, transcripts, histories, usage and lifecycle state. Twilio, ElevenLabs and OpenAI are not interchangeable full-stack providers.

This issue owns the canonical Voice Engine. Issue #21 owns migration of ChatbotVoice and ChatbotVoiceCall.

## Provider capabilities

- `TelephonyProvider`
- `RealtimeTransportProvider`
- `SpeechRecognitionProvider`
- `ConversationProvider`
- `SpeechSynthesisProvider`
- `RecordingProvider`

## Canonical records

- calls and sessions;
- participants;
- transcript segments;
- recordings;
- lifecycle events;
- provider attempts;
- usage and costs;
- consent and disclosure;
- redaction state;
- post-call analysis.

## Requirements

- Tenant-safe call lifecycle with stable external mappings.
- Capability-level health and controlled fallback.
- Idempotent verified webhook/event processing.
- Typed transcript segments and ordering.
- Recording consent, retention, deletion and redaction policy.
- Shared usage accounting and provider-attempt tracing.
- Governed tool execution during calls.
- Post-call summary and explicit memory-promotion policy.
- Migrate PhoneCallAgent as the first telephony product adapter.
- Full transcripts remain canonical in Voice Engine; UnifiedMemory receives derived facts only.

## Acceptance criteria

- A call can be initiated, connected, transcribed, metered, audited and summarized through the shared engine.
- Provider degradation occurs by capability with traceable attempts.
- Duplicate provider callbacks do not duplicate calls or transcript segments.
- Consent and retention rules are enforced and tested.
- Cross-tenant call, recording and transcript access is impossible.
- PhoneCallAgent remains compatible through an explicit adapter and rollback path.

---

## Issue #59: [HIGH] [Epic] ChatbotEcommerce v4.9.0 → v6.0.0 completion roadmap

**URL**: https://github.com/masterleeaus/ai-extensions/issues/59
**State**: OPEN

Related architecture programme: #6

## Goal

Track all remaining work required to move the merged `extensions/ChatbotEcommerce` v4.9.0 package from standalone validation to controlled pilot, full commercial capability and v6.0.0 general availability.

## Verified baseline

- `extensions/ChatbotEcommerce/extension.json` on `main` reports version 4.9.0.
- PR #5 is merged and introduced the isolated v4.9.0 package overlay.
- Standalone package checks are useful evidence but do not replace complete MagicAI/WorkCore host tests.

## Architecture boundary

- `ChatbotEcommerce` remains one standalone installable product.
- #65 defines shared commerce contracts, provider mappings and WorkCore authority boundaries without creating a second competing commerce store.
- #23 adopts those shared contracts while preserving the standalone product, APIs and migration history.
- WorkCore remains authoritative only for operational records explicitly assigned to it.
- Ecommerce-specific carts, provider snapshots, approvals and settlement projections remain governed within ChatbotEcommerce unless an explicitly approved migration changes ownership.

## Required architecture alignment

- [ ] #65 — Shared commerce contracts and authority map
- [ ] #23 — Integrate standalone ChatbotEcommerce with those contracts

## Execution order

### Immediate pilot gate
- [ ] #34 — Complete MagicAI host integration and controlled-pilot gate

### Commerce intelligence
- [ ] #38 — Pass 21: Seller analytics and commerce intelligence (v5.0.0)
- [ ] #39 — Pass 22: Predictive shopping and replenishment (v5.1.0)

### Rich customer experience
- [ ] #41 — Pass 23: Visual and voice commerce (v5.2.0)
- [ ] #43 — Pass 24: Customer communications automation and SLA management (v5.3.0)

### Direct provider integrations
- [ ] #45 — Pass 25: eBay direct adapter (v5.4.0)
- [ ] #46 — Pass 26: Etsy direct adapter (v5.5.0)
- [ ] #48 — Pass 27: Amazon Selling Partner adapter (v5.6.0)
- [ ] #50 — Pass 28: Direct payment and BNPL provider adapters (v5.7.0)

### Agentic, local and enterprise commerce
- [ ] #52 — Pass 29: Agentic Commerce Protocol v2 (v5.8.0)
- [ ] #53 — Pass 30: Privacy-first local intelligence and offline read mode (v5.9.0)
- [ ] #56 — Pass 31: Enterprise governance and v6.0.0 general availability

## Global implementation rules

- Keep one standalone `ChatbotEcommerce` extension.
- Preserve Shopify, WooCommerce and legacy API compatibility.
- Use additive, repeat-safe migrations.
- Never store or expose raw payment, bank, marketplace or provider credentials.
- Keep marketplace checkout authoritative and never bypass platform policy controls.
- Keep financial, refund, cancellation and bulk actions approval-gated.
- Never give customer or support actors seller-management authority.
- Require verified identity before exposing private order or payment data.
- Keep predictive shopping opt-in and disabled by default.
- Preserve prior files, APIs, tests and migration history unless compatibility is proven.
- Use #24, #31, #49, #58, #61, #64 and #71 rather than creating product-specific replacements for tenancy, credentials, tools, identity, connectors, testing or migration control.

## Per-issue release gate

Every child issue must include:

- failing tests written before implementation;
- all historical regression scripts passing;
- PHP syntax validation;
- package schema and OpenAPI validation where applicable;
- migration idempotency and data-preservation checks;
- baseline archive comparison with no unintended file loss;
- ZIP integrity and SHA-256 verification for release artifacts;
- clear disclosure of complete-host or provider-dependent tests not run.

## Completion definition

This epic closes only when #56 is complete, #23 and #65 are reconciled, ChatbotEcommerce v6.0.0 has no unresolved critical/high findings, passes the complete host suite, has deterministic lifecycle and upgrade behaviour, and ships complete operator/API/provider documentation.

---

## Issue #58: [HIGH] [Phase 4] Build the tenant-safe Customer Identity Engine and migrate product identity surfaces

**URL**: https://github.com/masterleeaus/ai-extensions/issues/58
**State**: OPEN

Parent: #6
Depends on: #24, #31, #44, #54, #55, #64, #71

## Problem

Customer information is fragmented across Chatbot conversations and tags, voice callers, Gmail senders, WhatsApp profiles, booking attendees, ecommerce customers, campaign contacts and social identities. Automatic merging without provenance can join the wrong people, destroy channel-specific context or violate consent and retention rules.

This issue is the canonical Customer Identity authority. Closed issue #62 is a duplicate and must not be used as a dependency.

## Canonical responsibilities

- customers and source identities;
- contact points and channel profiles;
- external provider mappings;
- consent and communication preferences;
- tags and attributes;
- interaction references;
- merge candidates, decisions and reversal audits.

## Requirements

- Preserve separate external identities and provider mappings.
- Generate merge candidates with confidence, evidence and provenance.
- Require policy/approval for destructive or high-impact merges.
- Make every merge explainable and reversible.
- Maintain consent, communication preference, retention and deletion state per channel/contact point.
- Add tenant-safe lookup contracts for chat, voice, email, WhatsApp, booking, commerce, campaigns and social channels.
- Keep full domain interactions in their authoritative engines; store references in the identity timeline.
- Route operational customer promotion/update through the WorkCore gateway.
- Add adapters and reversible backfills for existing product identity surfaces.
- Add duplicate detection without exposing cross-tenant existence.

## Acceptance criteria

- The same person can be recognized across at least two channels without irreversible automatic merging.
- Every merge records evidence, confidence, actor, policy version and rollback data.
- Consent withdrawal is enforced across connector and campaign operations.
- Tenant A cannot enumerate or match Tenant B identities.
- Backfill reruns do not duplicate customers, contact points or external mappings.
- Existing inbox, tag, caller, attendee and customer workflows remain compatible through adapters.
- Tests cover false-positive matches, shared phones/emails, changed contact points, merge reversal, deletion and cross-tenant denial.

---

## Issue #56: [MEDIUM] [ChatbotEcommerce Pass 31] Enterprise governance and v6.0.0 general availability

**URL**: https://github.com/masterleeaus/ai-extensions/issues/56
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #34, #38, #39, #41, #43, #45, #46, #48, #50, #52, #53, #58, #61, #64, #65, #71, #74

## Objective

Complete the final governance, compliance, resilience and operator-readiness gate for ChatbotEcommerce v6.0.0 after all roadmap passes, architecture alignment and complete-host integration paths are proven.

## Deliverables

- Complete a current architecture/compliance audit against the shipped package and accepted #6/#59 authority boundaries.
- Add enterprise role/permission templates and delegated approval limits.
- Add data export, deletion, retention, legal hold and audit-signature workflows.
- Complete full host boot, database, queue, scheduler, verified-webhook and HTTP integration suites.
- Add performance, load, fault-injection and long-running reconciliation tests.
- Validate upgrades from every supported historical ecommerce version.
- Document backup, restore, disaster recovery and rollback procedures.
- Produce a stable operator handbook, API reference and provider capability matrix.
- Complete security, privacy, tenancy, accessibility and operational-readiness reviews.
- Verify all shared foundation dependencies and cross-suite tests remain compatible.

## Acceptance criteria

- No unresolved critical or high findings remain in the release scope.
- Full MagicAI/WorkCore host integration and #74 cross-suite paths pass.
- Upgrade, disable, uninstall and reinstall are deterministic and preserve required history.
- Recovery objectives and rollback procedures are tested, not merely documented.
- Performance budgets are defined and met for catalogue, cart, checkout, webhooks, reconciliation and analytics.
- Provider and marketplace limitations are explicitly documented.
- Authority boundaries from #65 and standalone-product constraints from #23 remain intact.
- Release notes and upgrade instructions are complete for v6.0.0.

## Final verification gate

- All historical and new tests pass.
- PHP syntax validation passes.
- Package schema and OpenAPI validation passes.
- Migration, authority and data-preservation checks pass.
- Credential, tenant, replay, idempotency, consent and offline-conflict tests pass.
- Archive comparison shows no unintended file loss.
- Release ZIP integrity and SHA-256 verification pass.
- Any provider certification that cannot run locally is explicitly identified with external evidence requirements.

---

## Issue #55: [HIGH] [Foundation] Enforce UnifiedMemory boundaries and domain-owned canonical storage

**URL**: https://github.com/masterleeaus/ai-extensions/issues/55
**State**: OPEN

Parent: #6
Depends on: #24, #44, #47

## Problem

The ecosystem contains several memory implementations and risks treating memory as a universal document, transcript, booking or operational store. The target architecture requires hard separation between contextual memory and canonical domain records.

## Scope

Define and enforce what UnifiedMemory may and may not store, then adapt Chatbot and AIAgent memory paths to the shared policy.

## UnifiedMemory may store

- contextual facts and preferences
- summaries and commitments
- derived observations with confidence and provenance
- references to canonical domain records
- retention, consent and decay metadata

## UnifiedMemory must not own

- documents, chunks, embeddings or citations
- full calls, recordings or transcripts
- bookings or provider synchronization state
- carts, orders, inventory or payment state
- credentials or provider tokens
- canonical WorkCore operational records

## Requirements

- Define typed memory records, scopes, provenance, confidence and policy versions.
- Require source references for promoted facts.
- Add validation before writing or promoting memory.
- Add retention, decay, deletion, consent and sensitivity policies.
- Prevent raw canonical content from being copied into memory.
- Support reversible correction and contradiction handling.
- Define promotion rules from conversation, knowledge and voice domains.
- Add tenant and principal access controls.
- Provide adapters for existing governed memory, SystemAIChat memory and AIAgent memory.

## Acceptance criteria

- Architecture tests reject canonical-domain records being persisted as memory.
- Every promoted fact records provenance, confidence, policy and tenant.
- Deleting or restricting a source affects derived memory according to policy.
- Full transcripts/documents remain in their authoritative domains.
- Cross-tenant and unauthorized memory retrieval tests pass.
- Existing contextual-memory behavior continues through adapters.

---

## Issue #54: [URGENT] [Phase 0] Enforce WorkCore as the sole operational write authority through governed gateways

**URL**: https://github.com/masterleeaus/ai-extensions/issues/54
**State**: OPEN

Parent: #6
Depends on: #24, #28, #31, #44, #49

## Problem

AI extensions can create or mutate operational concepts such as customers, jobs, quotes, appointments, orders, inventory, invoices and payments through extension-specific models, Tier-3 agents, booking tools, commerce tools and compatibility runtimes. This risks competing authorities, bypassed policies and inconsistent audit trails.

## Scope

Create explicit WorkCore gateway contracts for every operational write and prohibit direct extension ownership of authoritative business records.

## Requirements

- Define typed commands/results for customer, job, quote, schedule, order, inventory, invoice and payment operations.
- Require TenantContext, actor identity, permissions, policy version, budget and idempotency key.
- Route every side effect through governed execution and produce immutable ActionReceipts.
- Preserve extension-local projections only when clearly non-authoritative and rebuildable.
- Add source/provenance references from AI proposals to accepted WorkCore records.
- Support dry-run/proposal, approval, commit, conflict and rollback states.
- Detect direct operational model writes from extensions in architecture tests.
- Add compatibility adapters for Chatbot Tier-3 actions, AIAgent bridges, Booking and Commerce surfaces.
- Define failure semantics when WorkCore is unavailable or returns a conflict.

## Acceptance criteria

- No migrated extension directly creates or mutates authoritative WorkCore records.
- The same approved operation produces one WorkCore side effect under retries.
- Denied, conflicted, failed and rolled-back writes remain traceable by correlation ID.
- Extension projections can be rebuilt from WorkCore and event records.
- Cross-tenant and wrong-actor writes fail closed.
- Integration tests cover chat → governed action → WorkCore write → receipt → Chatbot sync.
- Architecture tests reject new direct operational writes outside approved gateways.

---

## Issue #53: [LOW] [ChatbotEcommerce Pass 30] Privacy-first local intelligence and offline read mode (v5.9.0)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/53
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #24, #31, #49, #51, #54, #58, #64, #65, #71

## Objective

Add privacy-first local-model support and offline-safe commerce reads without fabricating stock, prices, payments or order state and without making local caches a competing canonical commerce store.

## Deliverables

- Add provider-neutral local model and embedding capability contracts.
- Support approved local inference for product retrieval, summaries, classification and draft generation.
- Add encrypted tenant/device-scoped local indexes for permitted product data, policies and selected preferences.
- Add offline catalogue, draft-cart, order-history snapshot and support read modes.
- Queue only explicitly permitted mutations for later synchronization with conflict detection and approval preservation.
- Add selective-cloud-sync, retention, device revocation and local-data deletion controls.
- Mark cached facts with source version, retrieval time, freshness policy and offline status.
- Add recovery behaviour for network/provider outages and interrupted synchronization.
- Revalidate current price, stock, identity, approval and policy before executing any queued consequence.

## Acceptance criteria

- Useful permitted read workflows remain available during outages.
- Offline results clearly identify cached or potentially stale facts.
- Payment, final stock, final price and authoritative order-state claims are never fabricated.
- Queued writes preserve original approval evidence but revalidate current authority before execution.
- Local indexes and preferences are encrypted, tenant/device-scoped, revocable and deletable.
- Loss or revocation of a device prevents future access to synchronized protected data.
- Reconnect conflicts do not silently overwrite canonical commerce or WorkCore state.

## Verification

Add offline, stale-cache, conflict, reconnect, device-revocation, retention, local-provider and queued-action fixtures plus the complete historical release gate.

---

## Issue #52: [MEDIUM] [ChatbotEcommerce Pass 29] Agentic Commerce Protocol v2 (v5.8.0)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/52
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #24, #28, #31, #34, #44, #49, #51, #54, #58, #61, #64, #65, #71

## Objective

Expose a governed agent-to-commerce protocol that lets external buyer and seller agents discover, quote and prepare purchases without receiving unrestricted customer, payment or seller authority.

## Deliverables

- Expand the public OpenAPI contract for discovery, quotes, availability, delivery, returns and purchase approval.
- Add short-lived signed agent credentials with audience, tenant, customer-consent and capability scopes.
- Add quote expiry, provenance, confidence, currency, tax/shipping assumptions and policy disclosures.
- Add buyer-agent and seller-agent negotiation limits.
- Add replay-safe purchase intents and seller/customer approval controls.
- Add agent identity, delegation, revocation and audit records.
- Add conformance tests and example partner clients.
- Preserve the `Inform → Prepare → Approve → Execute` model for consequential actions.
- Route execution through shared commerce contracts, governed tools and WorkCore boundaries.

## Acceptance criteria

- External agents can discover and prepare purchases without unrestricted authority.
- Tokens are short-lived, audience-bound, replay-resistant, tenant-scoped and capability-scoped.
- Quotes are immutable, expiring and traceable to source inventory, price and policy snapshots.
- Negotiation limits cannot bypass spend limits, marketplace rules, identity checks or seller/customer approvals.
- Revoked delegation stops future operations immediately.
- Example clients pass the published conformance suite.
- Cross-tenant agents and customer/order references are rejected.

## Verification

Run protocol conformance, token-tampering, replay, revocation, tenant-isolation, approval and provider-failure tests plus the complete extension release gate.

---

## Issue #51: [HIGH] [Phase 2] Consolidate provider/model profiles, capability health and usage accounting

**URL**: https://github.com/masterleeaus/ai-extensions/issues/51
**State**: OPEN

Parent: #6
Depends on: #24, #31, #40, #44

## Problem

AIChatPro, Chatbot, AIAgent, voice, creative and provider extensions maintain overlapping provider settings, fallback rules, balance checks, usage records and model selection behavior. Cost attribution and degradation policy are inconsistent.

## Scope

Create shared provider/model profile contracts and one tenant-scoped usage and budget ledger.

## Requirements

- Separate provider identity from capability implementations and model profiles.
- Define tenant-scoped provider profiles using Credential Vault references.
- Declare supported capabilities, regions, models, limits and health.
- Track provider attempts, latency, status, token/media/call usage and estimated/actual cost.
- Add budget scopes for tenant, user, agent, workflow, conversation and feature.
- Add rate-limit telemetry, circuit breakers and controlled fallback.
- Make fallback policy explicit by capability, quality, privacy, residency and cost.
- Avoid double counting retries, streamed responses and provider fallbacks.
- Provide adapters for existing Chatbot native runtime, AIChatPro, AIAgent and media/voice extensions.
- Redact prompts/content according to retention policy while preserving accounting.

## Acceptance criteria

- The same provider call is attributed once with correlation and attempt IDs.
- Budget limits are enforced before provider execution.
- Fallback attempts remain traceable and do not hide the original failure.
- Provider health and rate-limit state are visible without exposing credentials.
- Chatbot, AIChatPro and AIAgent use compatible model/profile resolution.
- Tests cover retries, fallback, partial streaming, cancellation, circuit breaking and budget exhaustion.
- Existing provider settings migrate through reversible adapters.

---

## Issue #50: [LOW] [ChatbotEcommerce Pass 28] Direct payment and BNPL provider adapters (v5.7.0)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/50
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #24, #25, #28, #31, #34, #40, #44, #49, #51, #54, #58, #61, #64, #65, #71

## Objective

Add first-class payment and BNPL adapters behind the standalone ChatbotEcommerce payment boundary and shared credential, connector, governance and WorkCore authority contracts—only for providers, regions and merchant accounts where support and approval are verified.

## Deliverables

- Select and document the initial supported payment gateways and regions.
- Add direct BNPL adapters only where merchant approval, product eligibility and regional support exist.
- Support authorization, capture, cancellation and partial/full refunds according to provider capability.
- Support disputes, reconciliation and verified provider webhooks.
- Add provider capability discovery, idempotency and hosted-action/redirect handling.
- Add normalized failure translation and customer-safe error messages.
- Keep raw card, bank and provider credentials outside ordinary extension storage.
- Define whether payment/order records are authoritative in ChatbotEcommerce, WorkCore or the provider for each state transition.
- Add sandbox certification suites and provider-specific fixtures.

## Acceptance criteria

- Adapters pass signature/replay, duplicate-event, partial capture/refund, timeout and reconciliation tests.
- Capability differences are explicit; unsupported operations fail before execution.
- Payment and refund actions retain seller/customer approval and verified-identity requirements.
- No raw payment credentials, card data or provider secrets appear in logs, models, events, receipts or API output.
- Duplicate requests and callbacks cannot duplicate charges, captures or refunds.
- Cross-tenant merchant accounts and payment references are inaccessible.
- Residential-rent BNPL remains disabled by default.

## Verification

Run provider sandbox suites, credential rotation/revocation tests, signature/replay tests, concurrency/idempotency tests, complete-host tests and the historical extension release gate.

---

## Issue #49: [HIGH] [Phase 2] Define shared ToolDefinition, ExecutionContext, ToolResult and ActionReceipt contracts

**URL**: https://github.com/masterleeaus/ai-extensions/issues/49
**State**: OPEN

Parent: #6
Depends on: #24, #28, #31, #40, #44

## Problem

Tool and action execution is independently implemented across AIChatPro connectors, AIAgent actions and auto tool calling, Chatbot tools and Tier-3 agents, PhoneCallAgent, MarketingBot, SocialMediaAgent and compatibility bridges. Schemas, permissions, retries, budgets, receipts and error behavior can drift.

## Scope

Create one provider-neutral execution contract and adapt each product surface without conflating tools, skills, agents, workflows, connectors or providers.

## Core contracts

- `ToolDefinition`
- `ExecutionContext`
- `ToolInputSchema`
- `ToolResult`
- `ActionReceipt`
- `ToolError` / retry classification
- provider-schema converter

## Requirements

- Stable tool identity and semantic version.
- Typed JSON-schema input and output validation.
- Side-effect, permission, approval, risk, timeout, retry, budget and idempotency declarations.
- Provider-neutral conversion for OpenAI, Anthropic and Gemini.
- Evidence/citation support for read tools.
- Receipts and rollback contracts for side-effect tools.
- Shared usage/cost attribution.
- Explicit separation between a tool definition and the connector/provider that implements it.
- Adapters for AIChatPro, AIAgent, Chatbot, PhoneCallAgent, MarketingBot and SocialMediaAgent.
- Conformance suite every adapter must pass.

## Acceptance criteria

- One governed tool executes through AIChatPro, Chatbot and AIAgent with identical validation and policy outcomes.
- Invalid input/output is rejected consistently.
- Permission, approval, denial, retry, timeout, idempotency and rollback tests pass across adapters.
- Provider schema conversion produces equivalent tool contracts for supported model APIs.
- All side effects can be traced by correlation ID and receipt.
- Architecture tests reject new product-specific tool contracts that bypass the shared boundary.

---

## Issue #48: [ChatbotEcommerce Pass 27] Amazon Selling Partner adapter (v5.6.0)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/48
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #24, #25, #31, #34, #40, #44, #49, #51, #58, #61, #64, #65, #71

## Objective

Implement an Amazon Selling Partner API adapter through the shared connector, commerce, identity, credential and governed-action boundaries while keeping buyer checkout and Amazon-authoritative marketplace state on Amazon.

## Deliverables

- Implement Login with Amazon authorization and Credential Vault lifecycle.
- Implement AWS request signing within the connector/provider boundary.
- Add Listings Items, Catalog Items, Orders, Feeds, Fulfilment and Finances read models.
- Add product-type schema and required-attribute discovery.
- Add feed submission, status polling and per-record error translation.
- Add eligibility, restricted-product and identifier warnings.
- Add approval-gated listing, price, inventory and fulfilment writes.
- Add marketplace, region, account and restricted-data scoping.
- Map permitted operational records through #65 and WorkCore authority rules.

## Acceptance criteria

- Adapter passes sandbox or approved test-account flows.
- Restricted seller and buyer data never escapes authorized tenant, actor and purpose scope.
- Every write uses capability checks, source-version evidence, approval, idempotency and rollback state.
- Feed partial failures are isolated and translated into actionable item-level results.
- Amazon checkout remains authoritative and cannot be bypassed.
- Request signing, token rotation and provider errors never expose credentials.
- Duplicate notifications, polls or retries cannot duplicate marketplace effects.

## Verification

Add signed-request fixtures, token-rotation tests, feed polling tests, restricted-data tests, provider-failure tests, complete-host tests and the historical extension release gate.

---

## Issue #47: [Phase 1] Build the tenant-safe Knowledge Engine first production vertical slice

**URL**: https://github.com/masterleeaus/ai-extensions/issues/47
**State**: OPEN

Parent: #6
Depends on: #10, #14, #24, #28, #31, #44

## Objective

Implement the first production proof defined in `upgrade plan/02-KNOWLEDGE-ENGINE-FOUNDATION.md` without an all-at-once migration.

## Vertical slice

```text
Submit one plain-text source
→ authorize tenant and actor
→ validate and deduplicate
→ create a logical source and immutable document version
→ create stable chunks and keyword index
→ assign one knowledge base to two independent agents
→ retrieve evidence through `search_knowledge`
→ return stable citations
→ preserve one legacy controller response shape
```

PDF, spreadsheet, website crawling, vector retrieval and bulk migration are out of the initial slice unless required by an accepted dependency.

## Canonical records

- knowledge bases
- sources
- documents
- immutable document versions
- chunks
- assignments and access rules
- ingestion jobs and events
- query logs and citations

Every authoritative record must be tenant-scoped.

## Requirements

- Typed submission, assignment, deletion, reingestion and search commands.
- Separate hashes for source identity, raw content, normalized content, chunks and embedding inputs.
- Private storage for canonical content.
- Idempotent staged jobs with heartbeat, retry, cancellation and stale recovery.
- Assignment-based reuse without duplicating source content.
- Keyword retrieval with permission filtering and context budgeting.
- Stable citations tied to immutable document versions.
- `search_knowledge` returns evidence; the calling agent composes the final answer.
- One legacy training controller operates through a thin compatibility adapter.
- UnifiedMemory receives only derived facts and source references.

## Acceptance criteria

- One source is ingested once and assigned to two agents.
- Both agents retrieve the same permitted evidence with stable citations.
- Removing one assignment does not delete shared knowledge.
- Retries create no duplicate sources, versions or chunks.
- New document versions do not invalidate historical citations.
- Cross-tenant retrieval and enumeration tests pass.
- The selected legacy UI/API remains behaviorally compatible.
- A labelled evaluation set verifies retrieval relevance, citation precision, empty-result correctness and latency.

---

## Issue #46: [ChatbotEcommerce Pass 26] Etsy direct adapter (v5.5.0)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/46
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #24, #25, #31, #34, #40, #44, #49, #51, #58, #61, #64, #65, #71

## Objective

Implement a first-class Etsy adapter behind the shared connector, commerce, identity, credential and governed-action contracts while preserving Etsy checkout and authoritative marketplace state.

## Deliverables

- Implement Etsy OAuth and shop connection lifecycle through Credential Vault and Connector Runtime.
- Add listings, variations, inventory, images, tags, receipts and fulfilment reads.
- Add approval-gated listing and inventory writes.
- Map personalization, production partners, handmade/vintage rules and category attributes.
- Add Etsy-specific compliance findings and listing-generation templates.
- Add verified notifications or safe delta synchronization and provider-error translation.
- Add test-shop/provider fixtures where available.
- Preserve Etsy checkout and authoritative order state.
- Map permitted operational records through #65 and WorkCore authority rules.

## Acceptance criteria

- Etsy uses the same provider-neutral contracts and safety gates as other marketplace adapters.
- Writes require current source state, seller approval, idempotency and rollback evidence.
- Credential rotation and revocation work without exposing tokens.
- Listing intelligence validates Etsy tags, attributes and marketplace-specific restrictions.
- Rate-limit and partial-failure behaviour is covered by integration fixtures.
- Duplicate notifications or retries cannot duplicate marketplace effects.
- Cross-tenant shops, listings and receipts are inaccessible.

## Verification

Run provider fixtures, OAuth failure/rotation tests, synchronization and replay tests, complete-host tests and the historical extension verification gate.

---

## Issue #45: [ChatbotEcommerce Pass 25] eBay direct adapter (v5.4.0)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/45
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #24, #25, #31, #34, #40, #44, #49, #51, #58, #61, #64, #65, #71

## Objective

Implement a first-class eBay adapter behind the shared connector, commerce, identity, credential and governed-action contracts while preserving eBay checkout and authoritative marketplace state.

## Deliverables

- Implement OAuth connection lifecycle through Credential Vault and Connector Runtime.
- Implement eBay Browse, Inventory, Account and Fulfilment read capabilities.
- Implement approval-gated listing, price, stock, pause/resume and fulfilment updates.
- Map categories, item specifics, business policies and provider errors.
- Add verified notification ingestion or safe scheduled delta polling.
- Add provider capability discovery and region/marketplace handling.
- Add sandbox/test-account fixtures and integration tests.
- Preserve external checkout and eBay-authoritative order state.
- Map permitted operational records through #65 and WorkCore authority rules.

## Acceptance criteria

- Reads and writes pass eBay sandbox or approved test-account flows.
- Every write uses current source/version evidence, seller approval, idempotency and rollback state.
- Credentials never appear in ordinary ecommerce records, logs, events or responses.
- Rate limits, `Retry-After`, token refresh and provider outages are handled deterministically.
- Unsupported categories, policies and restricted actions fail before partial mutation.
- Duplicate notifications or write retries cannot duplicate marketplace effects.
- Cross-tenant marketplace accounts and listings are inaccessible.

## Verification

Run provider fixtures, OAuth failure/rotation tests, webhook replay tests, concurrency/idempotency tests, complete-host tests and the historical extension verification gate.

---

## Issue #44: [Phase 0] Implement a versioned EventEnvelope and idempotent extension event bus

**URL**: https://github.com/masterleeaus/ai-extensions/issues/44
**State**: OPEN

Parent: #6
Depends on: #24, #31

## Problem

Events, queue payloads, webhook normalization and cross-extension notifications currently use inconsistent shapes and can carry excessive models, provider payloads or sensitive values. Consumers need explicit tenancy, schema versions, causation and idempotency.

## Scope

Create a shared event envelope and delivery contract for domain events across all AI extensions.

## Required envelope fields

- immutable event ID
- event type and schema version
- tenant ID
- aggregate type and ID
- occurred-at timestamp
- actor identity
- correlation and causation IDs
- idempotency key
- compact payload and metadata

## Requirements

- Events contain identifiers and bounded metadata, not ORM models, credentials, full documents, transcripts, recordings or vectors.
- Add versioned serializer/deserializer and schema validation.
- Add an outbox pattern for authoritative state changes.
- Make consumers idempotent and tenant-scoped.
- Add retry classification, backoff, dead-letter handling and replay tooling.
- Add sensitive-data redaction and retention policy.
- Support local synchronous delivery only for explicitly safe in-process events.
- Add compatibility adapters for existing extension events.
- Track delivery attempts and correlation traces.

## Acceptance criteria

- Duplicate delivery cannot repeat a side effect.
- Invalid schema versions and cross-tenant payloads are rejected.
- Events never contain raw credentials or unbounded canonical content.
- Outbox records commit atomically with authoritative state changes.
- Failed deliveries are observable and replayable after correction.
- Tests cover retry, replay, ordering assumptions, dead letters and consumer idempotency.
- Chatbot, AIAgent and provider webhook flows publish through the shared envelope.

---

## Issue #43: [MEDIUM] [ChatbotEcommerce Pass 31] Enterprise governance and v6.0.0 general availability

**URL**: https://github.com/masterleeaus/ai-extensions/issues/43
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #34, #49, #54, #58, #61, #64, #65, #67

## Objective

Turn the Customer Communications Agent into a governed service-operations layer with SLAs, escalations, approved automation and quality review while leaving channel transport and delivery state in the shared Connector Runtime.

## Deliverables

- Add business hours, response SLAs, escalation timers and queue priorities.
- Add seller-approved automatic answers for stock, policy, tracking and common product questions.
- Add bounded discount, replacement, credit and refund policies with zero authority by default.
- Add consent-aware abandoned-cart recovery with quiet-hour controls.
- Add multilingual reply generation that preserves approved policy wording and brand voice.
- Add conversation-quality review, unresolved-reason analytics and coaching summaries.
- Add evidence references showing which live commerce facts or policies support each automated reply.
- Route actual send/delivery operations through #61 rather than direct provider/channel calls.
- Use #58 for customer identity, consent and communication preferences.
- Use #67 for durable SLA timers and escalations that must survive retries/restarts.

## Acceptance criteria

- Automated replies are grounded in current commerce data or seller-approved policy.
- Fraud, legal threats, chargebacks, safety concerns and low-confidence cases always escalate.
- Financial and fulfilment actions remain policy-checked and approval-gated.
- SLA timers survive retries and worker restarts without duplicate escalations.
- Consent, quiet hours and customer communication preferences are enforced per identity/channel.
- Delivery retries and provider health remain observable through Connector Runtime receipts.

## Verification

Include clock/timezone, retry, duplicate-message, consent, escalation, identity and connector-contract tests plus the complete extension verification gate.

---

## Issue #42: [High] Add a shared secure remote-media fetcher for VideoEditor and provider outputs

**URL**: https://github.com/masterleeaus/ai-extensions/issues/42
**State**: OPEN

Parent: #6
Depends on: #7, #24

## Confirmed risk

VideoEditor and multiple media-provider extensions download remote files using separate helpers with inconsistent validation, redirect handling, size limits, content checks and cleanup.

## Scope

Create one bounded remote-media fetcher and migrate VideoEditor plus provider-result downloaders to it.

## Requirements

- Permit only configured HTTP/HTTPS sources.
- Reject unsafe or unsupported destination classes before connecting.
- Revalidate redirect destinations.
- Apply connection, idle and total timeouts.
- Apply response-size, redirect and decompression limits.
- Validate expected MIME type and file signatures before promotion.
- Stream to private quarantine storage using server-generated names.
- Verify media dimensions, duration and codec policy where applicable.
- Sanitize URLs and provider errors in logs.
- Support cancellation and reliable cleanup of partial downloads.
- Allow provider-domain policies without bypassing destination validation.

## Acceptance criteria

- Security tests reject unsafe destinations and unsafe redirect chains.
- Oversized and content-mismatched media is rejected and cleaned up.
- VideoEditor exports continue working with valid local and remote sources.
- Provider webhook handlers use the shared fetcher instead of direct download helpers.
- Media is not publicly available before validation.
- Download audit records include tenant, source host, resolved destination, byte count, content type and correlation ID.

---

## Issue #41: [ChatbotEcommerce Pass 23] Visual and voice commerce (v5.2.0)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/41
**State**: OPEN

Parent: #59
Related architecture programme: #6
Depends on: #23, #34, #42, #49, #51, #58, #60, #64, #65

## Objective

Add image- and voice-first shopping workflows by reusing shared media, voice, identity and commerce contracts rather than duplicating vision, OCR, storage or voice engines inside ChatbotEcommerce.

## Deliverables

- Add image-based product discovery with traceable evidence.
- Add barcode and OCR extraction through registered media/vision capabilities.
- Add replacement-part and compatible-product matching.
- Add visual-similarity search across permitted native and marketplace listings.
- Add voice-first product search, comparison, cart changes, checkout preparation and support enquiries through #60.
- Require visual or secure-screen confirmation for addresses, payments, BNPL and consequential actions.
- Add accessible text fallbacks for every visual and voice result.
- Store evidence references, confidence and source listing IDs for generated matches.
- Use #42 for remote media retrieval and bounded validation.
- Fail safely when required media/voice capabilities are unavailable or degraded.

## Acceptance criteria

- A customer can photograph or describe an item and receive grounded, traceable matches.
- Sensitive payment, address and identity data is not spoken back unnecessarily.
- Low-confidence and ambiguous matches request clarification instead of modifying a cart.
- Customer actors cannot reach seller-only media or catalogue-management tools.
- Text-only clients can complete equivalent safe flows.
- Voice, image and OCR records remain in their authoritative services; ChatbotEcommerce stores only necessary commerce references.

## Verification

Include media fixtures, accessibility checks, tenant/identity tests, confidence thresholds, provider-degradation tests, all historical regressions, PHP lint, package/schema validation, OpenAPI parsing and release-artifact integrity checks.

---

## Issue #40: [Phase 2] Upgrade AIChatPro connector registry to capability-aware, versioned discovery

**URL**: https://github.com/masterleeaus/ai-extensions/issues/40
**State**: OPEN

Parent: #6
Depends on: #24, #31, #37

## Problem

AIChatPro's connector registry is a useful provider-neutral seed, but registration is currently a simple key-to-class overwrite and tool lookup depends on function-name prefixes. There is no duplicate detection, semantic versioning, capability negotiation, health, provenance or dependency policy.

## Scope

Evolve the connector registry into the connector-facing adapter of the Unified Registry while preserving distinct registry entity types.

## Requirements

- Define versioned connector manifests with stable IDs, provider, capabilities, scopes and lifecycle state.
- Reject duplicate active IDs unless an explicit replacement/migration rule exists.
- Add semantic version and host compatibility constraints.
- Declare inbound, outbound, authentication, tool and health capabilities separately.
- Register typed tool definitions without relying on string prefixes.
- Add health, degraded and paused states plus rate-limit telemetry.
- Store only Credential Vault references.
- Capture package provenance, integrity hash and installed version.
- Support discovery by capability, provider and tenant entitlement.
- Keep connectors separate from tools, providers, skills, workflows and agents.
- Provide adapters for existing AIChatPro connector extensions.

## Acceptance criteria

- Registration collisions fail deterministically with a clear diagnostic.
- Callers discover connectors by typed capability rather than marketplace-name checks.
- Provider tool schemas remain convertible for OpenAI, Anthropic and Gemini.
- Disabled, unhealthy, incompatible or unauthorized connectors are not returned as executable.
- Existing connector UI and lifecycle operations work through adapters.
- Registry conformance tests cover duplicate IDs, version mismatch, health and capability lookup.

---

