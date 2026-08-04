# AI Extensions - Complete Architecture Blueprint

**Status**: Foundational Contracts & Scaffolding Complete  
**Total Issues**: 75  
**Phases**: 8 Waves  
**Implementation Approach**: Contract-first with rapid scaffolding  

---

## Architecture Layers

```
┌─────────────────────────────────────────────────────────┐
│         Core Suites (AiChatPro, Chatbot, AIAgent)       │
├─────────────────────────────────────────────────────────┤
│  Vertical Customization (Prompts, Templates, Localization)
├─────────────────────────────────────────────────────────┤
│     WorkCore Integration (6 modules + 3 suite adapters)  │
├─────────────────────────────────────────────────────────┤
│  Core Engines (Knowledge, Voice, Connector, Booking, ...)
├─────────────────────────────────────────────────────────┤
│   Phase 0 Foundation (TenantContext, Events, Vault, ...)
├─────────────────────────────────────────────────────────┤
│            Shared Infrastructure & Contracts            │
└─────────────────────────────────────────────────────────┘
```

---

## Core Contracts & Responsibilities

### Phase 0: Foundation Layer (Complete ✓)

**Tenant Isolation & Security**
- `TenantContextContract` - Tenant ID, user identity, permissions
- `AuthorizationPolicyContract` - Fine-grained access control
- `WebhookVerifierContract` - Webhook validation & replay prevention

**Reliable Event Processing**
- `EventEnvelopeContract` - Idempotent event wrapping
- `CredentialVaultContract` - Secure secret management

### Phase 1-2: Core Engines (Scaffolded)

**Knowledge Management**
- `KnowledgeEngineContract` - Document ingestion, retrieval, citation
- Used by: AiChatPro (file chat), Chatbot (memory), AIAgent (knowledge)

**Communication Channels**
- `VoiceEngineContract` - TTS/STT, voice sessions
- `ConnectorRuntimeContract` - Gmail, Slack, WhatsApp integration
- Used by: ChatbotVoice, PhoneCallAgent, all channel extensions

**Business Operations**
- `BookingEngineContract` (planned) - Appointment scheduling
- `CustomerIdentityContract` (planned) - Cross-extension identity
- `SkillRuntimeContract` (planned) - Shared skills framework
- `ToolExecutionContract` (planned) - Governed tool execution

### Phase 3-6: Advanced Engines (To Scaffold)

- Commerce contracts (payments, inventory, pricing)
- Workflow engine (orchestration, approvals)
- Migration engine (shadow reads, progressive rollout)
- Audit & observability engine

---

## WorkCore Integration Model

```
┌──────────────────────────────────────────────────┐
│            Core Suites                           │
│  (AiChatPro, Chatbot, AIAgent)                   │
└────────────────────┬─────────────────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
    ┌───▼──┐    ┌────▼─┐    ┌───▼──┐
    │ HR   │    │Ops   │    │CRM   │
    │Async.│    │Async.│    │Async.│
    └───┬──┘    └────┬─┘    └───┬──┘
        │            │           │
        └────────────┼───────────┘
                     │
      ┌──────────────▼──────────────┐
      │    WorkCore Modules         │
      │ (6 domain-specific modules) │
      └──────────────┬──────────────┘
                     │
      ┌──────────────▼──────────────┐
      │   Phase 0 Foundation        │
      │ (Tenancy, Auth, Events)     │
      └─────────────────────────────┘
```

---

## Implementation Strategy

### WAVE 1: Phase 0 Foundation ✓ (Done - PR #213)
- TenantContext, AuthPolicy, EventEnvelope, Vault, WebhookVerifier
- All contracts + implementations ready
- Status: Merged to main

### WAVE 2: Core Engines (In Progress)
- Knowledge Engine contract
- Voice Engine contract
- Connector Runtime contract
- Booking Engine contract (scaffold)
- Customer Identity contract (scaffold)

### WAVE 3: Critical Security (Next)
- WhatsApp media quarantine
- Secure remote media fetcher
- Governed tool enforcement
- FFmpeg isolation

### WAVE 4: Core Suite Migrations (Following)
- AiChatPro hardening (folder ownership, deep research, file migration)
- Chatbot modularization (voice, booking migrations)
- AIAgent workflow hardening

### WAVE 5: WorkCore Integration (After)
- 6 WorkCore modules with domain-specific functionality
- 18 suite + WorkCore integration bridges

### WAVE 6: Vertical Customization (After)
- Prompt customization framework
- Template management
- Forms builder
- Localization (i18n)
- Branding & theming
- Behavior configuration

### WAVE 7: Testing & Quality (After)
- Architecture test suite
- Cross-suite integration tests
- Security test coverage
- Audit trail & observability

### WAVE 8: Final Integration (Last)
- ChatbotEcommerce v6.0.0 upgrade
- All pass-specific features
- Production readiness

---

## Contract Application Patterns

### Tenant-Safe Repository Pattern
```php
class Repository {
    public function __construct(TenantContextContract $context) {}
    
    public function getById($id) {
        return Model::where('tenant_id', $this->context->getTenantId())
                   ->where('id', $id)->first();
    }
}
```

### Idempotent Event Handler Pattern
```php
class EventListener {
    public function handle(EventEnvelopeContract $event) {
        if ($event->isProcessed()) return;
        
        // Process event...
        
        $event->markProcessed(new DateTime());
    }
}
```

### Secure Webhook Pattern
```php
class WebhookController {
    public function handle(WebhookVerifierContract $verifier) {
        if (!$verifier->verify('provider', $payload, $signature)) {
            throw new UnauthorizedException();
        }
        
        if (!$verifier->checkReplay('provider', $eventId)) {
            return response('Duplicate', 409);
        }
        
        $tenant = $verifier->resolveTenant('provider', $payload);
        // Process for tenant...
    }
}
```

---

## Files Created

### Phase 0 Foundation (12 files)
- 5 Contracts
- 5 Implementations
- README + Architecture guide

### Phase 1-2 Engines (3 contracts so far)
- KnowledgeEngineContract
- VoiceEngineContract
- ConnectorRuntimeContract

### Next Commits
- Additional WAVE 2 contracts
- WAVE 3 security scaffolding
- WAVE 4-8 frameworks

---

## Success Criteria

- [x] Phase 0 foundation complete with all contracts & implementations
- [ ] WAVE 2 core engines scaffolded
- [ ] WAVE 3 security contracts defined
- [ ] WAVE 4 core suite patterns established
- [ ] WAVE 5 WorkCore module framework
- [ ] WAVE 6 vertical customization patterns
- [ ] WAVE 7 testing infrastructure
- [ ] WAVE 8 integration complete

---

## Related GitHub Issues

**Phase 0**: #143, #144, #145, #146, #150, #71, #24, #31, #44, #54, #64  
**Engines**: #47, #49, #51, #68, #60, #61, #63, #58, #65  
**Security**: #211, #42, #28, #25, #20, #17, #14, #66  
**Suites**: #9-#23, #40, #8, #13  
**WorkCore**: #181-#186, #192-#204  
**Customization**: #205-#210  
**Quality**: #7, #12, #37, #55, #69, #74, #75, #76  
**Epic**: #6, #59, #34  

---

**Next Step**: Continue WAVE 2 with additional core engine contracts and implementations.
