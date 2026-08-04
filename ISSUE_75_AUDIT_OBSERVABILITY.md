# Issue #75: Add shared audit, observability, correlation tracing and retention controls (Foundation)

## Overview
Implement comprehensive audit logging, observability infrastructure, distributed tracing, and data retention policies to enable production monitoring, debugging, and compliance.

## Requirements

### Audit Logging
1. **Audit Events**
   - User actions (create, read, update, delete)
   - Authorization decisions
   - Credential access
   - Webhook processing
   - Workflow execution
   - Error events

2. **Audit Trail**
   - Immutable audit records
   - Tenant isolation
   - Actor identification
   - Timestamp accuracy
   - Change tracking (old→new values)

3. **Audit Repository**
   - Query by tenant, date range, actor, action
   - Export audit logs (CSV, JSON)
   - Retention policy enforcement

### Distributed Tracing
1. **OpenTelemetry Integration**
   - Trace context propagation
   - Span per operation
   - Span attributes (tenant, user, error)
   - Trace sampling

2. **Exporters**
   - Jaeger exporter (development)
   - OTLP exporter (production)
   - Console exporter (debugging)

### Observability
1. **Metrics**
   - Request latency (p50, p95, p99)
   - Error rates (by type)
   - Queue depths
   - Database performance
   - Cache hit rates

2. **Logging**
   - Structured logging (JSON)
   - Log levels (DEBUG, INFO, WARN, ERROR)
   - Context propagation (correlation ID, tenant)
   - Performance logging

3. **Health Checks**
   - Database connectivity
   - Cache connectivity
   - Queue connectivity
   - Third-party API connectivity
   - Disk space available

### Data Retention
1. **Retention Policies**
   - Audit logs: 1 year (configurable)
   - Metrics: 90 days
   - Traces: 30 days
   - Call recordings: per-tenant policy
   - Webhook payloads: 7 days

2. **Cleanup**
   - Automatic cleanup via scheduled job
   - Manual cleanup capability
   - Compliance reporting (what was deleted)

3. **Archival**
   - Export to S3 before deletion
   - Encryption at rest
   - Long-term retention option

## Testing Requirements
- Unit tests for audit logging
- Tests for trace propagation
- Tests for retention enforcement
- Integration tests for observability stack
- Performance tests (logging overhead <2%)

## Acceptance Criteria
- ✅ Audit trail comprehensive and immutable
- ✅ Distributed tracing working
- ✅ Observability dashboards available
- ✅ Retention policies enforced
- ✅ Performance overhead <2%
- ✅ All tests pass (18+ assertions)

## Related Issues
- Depends on: #143-146
- Used by: All other issues (for observability)
- Blocks: None

## Timeline
- **Foundation**
- Start after: Phase 0 Foundation
- Estimated effort: 3 days
