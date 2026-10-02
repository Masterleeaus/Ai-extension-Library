# AI Suite Extensions Issues - Complete Index

**Last Updated:** August 4, 2026  
**Total Issues:** 50+  
**Status:** Documentation phase - Ready for implementation  

## Quick Navigation

- [Phase 0: Foundation](#phase-0-foundation) - URGENT
- [Phase 1: Critical Security](#phase-1-critical-security)
- [Phase 2: Production Hardening](#phase-2-production-hardening)
- [Phase 3: Voice Platform](#phase-3-voice-platform)
- [Phase 5: Connector Platform](#phase-5-connector-platform)
- [WorkCore Modules](#workcore-modules)
- [Integrations](#integrations)
- [Vertical Customizations](#vertical-customizations)
- [Other Tests & Infrastructure](#other-tests--infrastructure)

---

## Phase 0: Foundation (URGENT)

These are prerequisite issues for all other work. Start here.

### Issue #143: TenantContext & Authorization Policies
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** `ISSUE_143_TENANT_CONTEXT_AUTHORIZATION.md`
- **Purpose:** Establish tenant isolation and authorization as foundation
- **Deliverables:**
  - TenantContext interface
  - AuthorizationPolicy interfaces
  - Tenant filters on all repositories
  - Tenant ID on all domain events
  - Webhook tenant resolution
- **Exit Criteria:**
  - All queries include tenant filter
  - All events carry tenant_id
  - No cross-tenant data leakage
  - Isolation tests pass

### Issue #144: EventEnvelope & Idempotent Event Consumers
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** `ISSUE_144_EVENT_ENVELOPE_IDEMPOTENCY.md`
- **Purpose:** Establish event structure and idempotency guarantees
- **Deliverables:**
  - EventEnvelope schema with immutable event ID
  - IdempotencyLedger implementation
  - IdempotentEventConsumer base class
  - Event replay prevention
- **Exit Criteria:**
  - Every event has immutable ID and idempotency key
  - Event consumers are idempotent
  - Replayed events produce identical results
  - Correlation tracing works

### Issue #145: Credential Vault References & Envelope Encryption
- **Status:** Pending
- **Effort:** 1 week
- **Documentation:** `ISSUE_145_CREDENTIAL_VAULT.md`
- **Purpose:** Replace plaintext secrets with vault references and encryption
- **Deliverables:**
  - CredentialVaultReference contract
  - Vault interface with AWS/local implementations
  - EnvelopeEncryption for at-rest storage
  - SecretRedactor for log cleaning
  - Credential lifecycle management
- **Exit Criteria:**
  - No secrets in logs, events, or queue payloads
  - All API keys use vault references
  - Secrets absent from configuration snapshots
  - Encryption tests pass

### Issue #146: Webhook Verification & Replay Prevention Tests
- **Status:** Pending
- **Effort:** 1 week
- **Documentation:** `ISSUE_146_WEBHOOK_VERIFICATION.md`
- **Purpose:** Comprehensive webhook verification and replay prevention
- **Deliverables:**
  - WebhookVerification interface
  - Provider-specific verifiers (Twilio, ElevenLabs, Meta, Slack, Gmail)
  - ReplayPrevention implementation
  - WebhookHandler base class
  - Complete webhook test suite
- **Exit Criteria:**
  - All webhook providers verified
  - Timestamp freshness enforced
  - Replay attacks prevented
  - Idempotency working

### Issue #147: AIAgent Conformance Test Suite
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** ISSUE_147_AIAGENT_TESTS.md (create)
- **Purpose:** 20-30 conformance tests for AIAgent
- **Test Coverage:**
  - Workflow execution
  - Action dispatching
  - Trigger processing
  - Channel operations
  - Memory injection
  - Knowledge retrieval
  - Tool calling

### Issue #148: PhoneCallAgent Conformance Test Suite
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** ISSUE_148_PHONECALLAGENT_TESTS.md (create)
- **Purpose:** 25-40 conformance tests for PhoneCallAgent
- **Test Coverage:**
  - Twilio webhook handling
  - ElevenLabs integration
  - Call lifecycle
  - Recording handling
  - Transcription
  - Booking operations
  - Provider fallback

### Issue #149: ChatbotVoice & ChatbotVoiceCall Test Suite
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** ISSUE_149_CHATBOT_VOICE_TESTS.md (create)
- **Purpose:** 15-20 tests each for ChatbotVoice and ChatbotVoiceCall
- **Test Coverage:**
  - Voice conversations
  - Audio session management
  - TTS/STT integration
  - Multi-vertical scenarios
  - Realtime transport
  - Session lifecycle

### Issue #150: Root Architecture Test Suite
- **Status:** Pending
- **Effort:** 1 week
- **Documentation:** ISSUE_150_ROOT_ARCHITECTURE_TESTS.md (create)
- **Purpose:** System-wide integration tests
- **Test Coverage:**
  - Cross-extension communication
  - Shared infrastructure
  - Event flow across modules
  - Authority boundaries
  - Data isolation
  - Permission checks

### Issue #64: Native Smoke, Security & Contract Tests
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_64_SMOKE_SECURITY_TESTS.md (create)
- **Purpose:** Automated testing for all 78 extensions
- **Test Coverage:**
  - Smoke tests (basic functionality)
  - Security property tests
  - Contract compliance
  - Configuration validation
  - Integration points

---

## Phase 1: Critical Security

These security issues must be fixed before production use.

### Issue #20: Harden PhoneCallAgent Webhooks and ElevenLabs Callbacks
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** `ISSUE_20_PHONECALLAGENT_HARDENING.md`
- **Purpose:** Fix critical webhook security defects
- **Confirmed Defects:**
  - Missing credential validation
  - Incomplete signature verification
  - No timestamp freshness enforcement
  - Public tool endpoint vulnerability
  - Raw bodies and exceptions logged
- **Immediate Containment:** Required
- **Full Hardening:** See documentation

### Issue #211: Quarantine WhatsApp Media & Remove Base64 Payloads
- **Status:** Pending
- **Effort:** 1 week
- **Documentation:** ISSUE_211_WHATSAPP_MEDIA_QUARANTINE.md (create)
- **Purpose:** Fix critical media handling defect
- **Confirmed Defect:**
  - Inbound WhatsApp media downloaded to memory
  - Base64-encoded and embedded in messages
  - No media size limits
- **Requirements:**
  - Quarantine media to temporary storage
  - Remove base64 encoding
  - Implement bounded media handling
  - Never store full payloads in workflow records

---

## Phase 2: Production Hardening (URGENT)

These issues prepare the system for production.

### Issue #67: Harden AIAgent Workflow Engine
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_67_AIAGENT_HARDENING.md (create)
- **Purpose:** Production-ready workflow execution
- **Focus Areas:**
  - Error recovery
  - Durable state management
  - Timeout policies
  - Budget limits
  - Action permissions
  - Secret references

### Issue #68: Formalize Shared Skill Runtime
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** ISSUE_68_SKILL_RUNTIME.md (create)
- **Purpose:** Package security and version lifecycle
- **Deliverables:**
  - Skill package contract
  - Version resolution
  - Dependency management
  - Compatibility checks
  - Security scanning

### Issue #70: Migrate Chatbot Tier-3 Actions to WorkCore Gateways
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_70_CHATBOT_WORKCORE_MIGRATION.md (create)
- **Purpose:** Move operational actions to shared gateways
- **Requirements:**
  - Identify all Chatbot Tier-3 agents
  - Create WorkCore gateway for each vertical
  - Migrate actions
  - Implement governance
  - Add audit trails

### Issue #71: Add Feature Flags, Migration State & Rollback Controls
- **Status:** Pending
- **Effort:** 1-2 weeks
- **Documentation:** ISSUE_71_FEATURE_FLAGS.md (create)
- **Purpose:** Safe progressive migration
- **Deliverables:**
  - Feature flag system
  - Migration state tracking
  - Shadow read validation
  - Rollback procedures
  - Gradual cutover

---

## Phase 3: Voice Platform

Building the canonical voice infrastructure.

### Issue #60: Build Capability-Composed Voice Engine
- **Status:** Pending
- **Effort:** 3-4 weeks
- **Documentation:** ISSUE_60_VOICE_ENGINE.md (create)
- **Purpose:** Canonical voice infrastructure for all voice products
- **Deliverables:**
  - Call lifecycle management
  - Transcript segment handling
  - Recording consent & retention
  - Usage accounting
  - Provider capability abstraction
  - Governed tool execution
- **Supported Providers:**
  - Twilio (telephony)
  - ElevenLabs (speech)
  - OpenAI (realtime)

### Issue #21: Migrate ChatbotVoice to Voice Engine
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_21_CHATBOT_VOICE_MIGRATION.md (create)
- **Purpose:** Consolidate voice conversations onto shared engine
- **Requirements:**
  - Adapt existing voice agents
  - Preserve UI behavior
  - Move canonical call/transcript state
  - Route through knowledge engine
  - Enforce tenant isolation
  - Add reconciliation & rollback

---

## Phase 5: Connector Platform

Building the canonical connector infrastructure.

### Issue #61: Build Shared Connector Runtime
- **Status:** Pending
- **Effort:** 3-4 weeks
- **Documentation:** ISSUE_61_CONNECTOR_RUNTIME.md (create)
- **Purpose:** Canonical connector infrastructure for all channels
- **Deliverables:**
  - Connector lifecycle management
  - OAuth & credential handling
  - Webhook registration & verification
  - Inbound normalization
  - Outbound dispatch with retries
  - Rate limit handling
  - Health monitoring
  - Dead letter queues
  - Consent tracking
- **Conformance Adapters:**
  - Gmail
  - Slack
  - WhatsApp
  - (Telegram, Meta in Phase 6)

---

## WorkCore Modules

Building the operational business system.

### Issue #181: WorkCore Shared Foundation
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_181_WORKCORE_FOUNDATION.md (create)
- **Purpose:** Tenancy, permissions, and governance
- **Includes:**
  - Tenant management
  - Permission policies
  - Audit logging
  - Resource governance
  - Integration contracts

### Issue #182: WorkCoreBusinessNetwork
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_182_WORKCORE_BUSINESS_NETWORK.md (create)
- **Purpose:** CRM, catalogue, knowledge
- **Verticals:** All (Health, E-commerce, Real Estate, Field Services)

### Issue #183: WorkCoreCommercial
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_183_WORKCORE_COMMERCIAL.md (create)
- **Purpose:** Finance, payroll, inventory
- **Verticals:** E-commerce, Field Services, Real Estate

### Issue #184: WorkCoreWorkOperations
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_184_WORKCORE_WORK_OPERATIONS.md (create)
- **Purpose:** Scheduling, dispatch, fleet management
- **Verticals:** Field Services, Real Estate

### Issue #185: WorkCorePropertyOperations
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_185_WORKCORE_PROPERTY_OPERATIONS.md (create)
- **Purpose:** Premises, assets, documents
- **Verticals:** Real Estate, Field Services, Health

### Issue #186: WorkCoreWorkforceAssurance
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_186_WORKCORE_WORKFORCE_ASSURANCE.md (create)
- **Purpose:** Workforce management, compliance, NDIS
- **Verticals:** Health, Field Services

---

## Integrations

Connecting AI suites to WorkCore.

### AI Suite → WorkCore Foundation

- **Issue #187:** AiChatPro Platform AI
- **Issue #193:** Chatbot PWA
- **Issue #199:** AIAgent Autonomous Operations

### AI Suite → WorkCore Business Network

- **Issue #188:** AiChatPro CRM Features
- **Issue #194:** Chatbot CRM Assistant
- **Issue #200:** AIAgent CRM Automation

### AI Suite → WorkCore Commercial

- **Issue #189:** AiChatPro Financial Insights
- **Issue #195:** Chatbot Commerce Operations
- **Issue #201:** AIAgent Financial Automation

### AI Suite → WorkCore Work Operations

- **Issue #190:** AiChatPro Operations Dashboard
- **Issue #196:** Chatbot Job & Dispatch Assistant
- **Issue #202:** AIAgent Autonomous Dispatch

### AI Suite → WorkCore Property Operations

- **Issue #191:** AiChatPro Asset Management
- **Issue #197:** Chatbot Property Assistant
- **Issue #203:** AIAgent Property Automation

### AI Suite → WorkCore Workforce Assurance

- **Issue #192:** AiChatPro HR Operations
- **Issue #198:** Chatbot HR Assistant
- **Issue #204:** AIAgent HR Automation

---

## Vertical Customizations

Enabling vertical-specific configurations and behaviors.

### Issue #205: Prompt Customization Framework
- **Status:** Pending
- **Effort:** 2 weeks
- **Purpose:** Per-vertical AI prompts
- **Verticals:** Health, E-commerce, Real Estate, Field Services

### Issue #206: Template Management Framework
- **Status:** Pending
- **Effort:** 2 weeks
- **Purpose:** Domain-specific message templates
- **Verticals:** Health, E-commerce, Real Estate, Field Services

### Issue #207: Forms Builder Framework
- **Status:** Pending
- **Effort:** 2 weeks
- **Purpose:** Domain-specific data collection
- **Verticals:** Health, E-commerce, Real Estate, Field Services

### Issue #208: Localization Framework
- **Status:** Pending
- **Effort:** 2 weeks
- **Purpose:** Language, region, cultural adaptation
- **Languages:** Multi-language support

### Issue #209: Branding & Theming Framework
- **Status:** Pending
- **Effort:** 2 weeks
- **Purpose:** White-label customization
- **Customization:** Colors, logos, brand identity

### Issue #210: Behavior Configuration Framework
- **Status:** Pending
- **Effort:** 2 weeks
- **Purpose:** AI model tuning per vertical
- **Models:** OpenAI, Anthropic, Gemini configurations

---

## Other Tests & Infrastructure

### Issue #75: Shared Audit, Observability & Correlation Tracing
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_75_AUDIT_OBSERVABILITY.md (create)
- **Purpose:** Cross-service observability
- **Deliverables:**
  - Audit logging framework
  - Correlation tracing
  - OpenTelemetry integration
  - Retention policies
  - Access controls

### Issue #74: Cross-Suite Integration Tests
- **Status:** Pending
- **Effort:** 2-3 weeks
- **Documentation:** ISSUE_74_CROSS_SUITE_TESTS.md (create)
- **Purpose:** Integration paths for all systems
- **Test Coverage:**
  - Chat → Workflow → Approval → WorkCore → Receipt
  - Voice → Knowledge → Tool → Governance
  - Channel → Connector → Agent → Action → WorkCore

---

## Implementation Order

### Recommended Sequence

**Week 1-2: Phase 0 Foundation (Parallel)**
- Start: #143, #144, #145, #146
- Blocks: Everything else

**Week 3: Phase 0 Testing**
- Continue: #147, #148, #149, #150, #64

**Week 4-5: Phase 1 Security**
- #20: PhoneCallAgent hardening (critical)
- #211: WhatsApp media quarantine (critical)

**Week 6-8: Phase 2 Production**
- #67, #68, #70, #71 (parallel)
- Prepare system for production use

**Week 9-10: Phase 3 Voice**
- #60: Voice Engine (long-running)
- #21: ChatbotVoice migration

**Week 11-12: WorkCore Modules**
- #181-186 (can run in parallel)

**Week 13-14: Phase 5 Connector**
- #61: Connector Runtime

**Week 15-16: Integrations**
- #187-204 (can run in parallel)

**Week 17-18: Vertical Customizations**
- #205-210 (can run in parallel)

**Ongoing: Tests & Observability**
- #75: Audit & observability
- #74: Cross-suite integration tests

---

## Core Suites Reference

### AIChatPro (16 extensions)
- Internal human-facing AI workspace
- Tool-enabled chat surface
- Connector-based provider integration (OpenAI, Anthropic, Gemini)
- Add-ons: Deep Research, File Chat, Folders, Skills, Entity Highlight, Canvas, Voice, etc.

### Chatbot (9 extensions)
- External/customer conversation platform
- Offline PWA runtime
- Staff inbox and team communication
- Five-tier AI orchestration (Tier-1 through Tier-3)
- Multi-vertical specialists
- Governance and approval workflows

### AIAgent (10 extensions)
- Autonomous workflow engine
- Triggers (schedule, webhook, channel message)
- Actions (AI call, message, memory, branch, report)
- Multi-channel conversation inbox
- Connectors (Gmail, Slack, WhatsApp, Telegram)
- Knowledge injection and memory
- Workflow copilot

### Supporting Extensions
- PhoneCallAgent: Twilio + ElevenLabs voice orchestration
- Skill Runtime: Packaged AI skills for all suites
- Provider Registry: Shared model orchestration
- WorkCore: Operational business system

---

## Key Architectural Principles

1. **Tenant Isolation First:** Every data point must be tenant-aware
2. **Event-Driven:** All changes flow through idempotent event consumers
3. **Secrets Never Exposed:** Credentials always use vault references
4. **Webhook Verification Mandatory:** All external inputs verified
5. **Authorization Enforced:** Every action checked against policies
6. **Governance Required:** Consequential actions route through approval layer
7. **Audit Trail Complete:** Every action logged for compliance
8. **Graceful Degradation:** Provider outages don't crash the system
9. **Progressive Migration:** Old and new systems coexist safely during transition
10. **Cross-Vertical Support:** All features work across Health, E-commerce, Real Estate, Field Services

---

## Success Criteria

All 50+ issues complete when:

- ✅ All Phase 0 foundations implemented and tested
- ✅ Critical security issues (#20, #211) fixed
- ✅ AIAgent, Chatbot, AIChatPro hardened for production
- ✅ Voice Engine and Connector Runtime operational
- ✅ WorkCore modules implemented and integrated
- ✅ All 18 integration paths working
- ✅ Vertical customizations available
- ✅ 80%+ test coverage across all extensions
- ✅ Audit and observability comprehensive
- ✅ Cross-suite integration tests passing
- ✅ Security review passed
- ✅ Documentation complete
- ✅ Ready for production deployment

---

## File Structure

```
AI-extensions/
├── ISSUE_143_TENANT_CONTEXT_AUTHORIZATION.md
├── ISSUE_144_EVENT_ENVELOPE_IDEMPOTENCY.md
├── ISSUE_145_CREDENTIAL_VAULT.md
├── ISSUE_146_WEBHOOK_VERIFICATION.md
├── ISSUE_20_PHONECALLAGENT_HARDENING.md
├── ISSUE_147_AIAGENT_TESTS.md (to create)
├── ISSUE_148_PHONECALLAGENT_TESTS.md (to create)
├── ISSUE_149_CHATBOT_VOICE_TESTS.md (to create)
├── ISSUE_150_ROOT_ARCHITECTURE_TESTS.md (to create)
├── ISSUE_64_SMOKE_SECURITY_TESTS.md (to create)
├── ISSUE_211_WHATSAPP_MEDIA_QUARANTINE.md (to create)
├── ISSUE_67_AIAGENT_HARDENING.md (to create)
├── ISSUE_68_SKILL_RUNTIME.md (to create)
├── ISSUE_70_CHATBOT_WORKCORE_MIGRATION.md (to create)
├── ISSUE_71_FEATURE_FLAGS.md (to create)
├── ISSUE_60_VOICE_ENGINE.md (to create)
├── ISSUE_21_CHATBOT_VOICE_MIGRATION.md (to create)
├── ISSUE_61_CONNECTOR_RUNTIME.md (to create)
├── ISSUE_75_AUDIT_OBSERVABILITY.md (to create)
├── ISSUE_74_CROSS_SUITE_TESTS.md (to create)
├── ISSUE_181_WORKCORE_FOUNDATION.md (to create)
├── ISSUE_182_186_WORKCORE_MODULES.md (to create)
├── ISSUE_187_204_INTEGRATIONS.md (to create)
├── ISSUE_205_210_VERTICAL_CUSTOMIZATIONS.md (to create)
└── AI_SUITE_EXTENSIONS_ISSUES_INDEX.md (this file)
```

---

## Next Steps

1. ✅ Create issue documentation files (Phase 0 complete)
2. Continue creating issue documentation (Phase 1-5)
3. Commit and push to working branch
4. Merge to main
5. Begin implementation starting with Phase 0 foundations

---

**Status:** Documentation ready for implementation  
**Last Updated:** August 4, 2026  
**Maintained by:** AI Extensions Development Team
