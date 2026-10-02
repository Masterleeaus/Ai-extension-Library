# Issue #64: Add native smoke, security and contract tests (Phase 0 Testing)

## Overview
Create native smoke, security, and contract tests to verify system health, security posture, and API contracts across all extensions and components.

## Requirements

### Smoke Tests (12 tests)
- Application boots without errors
- Database migrations applied successfully
- Cache systems operational
- Queue systems operational
- All middleware loads correctly
- All service providers boot correctly
- Health check endpoint returns 200
- Readiness check endpoint passes
- All extensions loaded
- Configuration loaded correctly
- Environment variables correct
- Port listeners active

### Security Tests (15 tests)
- HTTPS enforced in production
- CSRF tokens generated and validated
- XSS protection headers present
- SQL injection protection working
- Command injection protection working
- File upload validation working
- Directory traversal prevented
- Secrets not exposed in config
- Secrets not exposed in error messages
- Rate limiting working
- DDoS protection active
- Authentication required for protected endpoints
- Authorization checks applied
- Sensitive headers set correctly
- Credentials not logged

### Contract Tests (14 tests)
- TenantContext interface contract
- Authorization policy contract
- Event envelope contract
- Credential vault contract
- Webhook verifier contract
- AIAgent workflow contract
- PhoneCallAgent contract
- ChatbotVoice contract
- Voice Engine contract
- Connector Runtime contract
- WorkCore service contract
- Database schema contract
- API response contract
- Error response contract

## Test Coverage Goals
- Smoke tests: quick checks (<1s each)
- Security tests: comprehensive (<5s each)
- Contract tests: API/interface verification

## Files to Create
- `tests/Feature/Smoke/SystemHealthTest.php` (12 tests)
- `tests/Feature/Security/SecurityPostureTest.php` (15 tests)
- `tests/Feature/Contracts/InterfaceContractTest.php` (14 tests)

## Timeline
- **Phase 0 Testing**
- Start after: All Phase 0 Foundation issues
- Estimated effort: 2 days
