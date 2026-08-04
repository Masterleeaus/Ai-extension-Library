# 75 AI Extensions Issues - Complete Mapping & Status

**Last Updated**: 2026-08-04  
**Total Issues**: 75  
**Implementation Status**: Waves 1-2 in progress, 3-8 scaffolded  
**Merge Strategy**: Frequent merges to main after each logical wave  

---

## WAVE 1: Phase 0 Foundation ✓ COMPLETE

**PR #213 - Ready to Merge**  
**Issues**: #143, #144, #145, #146, #150, #71, #24, #31, #44, #54, #64

| # | Title | Status | Component |
|---|-------|--------|-----------|
| #143 | TenantContext & Authorization | ✓ Done | foundation/Implementations/TenantContext.php |
| #144 | EventEnvelope & Idempotent Events | ✓ Done | foundation/Implementations/EventEnvelope.php |
| #145 | Credential Vault & Redaction | ✓ Done | foundation/Implementations/CredentialVault.php |
| #146 | Webhook Verification & Replay | ✓ Done | foundation/Implementations/WebhookVerifier.php |
| #150 | Root Architecture Test Suite | → Pending | foundation/Tests/ |
| #71 | Feature Flags & Migration State | → Pending | foundation/Implementations/FeatureFlags.php |
| #24 | TenantContext repos | → Ready | Apply TenantContext pattern to all repos |
| #31 | Credential Vault intro | ✓ Done | foundation/Implementations/CredentialVault.php |
| #44 | EventEnvelope & event bus | ✓ Done | foundation/Implementations/EventEnvelope.php |
| #54 | WorkCore write authority | → Pending | WorkCore integration phase |
| #64 | Security & contract tests | → Pending | foundation/Tests/ |

---

## WAVE 2: Core Engines (In Progress)

**PR #214 - Being Built**  
**Issues**: #47, #49, #51, #68, #60, #61, #63, #58, #65

| # | Title | Status | Contract |
|---|-------|--------|----------|
| #47 | Knowledge Engine (Phase 1) | ✓ Contract | KnowledgeEngineContract.php |
| #49 | Tool Definitions & ExecutionContext | → Ready | ToolExecutionContract.php (next) |
| #51 | Provider Profiles & Health | → Ready | ProviderProfileContract.php (next) |
| #68 | Skill Runtime & Package Security | → Ready | SkillRuntimeContract.php (next) |
| #60 | Voice Engine (Phase 3) | ✓ Contract | VoiceEngineContract.php |
| #61 | Connector Runtime (Phase 5) | ✓ Contract | ConnectorRuntimeContract.php |
| #63 | Booking Engine (Phase 5) | → Ready | BookingEngineContract.php (next) |
| #58 | Customer Identity Engine (Phase 4) | → Ready | CustomerIdentityContract.php (next) |
| #65 | Commerce Contracts (Phase 5) | → Ready | CommerceContractContract.php (next) |

---

## WAVE 3: Critical Security (Scaffolding)

**PR #215 - To Be Created**  
**Issues**: #211, #42, #28, #25, #20, #17, #14, #66

| # | Title | Status | Implementation |
|---|-------|--------|-----------------|
| #211 | WhatsApp media quarantine | → Scaffolding | Media quarantine handler + tests |
| #42 | Secure remote media fetcher | → Scaffolding | SecureMediaFetcher contract + impl |
| #28 | Governed tool enforcement | → Scaffolding | ToolGovernancePolicy contract |
| #25 | Webhook verification boundary | ✓ Done | WebhookVerifierContract.php |
| #20 | PhoneCallAgent webhook hardening | → Scaffolding | PhoneCallWebhookSecure impl |
| #17 | FAL provider webhook consolidation | → Scaffolding | ProviderWebhookRegistry impl |
| #14 | Chatbot URL & file training secure | → Scaffolding | SecureTrainingProcessor impl |
| #66 | VideoEditor FFmpeg isolation | → Scaffolding | FFmpegSandbox impl |

---

## WAVE 4: Core Suite Migrations (Scaffolding)

**PR #216 - To Be Created**  
**Issues**: #9, #15, #16, #18, #19, #40, #21, #22, #23, #11, #13, #8

### AiChatPro Migrations
| # | Title | Status |
|---|-------|--------|
| #18 | Tenant-safe folder ownership | → Scaffolding |
| #19 | Deep Research durability & citations | → Scaffolding |
| #16 | Migrate FileChat to Knowledge Engine | → Scaffolding |
| #15 | Migrate Skills to Skill Runtime | → Scaffolding |
| #9 | Connectors under Pass 3 conformance | → Scaffolding |
| #40 | Connector registry upgrade | → Scaffolding |

### Chatbot Migrations
| # | Title | Status |
|---|-------|--------|
| #21 | Migrate Voice to Voice Engine | → Scaffolding |
| #22 | Migrate Booking to Booking Engine | → Scaffolding |
| #23 | WorkCore integration (Ecommerce) | → Ready for Wave 5 |
| #11 | Modularize while preserving identity | → Scaffolding |

### AIAgent Migrations
| # | Title | Status |
|---|-------|--------|
| #13 | Migrate to hardened Workflow Engine | → Scaffolding |
| #8 | Secure webhooks & tenant-scoped execution | → Scaffolding |

---

## WAVE 5: WorkCore Integrations (Scaffolding)

**PR #217 - To Be Created**  
**Issues**: #181-#186, #192-#204

