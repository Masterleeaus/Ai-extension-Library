# Issue #146: Webhook Verification & Replay Prevention (Phase 0 Foundation)

## Overview
Implement comprehensive webhook signature verification and replay prevention to ensure only authorized webhooks are processed and prevent replay attacks from malicious actors.

## Problem Statement
- Webhooks can be spoofed (fake POST requests with modified data)
- Replay attacks: attacker re-sends valid webhook multiple times
- Different providers use different signature algorithms
- No protection against timestamp manipulation

## Requirements

### Webhook Verification Strategy
1. **Provider-Specific Signature Validation**
   - Stripe: HMAC-SHA256 header validation
   - Slack: verification token or HMAC-SHA256
   - Twilio: HMAC-SHA1 validation
   - Custom: HMAC-SHA256 with shared secret

2. **Timestamp Freshness Check**
   - Reject if timestamp >5 minutes old (configurable)
   - Prevent offline signature replay
   - Timestamp from webhook body or header

3. **Nonce/Request ID Tracking**
   - Store processed webhook IDs (idempotency)
   - Reject duplicate webhook IDs (same request)
   - TTL-based cleanup of old nonces

4. **Signature Algorithms**
   - Support HMAC-SHA1, HMAC-SHA256, HMAC-SHA512
   - Public key signatures (RSA, Ed25519) for webhooks
   - Configurable per provider

### Implementation

1. **WebhookVerifier Interface**
   - `verify(request: Request): bool`
   - `getProvider(): string`
   - `getSignature(): string`

2. **Provider Implementations**
   - `StripeWebhookVerifier`
   - `SlackWebhookVerifier`
   - `TwilioWebhookVerifier`
   - `GenericHmacWebhookVerifier`

3. **ReplayPreventionRepository**
   - Store processed webhook IDs
   - Check if ID already processed
   - Auto-cleanup expired entries

4. **WebhookVerificationMiddleware**
   - Automatically verify all webhook requests
   - Reject invalid signatures
   - Log all verification attempts

## Testing Requirements
- Unit tests for each provider's signature verification
- Tests for timestamp validation (fresh/stale)
- Tests for replay prevention (duplicate IDs rejected)
- Tests for invalid signatures (rejected)
- Tests for provider-specific headers/formats
- Integration tests for webhook processing
- Security tests (timing attack resistance)

## Acceptance Criteria
- ✅ All provider signatures verified correctly
- ✅ Stale timestamps rejected (>5 min old)
- ✅ Duplicate webhook IDs rejected
- ✅ Invalid signatures rejected with 401/403
- ✅ Replay prevention working (no duplicate processing)
- ✅ Performance: verification <10ms
- ✅ All tests pass (24+ assertions)

## Related Issues
- Depends on: #144 (EventEnvelope), #145 (Credential Vault)
- Blocks: #20 (PhoneCallAgent hardening needs this)
- Blocks: #211 (WhatsApp media quarantine needs this)

## Files to Create
- `app/Domains/Shared/Webhooks/WebhookVerifier.php` (Interface)
- `app/Domains/Shared/Webhooks/StripeWebhookVerifier.php`
- `app/Domains/Shared/Webhooks/SlackWebhookVerifier.php`
- `app/Domains/Shared/Webhooks/TwilioWebhookVerifier.php`
- `app/Domains/Shared/Webhooks/GenericHmacWebhookVerifier.php`
- `app/Domains/Shared/Webhooks/ReplayPreventionRepository.php` (Interface)
- `app/Domains/Shared/Webhooks/WebhookVerificationMiddleware.php`
- `tests/Feature/Webhooks/WebhookVerificationTest.php`
- `tests/Feature/Webhooks/ReplayPreventionTest.php`

## Timeline
- **Phase 0 Foundation**
- Start after: #144, #145
- Estimated effort: 3 days
