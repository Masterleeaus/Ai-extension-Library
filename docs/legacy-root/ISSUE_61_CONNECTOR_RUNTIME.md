# Issue #61: Build shared Connector Runtime and migrate Gmail, Slack and WhatsApp adapters (Phase 5)

## Overview
Implement unified Connector Runtime with standardized adapter interface to consolidate email (Gmail), chat (Slack), and messaging (WhatsApp) integrations across all extensions.

## Problem Statement
- Connector logic scattered across extensions
- Duplicate adapter implementations
- Difficult to add new connectors
- No standardized error handling
- Missing rate limit enforcement

## Requirements

### Connector Runtime Architecture
1. **Unified Connector Interface**
   - Standard adapter contract
   - Connect/disconnect lifecycle
   - Standardized message types
   - Error handling framework
   - Rate limiting

2. **Adapter Types**
   - Synchronous adapters (direct API calls)
   - Asynchronous adapters (job queue)
   - Webhook-based adapters
   - Long-polling adapters
   - Bidirectional adapters

### Adapters to Migrate
1. **Gmail Adapter**
   - Send email
   - Receive email (webhooks)
   - Thread management
   - Attachment handling
   - Label management

2. **Slack Adapter**
   - Send messages
   - Receive messages (webhooks)
   - File uploads
   - Interactive components
   - User mentions

3. **WhatsApp Adapter**
   - Send messages
   - Receive messages (webhooks)
   - Media handling (with quarantine from #211)
   - Group management
   - Status updates

### Runtime Features
1. **Connection Management**
   - Credential storage (vault references)
   - Connection pooling
   - Connection health checks
   - Auto-reconnect logic

2. **Message Handling**
   - Standardized message envelope
   - Type coercion between adapters
   - Payload transformation
   - Error message standardization

3. **Rate Limiting**
   - Per-adapter rate limits
   - Per-tenant rate limits
   - Backoff strategies
   - Quota tracking

4. **Observability**
   - Adapter metrics (messages sent/received)
   - Error rates per adapter
   - Latency metrics
   - Connection status

### Dead Letter Queue
- Failed message handling
- Retry mechanism
- Manual intervention capability
- Audit trail

## Testing Requirements
- Unit tests for each adapter
- Integration tests for message flow
- Tests for rate limiting
- Tests for error handling
- Tests for retry logic
- Performance tests (message throughput)
- Security tests (credential handling)

## Acceptance Criteria
- ✅ Connector Runtime operational
- ✅ All adapters migrated
- ✅ Rate limiting enforced
- ✅ Credential vault integration
- ✅ Error recovery working
- ✅ Dead letter queue operational
- ✅ All tests pass (32+ assertions)
- ✅ Performance: <100ms message processing

## Related Issues
- Depends on: #143-146, #20, #67, #68, #70, #71
- Related: #211 (WhatsApp media quarantine)
- Blocks: None (standalone)

## Timeline
- **Phase 5 Connector Integration**
- Start after: #71
- Estimated effort: 5 days
