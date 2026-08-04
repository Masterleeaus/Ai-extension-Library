# MagicAI-WorkCore Integration Strategy

**Date:** August 4, 2026  
**Status:** Implementation Planning Phase  
**Scope:** CRM, Project Management, Sales & Finance Integration

---

## Executive Summary

This document synthesizes comprehensive scan results (21 integration reports) and establishes the architecture for integrating MagicAI's CRM and sales capabilities with WorkCore's operational platform.

**Key Decision:** WorkCore Finance remains the authoritative operational ledger. MagicAI CRM operates as an optional front-office sales and customer relationship layer in bridge mode. No dual-master synchronization permitted.

---

## 1. Integration Modes

### Mode A: WorkCore Standalone (Default)
- **When:** MagicAI CRM not installed
- **Authority:** WorkCore CRM owns all pre-sale and operational records
- **Use Case:** Device-first, free-tier installations without paid CRM

### Mode B: MagicAI CRM Bridge (Selected)
- **When:** Paid MagicAI CRM extension installed and explicitly enabled
- **Authority Split:**
  - **MagicAI owns:** Pre-sale leads, prospects, sales pipeline, proposals, estimates (pre-acceptance), deals
  - **WorkCore owns:** Operational customers, service sites, work orders, job costing, invoices, payments, GST, ledger
- **Conversion:** One-way conversion from MagicAI deal→won to WorkCore customer through governed action
- **UI:** MagicAI Sales receives WorkCore operational status as read-only projections

### Mode C: Migration/Projection (Staged Rollout)
- **When:** Importing historical CRM records or switching authority
- **Pattern:** One system authoritative per record type, other is read-only projection
- **Duration:** Temporary; resolves to either Mode A or Mode B

---

## 2. CRM Authority Matrix

### Pre-Sale Lifecycle (MagicAI CRM in bridge mode)

| Record Type | MagicAI Authority | WorkCore Shadow |
|---|---|---|
| Lead | Owner | Projection only |
| Contact (prospect) | Owner | Projection after conversion |
| Company (prospect org) | Owner | Maps to Customer after conversion |
| Pipeline | Owner | N/A |
| Deal/Opportunity | Owner | Conversion trigger only |
| Proposal | Owner | Archived copy after acceptance |
| Estimate | Owner | Archived copy after acceptance |

### Operational Lifecycle (WorkCore always)

| Record Type | WorkCore Authority | MagicAI Shadow |
|---|---|---|
| Customer (operating company) | Owner | Status projection |
| Service Site | Owner | Location reference |
| Service Agreement | Owner | Scope snapshot |
| Work Order | Owner | Status projection |
| Job | Owner | Status/cost projection |
| Invoice (customer-facing) | Owner | Status projection |
| Payment | Owner | Status projection |

---

## 3. CRM-to-Operations Conversion

### Conversion Trigger
```
MagicAI Deal marked as Won
  → Acceptance confirmation received
  → MagicAI acceptance event emitted
```

### Conversion Command (Single Governed Action)

```php
WorkCore::BusinessActionDispatcher->execute(
  'workcore.sales.convert_accepted_deal',
  actor: authenticated_user,
  company: workcore_company,
  payload: {
    magicai_system: 'magicai-crm',
    magicai_workspace: workspace_id,
    magicai_deal_id: deal_id,
    magicai_prospect_contact_id: contact_id,
    magicai_prospect_company_id: company_id,
    accepted_scope: proposal_content,
    accepted_terms: estimate_json,
    idempotency_key: deal_id + accepted_timestamp,
    correlation_id: event_correlation_id
  }
)
```

### Conversion Output
1. Create WorkCore Customer (mapped to MagicAI company)
2. Create WorkCore Contacts (mapped from MagicAI prospects)
3. Create WorkCore Service Site (from proposal/estimate location)
4. Create WorkCore Quote snapshot (from accepted estimate terms)
5. Record External Mapping in `tz_external_record_links`
6. Emit `workcore.customer.created` event for MagicAI listening
7. Mark MagicAI deal as converted (read-only from WorkCore perspective)

---

## 4. External Record Linking

### Schema: `tz_external_record_links`

