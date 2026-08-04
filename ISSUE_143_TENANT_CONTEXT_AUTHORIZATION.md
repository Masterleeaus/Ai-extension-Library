# Issue #143: Implement TenantContext & Authorization Policies

**Status:** URGENT | Phase 0 | Foundation  
**Priority:** Critical  
**Effort:** 1-2 weeks  
**Depends on:** None  
**Blocks:** #144, #145, #146, #147-150, #20, #67-71, #60, #61  

## Problem Statement

Cross-tenant data leakage risk through missing tenant filters in:
- Query repositories and event consumers
- Webhook processing
- Authorization checks for tool execution, workflow actions, connector usage, and knowledge ingestion

Current state: Tenant ID not enforced on authoritative domain records and events

## Solution Requirements

Implement shared `TenantContext` and authorization policy interfaces that all extensions must use.

## Deliverables

### 1. Define TenantContext Contract
```php
// app/Domains/Shared/Context/TenantContext.php
interface TenantContext {
    public function getTenantId(): string;
    public function getUserIdentity(): UserIdentity;
    public function getActor(): Actor;
    public function getPermissions(): array;
    public function getPolicyVersion(): string;
}
```

**Requirements:**
- Immutable tenant ID
- User identity resolution
- Actor context (service, user, webhook)
- Permission array for authorization checks
- Policy version tracking

### 2. Define AuthorizationPolicy Interfaces
```php
// app/Domains/Shared/Authorization/AuthorizationPolicy.php

interface ToolAuthorizationPolicy {
    public function canExecuteTool(TenantContext $context, Tool $tool): bool;
    public function getToolExecutionLimits(TenantContext $context, Tool $tool): ExecutionLimits;
}

interface WorkflowAuthorizationPolicy {
    public function canExecuteAction(TenantContext $context, WorkflowAction $action): bool;
}

interface ConnectorAuthorizationPolicy {
    public function canUseConnector(TenantContext $context, Connector $connector): bool;
}

interface KnowledgeAuthorizationPolicy {
    public function canIngestKnowledge(TenantContext $context, KnowledgeSource $source): bool;
}
```

### 3. Add Tenant Filters to Query Repositories

**Affected repositories:**
- AIAgentWorkflowRepository
- AIAgentConversationRepository
- AIAgentMemoryRepository
- ChatbotConversationRepository
- AIChatProChatRepository
- All other domain repositories

**Changes:**
```php
public function getForTenant(string $tenantId): Collection {
    return $this->query
        ->where('tenant_id', $tenantId)
        ->get();
}

// All queries must enforce tenant filter
```

### 4. Add tenant_id to Authoritative Domain Events

**Required changes:**
- Define `DomainEvent` base class with tenant_id field
- Update all event publishers to include tenant context
- Add tenant ID to event envelope

**Event structure:**
```php
class DomainEvent {
    public string $eventId;
    public string $tenantId;  // ADD THIS
    public string $eventType;
    public string $aggregateType;
    public string $aggregateId;
    public \DateTimeImmutable $occurredAt;
    public array $payload;
}
```

### 5. Implement Tenant Resolution for Webhook Processing

**Requirements:**
- Extract tenant ID from webhook headers
- Validate webhook signature includes tenant
- Resolve tenant from webhook URL/path
- Enforce tenant on all webhook event mutations

**Affected webhooks:**
- PhoneCallAgent Twilio webhooks
- AIAgentGmail webhooks
- AIAgentSlackChannel webhooks
- AIAgentWhatsappChannel webhooks
- All provider webhooks

## Exit Criteria (All must pass)

- ✅ TenantContext contract implemented and used in all extensions
- ✅ All query repositories include mandatory tenant filter
- ✅ All events carry tenant_id field
- ✅ Webhook processing resolves and validates tenant
- ✅ Cross-tenant repository isolation tests pass
- ✅ No query results leak across tenant boundaries
- ✅ Authorization policies enforced for tool/action/connector/knowledge access

## Testing Requirements

1. **Isolation Tests:**
   - Verify queries for tenant A don't return tenant B data
   - Test webhook payload filtering by tenant
   - Verify event consumers only process own-tenant events

2. **Authorization Tests:**
   - Tool execution requires authorization
   - Workflow actions require authorization
   - Connector usage requires authorization
   - Knowledge ingestion requires authorization

3. **Tenant Resolution Tests:**
   - Webhook header extraction works
   - URL-based tenant resolution works
   - Invalid tenant IDs rejected

## Implementation Phases

### Phase 1: Core Contracts (Day 1-2)
- Create TenantContext interface
- Create AuthorizationPolicy interfaces
- Add tenant_id to DomainEvent base

### Phase 2: Repository Updates (Day 3-5)
- Add tenant filters to all repositories
- Update all query methods
- Add isolation tests

### Phase 3: Event Updates (Day 6-7)
- Add tenant_id to all events
- Update event publishers
- Update event consumers

### Phase 4: Webhook Integration (Day 8-10)
- Update webhook handlers
- Add tenant resolution
- Add webhook verification tests

### Phase 5: Testing & Cleanup (Day 11-14)
- Write comprehensive isolation tests
- Write authorization tests
- Fix any integration issues

## Related Issues

- #54: WorkCore gateways
- #75: Audit and observability
- #144: EventEnvelope & Idempotent Event Consumers
- #145: Credential Vault References
- #146: Webhook Verification & Replay Prevention

## Files to Create/Modify

```
app/Domains/Shared/Context/
  └── TenantContext.php (NEW)
  └── TenantContextImpl.php (NEW)

app/Domains/Shared/Authorization/
  └── AuthorizationPolicy.php (NEW - interfaces)
  └── ToolAuthorizationPolicy.php (NEW)
  └── WorkflowAuthorizationPolicy.php (NEW)
  └── ConnectorAuthorizationPolicy.php (NEW)
  └── KnowledgeAuthorizationPolicy.php (NEW)

app/Domains/Shared/Events/
  └── DomainEvent.php (MODIFY - add tenant_id)
  └── EventPublisher.php (MODIFY - enforce tenant)

extensions/AIAgent/System/Repositories/
  └── *.php (MODIFY - add tenant filters)

extensions/Chatbot/System/Repositories/
  └── *.php (MODIFY - add tenant filters)

extensions/AIChatPro/System/Repositories/
  └── *.php (MODIFY - add tenant filters)

tests/Feature/TenantContext/
  └── TenantIsolationTest.php (NEW)
  └── AuthorizationPolicyTest.php (NEW)
  └── WebhookTenantResolutionTest.php (NEW)
```

## Acceptance Criteria Checklist

- [ ] TenantContext interface defined
- [ ] AuthorizationPolicy interfaces defined
- [ ] All repositories updated with tenant filters
- [ ] All events include tenant_id
- [ ] Webhook handlers resolve tenant
- [ ] Isolation tests pass
- [ ] Authorization tests pass
- [ ] No regression in existing functionality
- [ ] Documentation updated

## Next Steps

1. Implement core TenantContext contract
2. Update all repository query methods
3. Add tenant_id to domain events
4. Update webhook handlers
5. Write comprehensive tests
6. Merge to main
