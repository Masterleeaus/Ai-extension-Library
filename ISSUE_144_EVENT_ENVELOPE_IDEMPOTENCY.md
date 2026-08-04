# Issue #144: Event Envelope & Idempotent Event Consumers (Phase 0 Foundation)

## Overview
Implement an immutable EventEnvelope data structure and idempotent event consumer pattern to ensure exactly-once event processing semantics across distributed system components.

## Problem Statement
Without standardized event envelopes and idempotency guarantees, the system risks:
- Duplicate event processing (duplicate charges, duplicate workflows triggered)
- Lost event context (correlation chains for debugging)
- Non-deterministic event ordering
- Inability to replay events for recovery

## Requirements

### EventEnvelope Structure
```php
interface EventEnvelope {
    public function getEventId(): string;              // UUID, immutable
    public function getEventType(): string;            // e.g., "workflow.created"
    public function getTimestamp(): DateTimeImmutable; // When event occurred
    public function getCorrelationId(): string;        // Trace related events
    public function getCausationId(): ?string;         // Previous event that caused this
    public function getIdempotencyKey(): string;       // For replay detection
    public function getPayload(): array;               // Event data
    public function getTenantId(): string;             // Tenant isolation
    public function getActor(): ActorIdentity;         // Who/what triggered it
    public function getMetadata(): array;              // Custom metadata
}
```

### Idempotent Consumer Pattern
- Consumer must accept envelope with idempotencyKey
- Check if idempotencyKey already processed (via repository)
- If already processed, return cached result (no re-execution)
- If new, execute handler and store result with key
- All state mutations must be atomic with key storage

### Implementation Details

1. **EventEnvelopeImpl**
   - Immutable data class (all properties readonly)
   - Factory methods: `create()`, `fromPayload()`, `fromRequest()`
   - Automatic UUID generation for eventId
   - Timestamp captured at creation time

2. **IdempotencyKeyRepository**
   - Interface: `has(key: string): bool`, `store(key: string, result: array): void`, `get(key: string): ?array`
   - Implementation using database cache table
   - TTL-based cleanup (configurable)

3. **IdempotentEventConsumer**
   - Abstract base class with `consume(envelope): void` final
   - Subclasses implement `handleEvent(envelope): array`
   - Automatic idempotency checking in final consume method
   - Returns cached result if idempotency key exists

4. **EventDispatcher**
   - Envelops events before dispatching
   - Routes to registered consumers
   - Handles async dispatch with job queue

## Testing Requirements
- Unit tests for EventEnvelope immutability
- Unit tests for idempotency key caching
- Integration tests for end-to-end idempotency
- Tests for duplicate event handling (should not re-execute)
- Tests for correlated events (causation chains)
- Tests for event replay scenarios
- Performance tests (cache hit rate)

## Acceptance Criteria
- ✅ EventEnvelope is immutable (properties cannot be changed)
- ✅ Event IDs are unique (UUID v4)
- ✅ Idempotency keys prevent duplicate execution
- ✅ Correlation chains track causation
- ✅ Consumers automatically deduplicate via key
- ✅ All tests pass (21+ test assertions)
- ✅ Performance: idempotency cache hit is <1ms
- ✅ No event is processed twice for same key

## Related Issues
- Blocks: #145 (Credential Vault - uses envelopes)
- Blocks: #146 (Webhook Verification - validates envelopes)
- Blocked by: #143 (TenantContext required for tenant tracking)

## Files to Create
- `app/Domains/Shared/Events/EventEnvelope.php` (Interface)
- `app/Domains/Shared/Events/EventEnvelopeImpl.php` (Implementation)
- `app/Domains/Shared/Events/IdempotencyKeyRepository.php` (Interface)
- `app/Domains/Shared/Events/IdempotencyKeyRepositoryImpl.php` (Implementation)
- `app/Domains/Shared/Events/IdempotentEventConsumer.php` (Abstract base)
- `app/Domains/Shared/Events/EventDispatcher.php` (Implementation)
- `tests/Feature/Events/EventEnvelopeTest.php`
- `tests/Feature/Events/IdempotentEventConsumerTest.php`
- Database migration for `idempotency_keys` table

## Database Schema
```sql
CREATE TABLE idempotency_keys (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id VARCHAR(255) NOT NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    result JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    UNIQUE KEY unique_tenant_key (tenant_id, idempotency_key),
    INDEX idx_expires_at (expires_at)
);
```

## Timeline
- **Phase 0 Foundation** (Critical path blocker)
- Must complete before #145, #146
- Estimated effort: 4 days
- Start after: #143 (TenantContext)
