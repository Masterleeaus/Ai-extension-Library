# Issue #144: Implement EventEnvelope & Idempotent Event Consumers

**Status:** URGENT | Phase 0 | Foundation  
**Priority:** Critical  
**Effort:** 1-2 weeks  
**Depends on:** #143  
**Blocks:** #20, #67-71, #60, #61  

## Problem Statement

- Queue jobs, webhooks, event consumers lack idempotency keys
- Duplicate events can trigger duplicate charges, duplicate actions, or inconsistent state
- No standardized event structure across extensions
- Event tracing not correlated across services

## Solution Requirements

Define `EventEnvelope` contract with immutable event ID, correlation/causation IDs, and idempotency key. Implement idempotent event-consumer pattern.

## Deliverables

### 1. Define EventEnvelope Schema

```php
// app/Domains/Shared/Events/EventEnvelope.php

class EventEnvelope {
    // Immutable identification
    public string $eventId;              // UUID - immutable, globally unique
    public string $eventType;            // Class name or semantic type
    public string $schemaVersion;        // Version of event schema
    
    // Context & tracing
    public string $tenantId;             // From TenantContext
    public string $aggregateType;        // Domain object type (e.g., "AIAgentWorkflow")
    public string $aggregateId;          // ID of affected domain object
    public \DateTimeImmutable $occurredAt; // When event occurred
    
    // Actor information
    public ActorIdentity $actor;         // User, service, or webhook
    
    // Correlation & causation
    public string $correlationId;        // Trace ID across all services
    public string $causationId;          // ID of event that caused this one
    
    // Idempotency
    public string $idempotencyKey;       // For deduplication by consumer
    
    // Payload (compact, no credentials, no full models)
    public array $payload;               // Event-specific data
    public array $metadata;              // Additional context
    
    // Optional: for outbound event dispatch
    public ?string $externalId;          // Provider-specific reference
    public ?string $externalCorrelationId; // Provider's correlation ID
}
```

**Design constraints:**
- Payload must NOT contain:
  - Full model objects (use IDs only)
  - Credentials or secrets
  - Database snapshots
  - Large documents
- Payload MUST contain:
  - Relevant IDs
  - Changed field names
  - Before/after values for changed fields
  - Actor information

### 2. Define Actor Identity

```php
// app/Domains/Shared/Identity/ActorIdentity.php

class ActorIdentity {
    public enum ActorType {
        USER,
        SERVICE,
        WEBHOOK,
        SCHEDULED_JOB,
        SYSTEM
    }
    
    public ActorType $type;
    public string $id;                   // User ID, service name, webhook name
    public ?string $displayName;
    public array $permissions;           // Permissions of actor
    public \DateTimeImmutable $timestamp; // When action was performed
}
```

### 3. Implement Idempotency Ledger

```php
// app/Domains/Shared/Idempotency/IdempotencyLedger.php

interface IdempotencyLedger {
    /**
     * Record an idempotency key and its result
     */
    public function recordIdempotentAction(
        string $tenantId,
        string $idempotencyKey,
        mixed $result,
        \DateTimeImmutable $expireAt
    ): void;
    
    /**
     * Check if idempotency key was already processed
     */
    public function hasBeenProcessed(string $tenantId, string $idempotencyKey): bool;
    
    /**
     * Get result of previous processing
     */
    public function getResult(string $tenantId, string $idempotencyKey): mixed;
    
    /**
     * Clean up expired entries
     */
    public function pruneExpired(\DateTimeImmutable $before): void;
}

// Implementation: app/Domains/Shared/Idempotency/DatabaseIdempotencyLedger.php
// - Store in database table: idempotency_ledger
// - Fields: tenant_id, idempotency_key, result (JSON), processed_at, expires_at
// - Index on: (tenant_id, idempotency_key)
```

### 4. Define Idempotent Event Consumer

```php
// app/Domains/Shared/Events/IdempotentEventConsumer.php

abstract class IdempotentEventConsumer {
    protected IdempotencyLedger $ledger;
    
    public function handle(EventEnvelope $event): void {
        // Check if already processed
        if ($this->ledger->hasBeenProcessed($event->tenantId, $event->idempotencyKey)) {
            // Return cached result - don't process again
            return $this->ledger->getResult($event->tenantId, $event->idempotencyKey);
        }
        
        // Process the event
        $result = $this->processEvent($event);
        
        // Record result for future retries
        $this->ledger->recordIdempotentAction(
            $event->tenantId,
            $event->idempotencyKey,
            $result,
            now()->addHours(24)  // Keep for 24 hours
        );
        
        return $result;
    }
    
    // Subclasses implement this
    abstract protected function processEvent(EventEnvelope $event): mixed;
}
```

### 5. Event Publisher Updates

```php
// app/Domains/Shared/Events/EventPublisher.php

class EventPublisher {
    public function publish(DomainEvent $event): void {
        $envelope = EventEnvelope::fromDomainEvent($event);
        
        // Ensure required fields
        $envelope->ensureIdempotencyKey();  // Generate if missing
        $envelope->ensureCorrelationId();   // Inherit or generate
        $envelope->ensureCausationId();     // Set if this is response to another
        
        // Publish to event bus
        $this->eventBus->publish($envelope);
        
        // Log for audit
        $this->auditLog->record($envelope);
    }
}
```

