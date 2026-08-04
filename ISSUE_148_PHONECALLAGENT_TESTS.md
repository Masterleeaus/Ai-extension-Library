# Issue #148: Add PhoneCallAgent Conformance Test Suite (Phase 0 Testing)

## Overview
Create comprehensive conformance test suite for PhoneCallAgent to verify correct behavior of voice call operations, webhook processing, and integration with voice platforms (ElevenLabs, Twilio).

## Requirements

### Test Categories

1. **Call Lifecycle Tests** (8 tests)
   - Initiate call to phone number
   - Call connects successfully
   - Call fails (line busy, no answer)
   - Call times out (configurable)
   - Hang up call
   - Call disconnects unexpectedly
   - Call transfer between agents
   - Call recording lifecycle

2. **Voice Platform Integration Tests** (9 tests)
   - ElevenLabs TTS generation works
   - Twilio call initiation works
   - Webhook payload parsing correct
   - Webhook signature verification passes
   - Webhook replay prevention works
   - Transcript generation from call
   - Recording download works
   - Recording cleanup after call
   - Error recovery from provider failures

3. **State Management Tests** (7 tests)
   - Call state transitions valid
   - State persisted to database
   - State recoverable after restart
   - Concurrent calls handled correctly
   - Call context isolated per tenant
   - Call metadata tracked correctly
   - Call cleanup (garbage collection)

4. **Security Tests** (6 tests)
   - Webhook signature verified (Twilio/ElevenLabs)
   - Timestamp freshness enforced
   - Replay prevention working
   - Tenant isolation enforced
   - Permission checks on call operations
   - Sensitive data redacted in logs

5. **Error & Timeout Tests** (6 tests)
   - Network failures handled
   - Provider timeouts handled
   - Partial failure scenarios
   - Graceful degradation
   - Error recovery with retries
   - Circuit breaker engagement

## Test Coverage Goals
- 80%+ line coverage for call agents
- All webhook handlers tested
- All state transitions verified
- Security paths verified

## Files to Create
- `tests/Feature/PhoneCallAgent/CallLifecycleTest.php` (8 tests)
- `tests/Feature/PhoneCallAgent/VoicePlatformIntegrationTest.php` (9 tests)
- `tests/Feature/PhoneCallAgent/CallStateManagementTest.php` (7 tests)
- `tests/Feature/PhoneCallAgent/CallSecurityTest.php` (6 tests)
- `tests/Feature/PhoneCallAgent/CallErrorHandlingTest.php` (6 tests)

## Timeline
- **Phase 0 Testing**
- Start after: #143, #144, #145, #146
- Estimated effort: 3 days
