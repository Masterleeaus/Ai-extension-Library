# Issue #67: Harden AIAgent Workflow Engine for durable production execution (Phase 2 Urgent)

## Overview
Implement comprehensive hardening for AIAgent workflow engine to ensure durable, reliable production execution with proper error recovery, state persistence, timeout handling, and observability.

## Problem Statement
- Workflows fail without proper recovery mechanisms
- State lost on process crash/restart
- No timeout enforcement for stuck workflows
- Poor observability of workflow execution
- Missing error handling for partial failures
- No graceful degradation mechanisms

## Requirements

### Durable State Management
1. **Persistent Workflow State**
   - All workflow state persisted to database
   - State recoverable after restart
   - Atomic state transitions
   - Version tracking for rollback

2. **Checkpoint System**
   - Checkpoints after each action
   - Resume from checkpoint on failure
   - Checkpoint cleanup (TTL)

### Error Recovery
1. **Retry Logic**
   - Exponential backoff (1s, 2s, 4s, 8s, ...)
   - Max retries per action (configurable)
   - Retry budget (don't retry forever)
   - Failed action isolation

2. **Circuit Breaker**
   - Track consecutive failures
   - Open circuit after threshold (10 failures)
   - Auto-recover after cooldown (5 min)
   - Manual reset capability

3. **Dead Letter Queue**
   - Failed workflows moved to DLQ
   - Manual intervention possible
   - Audit trail captured
   - Retry possible after fix

### Timeout Enforcement
1. **Per-Action Timeouts**
   - Each action has timeout (configurable)
   - Force termination at timeout
   - Preserve partial results
   - Log timeout events

2. **Overall Workflow Timeout**
   - Maximum workflow duration enforced
   - Graceful shutdown at timeout
   - Cleanup of resources

### Observability
1. **Structured Logging**
   - All workflow events logged
   - JSON structured format
   - Correlation IDs for tracing
   - Log levels (DEBUG, INFO, WARN, ERROR)

2. **Metrics**
   - Workflow execution duration
   - Action success/failure rates
   - Retry counts
   - Circuit breaker status

3. **Tracing**
   - Distributed tracing (OpenTelemetry)
   - Span per action
   - Causation chains visible

## Testing Requirements
- Unit tests for state persistence
- Tests for error recovery mechanisms
- Tests for timeout enforcement
- Tests for concurrent workflow execution
- Tests for observability (logging, metrics)
- Integration tests for end-to-end durability
- Failure scenario tests (crash, restart)
- Performance tests (large workflows)

## Acceptance Criteria
- ✅ Workflow state persisted atomically
- ✅ Workflows resume after restart
- ✅ Error recovery working (retries, circuit breaker)
- ✅ Timeouts enforced (per-action and overall)
- ✅ Dead letter queue operational
- ✅ Full observability (logs, metrics, traces)
- ✅ All tests pass (28+ assertions)
- ✅ Performance: no degradation with large workflows

## Related Issues
- Depends on: #143, #144, #145, #146, #20
- Blocks: #70, #71 (Phase 2 migration)
- Works with: #68 (Skill Runtime)

## Timeline
- **Phase 2 Urgent Production**
- Start after: Phase 1 critical issues
- Estimated effort: 5 days