```sql
CREATE TABLE tz_external_record_links (
  id BIGINT PRIMARY KEY,
  public_id VARCHAR(36) UNIQUE,
  company_id BIGINT NOT NULL,
  system VARCHAR(64) NOT NULL,          -- 'magicai-crm', etc
  workspace_id VARCHAR(128),            -- MagicAI workspace ID
  record_type VARCHAR(64) NOT NULL,     -- 'contact', 'company', 'deal', etc
  external_id VARCHAR(128) NOT NULL,    -- MagicAI record ID
  workcore_record_type VARCHAR(64),     -- 'customer', 'contact', 'service_site', etc
  workcore_public_id VARCHAR(36),
  authority VARCHAR(32),                -- 'magicai', 'workcore', 'shared'
  sync_direction VARCHAR(32),           -- 'one_way', 'bi_directional'
  sync_status VARCHAR(32),              -- 'synced', 'pending', 'conflict'
  source_version INT,
  last_payload_hash VARCHAR(64),
  last_synced_at TIMESTAMP,
  conflict_reason TEXT,
  metadata JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  UNIQUE (company_id, system, workspace_id, record_type, external_id)
);
```

---

## 5. Event Bridge Architecture

### MagicAI → WorkCore Events (Async via Outbox)

```
magicai.crm.contact.created
  → Filter: deal converted?
  → Action: Create or update WorkCore Contact (one-way)
  → Store mapping

magicai.crm.deal.won
  → Filter: proposal accepted?
  → Action: Trigger conversion command
  → Create WorkCore Customer + linked records
  → Emit workcore.customer.created

magicai.sales.proposal.accepted
  → Store acceptance evidence
  → Ready for deal-won → conversion

magicai.sales.estimate.accepted
  → Store acceptance evidence + terms
  → Attachment: PDF/JSON scope snapshot
  → Ready for deal-won → conversion
```

### WorkCore → MagicAI Events (Status Projections)

```
workcore.customer.created
  → Emit to MagicAI outbox
  → MagicAI listener marks source deal as converted
  → MagicAI receives customer ID for billing link

workcore.work_order.created
  → Status: "Scheduled"
  → MagicAI receives status projection

workcore.job.completed
  → Status: "Ready for Invoicing"
  → MagicAI receives status

workcore.invoice.issued
  → WorkCore Finance is source of truth
  → MagicAI receives as payment-ready projection
  → MagicAI displays but does not edit
```

---

## 6. Chat & AI Integration

### CRM Assistant (MagicAI-provided)
- **Input:** CRM records, sales pipeline, proposals, estimates
- **Tool Access:** MagicAI CRM tools only
- **Sample Tools:**
  - Search CRM contacts
  - View CRM deals
  - Create proposal
  - Accept estimate
  - Record activity

### Operations Assistant (WorkCore-provided)
- **Input:** Customers, jobs, schedules, dispatch, compliance
- **Tool Access:** WorkCore operational tools only
- **Sample Tools:**
  - Search customers
  - Create work order
  - Schedule job
  - Record fieldwork evidence
  - View job profitability

### Unified Titan Zero Assistant (Router)
- **Single Entry:** User asks one question
- **Router Logic:** Dispatch to appropriate domain based on intent
  - "Show me my deals" → CRM Assistant
  - "Schedule tomorrow's jobs" → Operations Assistant
  - "Customer ABC status" → May need both (CRM + Operations projections)
- **Data Isolation:** Each domain tool namespace prevents accidental cross-domain access

### Required AI Tool Namespaces

```php
// MagicAI CRM tools
magicai_crm_search_contacts
magicai_crm_search_deals
magicai_crm_create_proposal
magicai_crm_accept_estimate
magicai_crm_record_activity

// WorkCore operational tools
workcore_search_customers
workcore_search_jobs
workcore_create_work_order
workcore_schedule_job
workcore_record_fieldwork
workcore_update_job_status

// WorkCore financial tools (Operations Assistant)
workcore_view_invoice
workcore_view_payment_status
workcore_view_job_profitability
workcore_view_payment_evidence
```

---

## 7. Contact Capture Integration

### Event v2: `BusinessContactCapturedV2`

```php
Event payload (recommended):
  event_id                  // UUID
  event_version             // "2.0"
  occurred_at               // ISO-8601
  source_system             // "magicai-crm", "website-form"
  source_workspace_id       // MagicAI workspace
  source_record_type        // "crm_contact"
  source_record_id          // External ID
  idempotency_key           // Prevent duplicates
  platform_user_id          // MagicAI user who captured
  actor_subject_id          // actual actor for audit
  workcore_company_id       // Validated before event
  name                      // Display name
  email                     // Normalized
  phone                     // Formatted
  phone_country_code        // "+61"
  organisation_name         // Company name
  contact_role              // Title/role
  consent                   // Marketing/operational
  metadata                  // Extra fields
  correlation_id            // Trace group
  causation_id              // Caused by which event
```

### Listener Requirements