### 6. Event Replay Prevention

```php
// app/Domains/Shared/Events/EventReplayPrevention.php

class EventReplayPrevention {
    // For webhook replays:
    // - Store webhook ID + timestamp + nonce
    // - Reject if nonce seen before
    
    // For queue replays:
    // - Use EventEnvelope.eventId as replay detection
    // - Idempotency ledger handles the rest
    
    // For cascade/integration replays:
    // - Correlation ID chains track causation
    // - No event processed twice with same causationId + same service
}
```

## Exit Criteria (All must pass)

- ✅ EventEnvelope schema defined with all required fields
- ✅ IdempotencyLedger implemented
- ✅ IdempotentEventConsumer base class working
- ✅ All event publishers use EventEnvelope
- ✅ Event consumers extend IdempotentEventConsumer
- ✅ Replay webhook events produce identical results
- ✅ Correlation tracing works across services
- ✅ Idempotency tests pass for all event-consuming extensions

## Testing Requirements

1. **EventEnvelope Tests:**
   - UUID generation and immutability
   - Correlation ID inheritance
   - Payload validation (no credentials, no full models)

2. **Idempotency Tests:**
   - Duplicate events produce same result
   - Result caching works
   - Expiration of old entries

3. **Event Replay Tests:**
   - Replayed webhook events don't duplicate records
   - Replayed queue jobs don't duplicate actions
   - Replayed integration events merge cleanly

4. **Correlation Tests:**
   - Causation chain preserved
   - Correlation ID visible in logs
   - Multi-service traces work

## Implementation Phases

### Phase 1: EventEnvelope & Ledger (Day 1-3)
- Create EventEnvelope class
- Create ActorIdentity class
- Implement IdempotencyLedger in database

### Phase 2: Consumer Pattern (Day 4-5)
- Create IdempotentEventConsumer base
- Update existing consumers to extend it
- Add idempotency key generation

### Phase 3: Publisher Updates (Day 6-7)
- Update EventPublisher to use EventEnvelope
- Add correlation/causation ID support
- Update all event-emitting code

### Phase 4: Replay Prevention (Day 8-9)
- Implement replay detection
- Add webhook nonce validation
- Test replay scenarios

### Phase 5: Testing & Cleanup (Day 10-14)
- Write comprehensive idempotency tests
- Write replay prevention tests
- Fix any integration issues

## Database Migrations

```sql
-- idempotency_ledger table
CREATE TABLE idempotency_ledger (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id VARCHAR(255) NOT NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    result LONGTEXT,  -- JSON
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    UNIQUE KEY unique_idempotency (tenant_id, idempotency_key),
    INDEX idx_expires_at (expires_at)
);

-- event_trace table (optional, for audit)
CREATE TABLE event_trace (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id VARCHAR(255) NOT NULL,
    event_id VARCHAR(255) NOT NULL,
    correlation_id VARCHAR(255),
    causation_id VARCHAR(255),
    aggregate_type VARCHAR(255),
    aggregate_id VARCHAR(255),
    event_type VARCHAR(255),
    actor_type VARCHAR(255),
    actor_id VARCHAR(255),
    occurred_at TIMESTAMP,
    UNIQUE KEY unique_event (event_id),
    INDEX idx_correlation (correlation_id),
    INDEX idx_causation (causation_id),
    INDEX idx_aggregate (aggregate_type, aggregate_id)
);
```

## Files to Create/Modify

```
app/Domains/Shared/Events/
  └── EventEnvelope.php (NEW)
  └── EventEnvelopeFactory.php (NEW)
  └── IdempotentEventConsumer.php (NEW)

app/Domains/Shared/Identity/
  └── ActorIdentity.php (NEW)

app/Domains/Shared/Idempotency/
  └── IdempotencyLedger.php (NEW - interface)
  └── DatabaseIdempotencyLedger.php (NEW)

app/Domains/Shared/Events/
  └── EventPublisher.php (MODIFY)
  └── EventReplayPrevention.php (NEW)

database/migrations/
  └── 2026_08_04_create_idempotency_ledger.php (NEW)
  └── 2026_08_04_create_event_trace.php (NEW)

tests/Feature/Events/
  └── EventEnvelopeTest.php (NEW)
  └── IdempotencyTest.php (NEW)
  └── EventReplayTest.php (NEW)
```

## Acceptance Criteria Checklist

- [ ] EventEnvelope class with all required fields
- [ ] ActorIdentity tracking implemented
- [ ] IdempotencyLedger database table created
- [ ] IdempotentEventConsumer base class works
- [ ] All event publishers emit EventEnvelopes
- [ ] All event consumers use IdempotentEventConsumer
- [ ] Duplicate events handled correctly
- [ ] Correlation IDs tracked through services
- [ ] Replay scenarios tested
- [ ] No regression in existing functionality
- [ ] Documentation updated

## Related Issues

- #143: TenantContext & Authorization Policies
- #75: Audit and observability
- #64: Smoke and contract tests

## Next Steps

1. Create EventEnvelope schema
2. Implement IdempotencyLedger
3. Create IdempotentEventConsumer
4. Update all event publishers
5. Update all event consumers
6. Write comprehensive tests
7. Merge to main