### WorkCore Modules (Foundation + 5 Operational)
| # | Title | Status | Module |
|---|-------|--------|--------|
| #181 | Shared Foundation (Tenancy, Auth, Gov) | → Scaffolding | WorkCoreSharedFoundation |
| #182 | BusinessNetwork (CRM, Catalogue) | → Scaffolding | WorkCoreBusinessNetwork |
| #183 | Commercial (Finance, Payroll, Inventory) | → Scaffolding | WorkCoreCommercial |
| #184 | WorkOperations (Dispatch, Fleet) | → Scaffolding | WorkCoreWorkOperations |
| #185 | PropertyOperations (Assets, Documents) | → Scaffolding | WorkCorePropertyOperations |
| #186 | WorkforceAssurance (Compliance, NDIS) | → Scaffolding | WorkCoreWorkforceAssurance |

### Suite + WorkCore Integration Adapters
| # | Integration | Status |
|---|-------------|--------|
| #192 | AiChatPro + WorkCore (HR) | → Scaffolding |
| #193 | Chatbot + WorkCore (Foundation) | → Scaffolding |
| #194-#198 | Chatbot + WorkCore (5 modules) | → Scaffolding |
| #199 | AIAgent + WorkCore (Foundation) | → Scaffolding |
| #200-#204 | AIAgent + WorkCore (5 modules) | → Scaffolding |

---

## WAVE 6: Vertical Customization (Scaffolding)

**PR #218 - To Be Created**  
**Issues**: #205-#210

| # | Framework | Status | Purpose |
|---|-----------|--------|---------|
| #205 | Prompt Customization | → Scaffolding | Per-vertical AI prompt tuning |
| #206 | Template Management | → Scaffolding | Response, document, form templates |
| #207 | Forms Builder | → Scaffolding | Drag-drop form creation |
| #208 | Localization (i18n) | → Scaffolding | Multi-language, RTL support |
| #209 | Branding & Theming | → Scaffolding | White-label customization |
| #210 | Behavior Configuration | → Scaffolding | AI model tuning, guardrails |

---

## WAVE 7: Testing & Quality (Scaffolding)

**PR #219 - To Be Created**  
**Issues**: #7, #12, #37, #55, #69, #74, #75, #76

| # | Title | Status | Scope |
|---|-------|--------|-------|
| #7 | Architecture tests | → Scaffolding | Root, routes, migrations, manifests |
| #12 | Upgrade documentation | → Scaffolding | Pass 3 baseline reconciliation |
| #37 | Compatibility ownership | → Scaffolding | Module boundary definition |
| #55 | UnifiedMemory boundaries | → Scaffolding | Canonical data source enforcement |
| #69 | Progressive migrations | → Scaffolding | Shadow validation & rollback |
| #74 | Cross-suite integration tests | → Scaffolding | Chat, workflows, knowledge flows |
| #75 | Shared audit & observability | → Scaffolding | Correlation tracing, logging |
| #76 | Reproducible audit trail | → Scaffolding | Evidence-linked documentation |

---

## WAVE 8: Final Integration (Scaffolding)

**PR #220 - To Be Created**  
**Issues**: #6, #59, #34, and e-commerce passes

| # | Title | Status | Type |
|---|-------|--------|------|
| #6 | Core Epic - Production upgrade | → Summary | Overall integration summary |
| #59 | ChatbotEcommerce v4.9→v6.0 | → Scaffolding | Upgrade roadmap execution |
| #34 | ChatbotEcommerce host integration | → Scaffolding | MagicAI host integration |
| #38-#52 | E-commerce passes (15 issues) | → Individual | Pass-specific implementations |

---

## Deployment Strategy

### Phase 1: Foundation Layer (Week 1-2)
```
main ← PR #213 (Phase 0 foundation)
  ↓
Apply TenantContext to all repositories
Add tenant filters to all queries
```

### Phase 2: Core Engines (Week 2-3)
```
main ← PR #214 (Core engines)
  ↓
main ← PR #215 (Security hardening)
  ↓
Implement Knowledge, Voice, Connector engines
Harden all webhook endpoints
```

### Phase 3: Suite Migrations (Week 3-4)
```
main ← PR #216 (Suite migrations)
  ↓
Migrate AiChatPro, Chatbot, AIAgent to new patterns
Apply governance to all tool execution
```

### Phase 4: WorkCore Integration (Week 4-5)
```
main ← PR #217 (WorkCore modules + adapters)
  ↓
Enable autonomous operations on AIAgent
Enable WorkCore queries in Chatbot/AiChatPro
```

### Phase 5: Customization (Week 5-6)
```
main ← PR #218 (Vertical customization)
  ↓
Enable per-vertical AI tuning
Enable white-label theming
```

### Phase 6: Quality & Finalization (Week 6-7)
```
main ← PR #219 (Testing & quality)
main ← PR #220 (Final integration)
  ↓
Complete production readiness
Deploy to staging for E2E testing
```

---

## Success Metrics

✓ Phase 0: 4 of 11 issues complete  
→ Phase 1-2: 9 contracts scaffolded  
→ Phase 3: 8 security implementations scaffolded  
→ Phases 4-8: 54 remaining issues scaffolded  

**Target**: 8 PRs, 75 issues addressed, production-ready in 6-7 weeks

---

## Key Design Principles

1. **Tenant-First**: All operations respect tenant isolation
2. **Event-Driven**: All async work uses EventEnvelope for idempotency
3. **Credential-Safe**: All secrets use CredentialVault, no hardcoding
4. **Webhook-Secure**: All webhooks use WebhookVerifier
5. **Contract-Driven**: All extensions implement shared contracts
6. **Audit-Complete**: All actions recorded for compliance

---

## Related Documentation

- `foundation/README.md` - Foundation usage guide
- `foundation/ARCHITECTURE.md` - Complete architecture blueprint
- `.github/workflows/` - CI/CD pipeline configuration