1. Verify mapped company/workspace exists
2. Reserve idempotency key (prevent duplicate)
3. Normalize email, phone, country code
4. Run duplicate detection (name + email + phone)
5. Execute through `workcore.contact.create` governed action
6. Store external mapping
7. Emit result event with public IDs
8. Mark idempotency record as complete

---

## 8. Sales → Finance Boundary

### MagicAI Sales Surfaces

```
MagicAI CRM/Sales owns:
  ✓ Proposal workflow (send, track, accept)
  ✓ Estimate requests (customer-facing quote)
  ✓ Sales approval process
  ✓ Deal stage management
  ✓ Sales reporting and analytics
  ✓ Sales performance KPIs

MagicAI Sales does NOT own:
  ✗ Double-entry ledger (WorkCore Finance)
  ✗ GST calculation for invoices (WorkCore Finance)
  ✗ Reconciliation (WorkCore Finance)
  ✗ Payment evidence (WorkCore Finance)
  ✗ Job-cost linkage (WorkCore Finance)
  ✗ Accounting periods (WorkCore Finance)
  ✗ Collections (WorkCore Finance)
  ✗ Supplier expenses (WorkCore Finance)
```

### WorkCore Finance Authority

```
WorkCore owns end-to-end financial ledger:
  - Accepted sale scope capture
  - Job-derived invoice generation
  - Tax (GST) calculation per jurisdiction
  - Payment method and evidence
  - Allocation and refunds
  - Bank reconciliation
  - P&L and profitability
  - Supplier/expense management
  - Period close-out
```

### Invoice Lifecycle

```
MagicAI CRM/Sales
  Estimate → Proposal → Acceptance

WorkCore Commercial
  Quote snapshot + service agreement

WorkCore Finance (Single Source of Truth)
  Invoice created (job-derived)
  → GST calculated
  → Sent to customer (possibly via MagicAI projection)
  → Payment received
  → Allocated and matched
  → Ledger posted
  → Reconciled

MagicAI Sales receives:
  ✓ Invoice status (read-only projection)
  ✓ Payment status (read-only projection)
  ✓ Collections alerts (if configured)
  ✗ Cannot override invoice balance
  ✗ Cannot edit line items
  ✗ Cannot post ledger entries
```

---

## 9. Project Management Integration

### MagicAI Project Concept
- Unverified in core (paid CRM extension required)
- Proposed to be sales project or internal project
- Mapping to WorkCore: Likely Work Order or Job

### WorkCore Operations Authority
- Service Site holds location and terms
- Work Order is primary execution container
- Job captures actuals, costs, evidence
- Schedule/Dispatch allocates workforce
- Fieldwork evidence records completion

### Recommendation
- Do NOT create duplicate projects in MagicAI and WorkCore
- If MagicAI Projects used: Implement as 1-way mapping to WorkCore Job
- Reference pattern: Store MagicAI project ID in `tz_external_record_links`
- MagicAI receives Job status as projection

---

## 10. Configuration & Activation

### Environment Variables

```env
# CRM Mode
WORKCORE_CRM_MODE=standalone|bridge
# standalone: WorkCore CRM only (default)
# bridge: MagicAI CRM owns pre-sale, WorkCore owns operations

# Feature Flags
WORKCORE_CRM_ENABLED=true
WORKCORE_CRM_BRIDGE_MODE=false
WORKCORE_CRM_ASSISTANT_ENABLED=true
WORKCORE_OPERATIONS_ASSISTANT_ENABLED=true
WORKCORE_UNIFIED_ASSISTANT_ENABLED=true

# Event Bridge
WORKCORE_MAGICAI_EVENT_BRIDGE_ENABLED=true
WORKCORE_EXTERNAL_RECORD_LINKING_ENABLED=true

# Finance Authority
WORKCORE_FINANCE_AUTHORITATIVE=true
MAGICAI_SALES_PROJECTION_ONLY=true
```

### Database Migrations

```
WorkCore prerequisite tables:
  ✓ tz_external_record_links (new)
  ✓ tz_crm_* (existing WorkCore CRM)
  ✓ tm_* (existing WorkCore Finance)

MagicAI prerequisite:
  ✓ crm_* (from paid CRM extension)
  ✓ sales_* (from paid CRM/Sales extension)
```

---

## 11. Required Implementation Tasks

### Phase 1: Foundation (Weeks 1-2)
- [ ] Create `tz_external_record_links` table
- [ ] Add `workcore_crm_mode` configuration
- [ ] Create CRM mode service (`CrmAuthorityMode`)
- [ ] Implement `ExternalRecordMapper` utility
- [ ] Add MagicAI event listener bootstrap
- [ ] Create `BusinessContactCapturedV2` event class
- [ ] Write unit tests for mapping and authority

