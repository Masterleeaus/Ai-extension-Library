# Issue #20: Harden PhoneCallAgent webhooks and ElevenLabs callbacks (Phase 1 Critical)

## Overview
Implement comprehensive security hardening for PhoneCallAgent webhook handling and ElevenLabs callbacks, including signature verification, replay prevention, state validation, and secure state persistence.

## Problem Statement
- Webhook spoofing: attackers can send fake call events
- Replay attacks: same webhook processed multiple times
- State tampering: call state modified without authorization
- Unencrypted storage: call data stored in plaintext
- Missing audit trail: no tracking of call operations

## Requirements

### Webhook Hardening
1. **Signature Verification**
   - Verify Twilio webhook signatures (HMAC-SHA1)
   - Verify ElevenLabs callback signatures
   - Reject unverified webhooks with 401/403
   - Log all verification failures

2. **Replay Prevention**
   - Track processed webhook IDs (idempotency)
   - Reject duplicate webhook IDs
   - Timestamp freshness enforcement (5 min max)

3. **State Validation**
   - Validate call state transitions
   - Verify state consistency
   - Prevent invalid state mutations
   - Audit all state changes

### Callback Security
1. **ElevenLabs Callback Handling**
   - Verify callback signatures
   - Validate callback payload schema
   - Handle partial/complete callbacks
   - Error recovery from missed callbacks

2. **Concurrent Call Management**
   - Lock-based consistency
   - Prevent race conditions
   - Handle simultaneous webhooks
   - Order preservation for causation

### State Persistence Security
1. **Encrypted State Storage**
   - Encrypt sensitive call data
   - Tenant-isolated storage
   - Immutable audit trail
   - Cleanup policy (TTL)

## Testing Requirements
- Unit tests for webhook verification
- Integration tests for callback handling
- Tests for replay prevention
- Tests for state consistency
- Tests for concurrent callbacks
- Security tests (signature spoofing, replay, state tampering)
- Performance tests (sub-100ms webhook processing)

## Acceptance Criteria
- ✅ All webhooks verified before processing
- ✅ Unverified webhooks rejected (401/403)
- ✅ Duplicate webhooks rejected (replay prevention)
- ✅ Call state transitions validated
- ✅ Sensitive data encrypted at rest
- ✅ Full audit trail maintained
- ✅ All tests pass (24+ assertions)
- ✅ Performance: <100ms per webhook

## Related Issues
- Depends on: #143, #144, #145, #146 (Phase 0 Foundation)
- Blocks: #67 (AIAgent hardening)
- Works with: #211 (WhatsApp media quarantine)

## Files to Create
- `app/Domains/PhoneCallAgent/Webhooks/TwilioWebhookHandler.php`
- `app/Domains/PhoneCallAgent/Webhooks/ElevenLabsCallbackHandler.php`
- `app/Domains/PhoneCallAgent/State/CallState.php`
- `app/Domains/PhoneCallAgent/State/CallStateValidator.php`
- `app/Domains/PhoneCallAgent/Encryption/CallDataEncryption.php`
- `tests/Feature/PhoneCallAgent/WebhookSecurityTest.php`
- `tests/Feature/PhoneCallAgent/CallStateConsistencyTest.php`
- `tests/Feature/PhoneCallAgent/ConcurrentCallHandlingTest.php`

## Timeline
- **Phase 1 Critical Security**
- Start after: All Phase 0 Foundation issues
- Estimated effort: 4 days
