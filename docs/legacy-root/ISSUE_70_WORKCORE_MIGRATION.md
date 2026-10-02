# Issue #70: Migrate Chatbot Tier-3 and AIAgent operational actions to WorkCore gateways (Phase 2 Urgent)

## Overview
Implement migration of operational actions from Chatbot and AIAgent to unified WorkCore gateway layer, enabling shared business logic across verticals (Health, E-commerce, Real Estate, Field Services).

## Problem Statement
- Operational logic duplicated across extensions
- Vertical-specific logic hard to maintain
- Difficult to support new verticals
- Integration points scattered
- No shared audit trail

## Requirements

### WorkCore Gateway Layer
1. **Unified Interface**
   - Common ActionRequest/ActionResponse types
   - Standardized error handling
   - Tenant context propagation
   - Permission enforcement

2. **Action Categories**
   - Business Network actions (CRM, contacts, accounts)
   - Commercial actions (invoices, payments, quotes)
   - Work Operations actions (scheduling, dispatch)
   - Property Operations actions (premises, assets)
   - Workforce Assurance actions (compliance, training)

### Chatbot Migration
1. **Migrate Tier-3 Actions**
   - Map existing actions to WorkCore schema
   - Implement adapter for legacy compatibility
   - Update chatbot flow definitions
   - Test end-to-end interaction

2. **Validation**
   - Verify equivalent behavior
   - Performance testing
   - Concurrency testing
   - Error scenario testing

### AIAgent Migration
1. **Migrate Workflow Actions**
   - Map existing actions to WorkCore schema
   - Implement action adapters
   - Update workflow definitions
   - Test execution

2. **State Preservation**
   - Migrate pending workflow state
   - No loss of context
   - Audit trail continued

### Vertical Support
1. **Health Vertical**
   - Patient records
   - Appointment scheduling
   - Medical billing

2. **E-commerce Vertical**
   - Order processing
   - Inventory management
   - Customer communications

3. **Real Estate Vertical**
   - Property listings
   - Lead management
   - Transaction tracking

4. **Field Services Vertical**
   - Job scheduling
   - Field crew dispatch
   - Service completion tracking

## Testing Requirements
- Unit tests for action adapters
- Integration tests for migration
- Vertical-specific tests
- Performance tests (no degradation)
- Backward compatibility tests
- End-to-end workflow tests

## Acceptance Criteria
- ✅ All Chatbot Tier-3 actions migrated
- ✅ All AIAgent actions migrated
- ✅ Backward compatibility maintained
- ✅ Performance equivalent or better
- ✅ All verticals supported
- ✅ Unified audit trail
- ✅ All tests pass (32+ assertions)

## Related Issues
- Depends on: #143-146, #20, #67, #68
- Blocks: #71 (Feature flags for gradual rollout)
- Related: #181-186 (WorkCore modules)

## Timeline
- **Phase 2 Urgent Production**
- Start after: #67, #68
- Estimated effort: 6 days