### Phase 2: CRM Bridge (Weeks 3-4)
- [ ] Implement deal→conversion governed action
- [ ] Add contact sync listener (MagicAI → WorkCore)
- [ ] Add company sync listener (MagicAI → WorkCore)
- [ ] Create `CompanyToCustomerConverter` service
- [ ] Add conversion idempotency cache
- [ ] Implement conflict detection
- [ ] Test mode switching (standalone ↔ bridge)

### Phase 3: AI Integration (Weeks 5-6)
- [ ] Add CRM AI tool namespacing
- [ ] Add Operations AI tool namespacing
- [ ] Implement router for Unified Assistant
- [ ] Add context isolation per domain
- [ ] Add permission gates per tool namespace
- [ ] Add tool-result audit logging

### Phase 4: Chat Security (Week 7)
- [ ] Implement chat access resolver
- [ ] Add company/workspace scope enforcement
- [ ] Add entitlement checks for streaming
- [ ] Add test suite for chat isolation
- [ ] Fix MagicAI chat ownership defects (per Scan 09)

### Phase 5: Events & Projections (Weeks 8-9)
- [ ] Implement WorkCore → MagicAI outbox events
- [ ] Add MagicAI event listener for WorkCore events
- [ ] Implement projection service (WorkCore data → MagicAI display)
- [ ] Add event versioning strategy
- [ ] Add replay/idempotency tests

### Phase 6: Testing & Documentation (Week 10)
- [ ] Write integration test suite (30+ tests per Scan 09 section 32)
- [ ] Load-test event bridge under concurrent conversions
- [ ] Document all authority rules
- [ ] Create operator runbooks for mode switching
- [ ] Create troubleshooting guide for conflicts

---

## 12. Prohibited Patterns

### ❌ DO NOT

```
- Create duplicate leads in both systems
- Sync company to both CRM Company and WorkCore Company
- Use last-write-wins for conflicts
- Bypass governed actions for conversions
- Store MagicAI data directly in WorkCore tables
- Create WorkCore Customer from CRM Company
- Edit WorkCore invoice from MagicAI Sales
- Assume user's active company applies to async events
- Direct UserOpenaiChat lookups without ownership filter
- Infer CRM workspace from user without mapping
- Enable dual-master synchronization
- Create duplicate payments in both systems
```

### ✅ DO

```
- Always use governed actions for writes
- Store all external IDs in tz_external_record_links
- Verify company/workspace context before events
- Implement idempotency on conversion
- Log all authority decisions
- Use event version headers
- Namespace AI tools by domain
- Test all chat access scenarios
- Audit all CRM→WorkCore conversions
- Maintain separate Assistant contexts
- Document field authority explicitly
- Review conflict queue weekly
```

---

## 13. Success Criteria

- [x] MagicAI base app integrated with all extensions
- [x] WorkCore extensions available in bridge mode
- [ ] CRM authority modes configurable per deployment
- [ ] Standalone mode works without paid CRM
- [ ] Bridge mode converts deals to customers without data loss
- [ ] No duplicate leads, contacts, or customers possible
- [ ] External record links auditable
- [ ] AI tools namespaced and isolated
- [ ] Chat access properly scoped
- [ ] Finance remains WorkCore authority
- [ ] All 30+ integration tests pass
- [ ] Zero unexamined conflicts in queue

---

## 14. Next Steps

1. **Immediate:** Review and approve this strategy with stakeholders
2. **Week 1:** Start Phase 1 implementation
3. **Week 3:** Begin CRM bridge development
4. **Week 5:** Implement AI integration
5. **Week 10:** Complete full test cycle
6. **Week 12:** Production readiness review

---

## Appendix: Document References

**Core Integration Scans:**
- 09-magicai-crm-host-contract.md
- 11-magicai-sales-estimates-invoices-and-payments.md
- 12-magicai-customer-lifecycle-and-workcore-conversion.md
- 15-workcore-magicai-combined-integration-architecture.md
- 17-workcore-magicai-install-activation-and-release-plan.md
- 18-workcore-magicai-confirmed-blockers-and-remediation.md

**Supporting Documentation:**
- 05-magicai-authentication-and-user-identity.md
- 06-magicai-teams-companies-and-tenancy.md
- 07-magicai-roles-permissions-and-access-control.md
- 14-magicai-ai-chat-runtime-and-context-hooks.md

**WorkCore Finance:**
- app/Domains/WorkCore/System/Modules/Finance/*
- app/Domains/WorkCore/System/Modules/CRM/*

---

**Status:** Ready for Review  
**Owner:** Integration Team  
**Last Updated:** 2026-08-04
