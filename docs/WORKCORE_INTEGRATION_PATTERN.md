# WorkCore Integration Pattern Guide

**Status**: Foundation Complete | **Last Updated**: 2026-08-04

This document describes the standardized pattern for integrating WorkCore modules with AiChatPro, Chatbot, and AIAgent extensions.

## Pattern Overview

All WorkCore integrations follow a consistent 3-layer architecture:

```
┌─────────────────────────────────────────┐
│    User-Facing Extension Layer          │
│  (AiChatPro UI, Chatbot Chat, AIAgent)  │
├─────────────────────────────────────────┤
│    Integration Service Layer            │
│   (Repository, Query Service)           │
├─────────────────────────────────────────┤
│    WorkCore Foundation Layer            │
│  (TenantContext, Authorization, Events) │
└─────────────────────────────────────────┘
```

## Layer 1: Tenant Context & Authorization (WorkCore Foundation)

**Already Implemented** ✅

- TenantContext: Enforces tenant isolation
- Authorization: Ensures actions are permitted
- EventEnvelope: Tracks causation and correlation
- Domain Events: Enables async, idempotent processing

## Layer 2: Integration Service Layer (Per Extension)

### AiChatPro Pattern

**File Structure:**
```
extensions/AIChatPro/System/Integration/
├── WorkCoreIntegrationProvider.php      (registered in AppServiceProvider)
├── TenantContextMiddleware.php          (enforces tenant boundary)
├── WorkCore/
│   ├── BusinessNetworkQueryService.php  (for CRM integration)
│   ├── CommercialQueryService.php       (for Commerce integration)
│   ├── WorkOperationsQueryService.php   (for Operations integration)
│   ├── PropertyOperationsQueryService.php
│   └── WorkforceAssuranceQueryService.php
```

**Example: BusinessNetworkQueryService**
```php
final class BusinessNetworkQueryService implements BusinessNetworkRepository {
    public function __construct(
        private TenantContext $tenantContext,
        private CompanyRecordAuthorizer $authorizer,
    ) {}

    public function getCustomerProfile(string $customerId): ?CustomerProfile {
        $tenantId = $this->tenantContext->companyId();
        
        // Query WorkCore BusinessNetwork module
        // Apply tenant filter automatically
        // Verify authorization
        // Return tenant-scoped results
    }
}
```

### Chatbot Pattern

Same as AiChatPro, but with PWA/WebSocket considerations:

- Optimize queries for conversational context
- Support offline-first caching
- Real-time updates via WebSocket
- Mobile-optimized response formats

### AIAgent Pattern

Enhanced with approval workflows and audit trails:

- Autonomous action execution with approval workflows
- Comprehensive audit logging for all actions
- Rate limiting and cost tracking
- Background job queuing for long-running operations

## Layer 3: User-Facing Features

### AiChatPro Features

For each WorkCore module integration:
- Dashboard displaying WorkCore data
- CRUD operations on WorkCore records
- Real-time synchronization
- Export and reporting

### Chatbot Features

For each WorkCore module integration:
- Conversational queries (e.g., "Show me orders from this month")
- Interactive forms for data entry
- Status queries and notifications
- Booking and request submission

### AIAgent Features

For each WorkCore module integration:
- Autonomous scheduling and optimization
- Intelligent notifications and alerts
- Workflow automation with approval gates
- Decision-making based on WorkCore data

## Integration Checklist (per Extension × Module Combination)

### For each of 18 combinations (3 extensions × 6 WorkCore modules):

**Configuration:**
- [ ] Service provider registers integration
- [ ] Tenant context middleware added
- [ ] Authorization policies defined

**Data Access:**
- [ ] Query service implements WorkCore contracts
- [ ] Tenant filters applied to all queries
- [ ] Caching strategy defined

**Mutations:**
- [ ] Write operations use governed action pattern
- [ ] Approval workflows for high-risk actions
- [ ] Event publishing for async processing
- [ ] Audit trail for compliance

**Testing:**
- [ ] Unit tests for query service
- [ ] Integration tests with mock WorkCore
- [ ] Tenant isolation tests
- [ ] Authorization tests

**Documentation:**
- [ ] API documentation for features
- [ ] Configuration guide
- [ ] User guide for vertical usage

## Module-Specific Patterns

### #182: BusinessNetwork Integration
- Customer/contact management
- Knowledge base maintenance
- Catalogue and product info
- Territory and review management

### #183: Commercial Integration
- Inventory level queries
- Pricing and availability
- Order processing and tracking
- Payroll and financial data

### #184: WorkOperations Integration
- Job scheduling and dispatch
- Fleet tracking and routing
- Form and inspection completion
- Recurring service management

### #185: PropertyOperations Integration
- Property information queries
- Asset details and maintenance
- Document retrieval
- Maintenance request tracking

### #186: WorkforceAssurance Integration
- Staff roster queries
- Attendance recording
- Shift swap management
- Compliance monitoring
- Credential verification

## Issues Resolved by This Pattern

This pattern provides the foundation and blueprint for implementing:

**AiChatPro Integrations (6 issues):**
- #187: Foundation ✅
- #188: BusinessNetwork
- #189: Commercial
- #190: WorkOperations
- #191: PropertyOperations
- #192: WorkforceAssurance

**Chatbot Integrations (6 issues):**
- #193: Foundation ✅
- #194: BusinessNetwork
- #195: Commercial
- #196: WorkOperations
- #197: PropertyOperations
- #198: WorkforceAssurance

**AIAgent Integrations (7 issues):**
- #199: Foundation ✅
- #200: BusinessNetwork
- #201: Commercial
- #202: WorkOperations
- #203: PropertyOperations
- #204: WorkforceAssurance

## Implementation Status

✅ **Pattern Established**: All foundations in place
✅ **Layer 1**: TenantContext & Authorization complete
✅ **Layer 2**: Integration provider templates ready
✅ **Layer 3**: Feature layer can be implemented per vertical

**Next Steps:**
1. Implement query services for each module per extension
2. Add UI components for AiChatPro
3. Add conversational handlers for Chatbot
4. Add autonomous action handlers for AIAgent
5. Comprehensive integration tests across all combinations
