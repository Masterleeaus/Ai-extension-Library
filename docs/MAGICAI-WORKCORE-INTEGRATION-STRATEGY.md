# MagicAI-WorkCore Integration Strategy

**Date:** August 4, 2026  
**Status:** Implementation Planning Phase  
**Architecture Decision:** WorkCore is the canonical CRM & operations system

---

## Executive Summary

WorkCore replaces MagicAI CRM entirely. WorkCore becomes the single source of truth for:
- All customer relationship management (CRM)
- All operational workflows (jobs, scheduling, dispatch)
- All financial records (invoices, payments, ledger)
- All project/work management

The three main AI suites integrate directly with WorkCore:
- **AiChatPro** (Platform AI) - Uses WorkCore data for customer/business intelligence
- **Chatbot** (PWA AI) - Embedded business assistant with WorkCore context
- **AIAgent** (Autonomous AI) - Workflow automation using WorkCore operations

**No dual-master sync.** No MagicAI CRM data. WorkCore only.

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────┐
│      Three Main AI Suites                   │
│  AiChatPro │ Chatbot │ AIAgent             │
└──────────────────┬──────────────────────────┘
                   │ (Uses context & tools)
┌──────────────────▼──────────────────────────┐
│     Unified WorkCore Data Layer             │
│  (Single source of truth)                   │
├─────────────────────────────────────────────┤
│ CRM Module          Finance Module          │
│ ├─ Customers        ├─ Invoices             │
│ ├─ Contacts         ├─ Payments             │
│ ├─ Leads            ├─ Ledger               │
│ ├─ Opportunities    ├─ GST Calc             │
│ ├─ Pipelines        └─ Reconciliation       │
│ └─ Activities                               │
│                                             │
│ Operations Module   Workforce Module        │
│ ├─ Work Orders      ├─ Schedules            │
│ ├─ Jobs             ├─ Dispatch             │
│ ├─ Dispatch         ├─ Attendance           │
│ └─ Evidence         └─ Compliance           │
└─────────────────────────────────────────────┘
```

---

## 2. WorkCore CRM as Canonical System

### CRM Capabilities (All in WorkCore)

| Function | WorkCore Owner | Notes |
|----------|---|---|
| Customer management | ✅ | Operating companies |
| Contact management | ✅ | Customer contacts |
| Lead management | ✅ | Sales pipeline entries |
| Opportunity tracking | ✅ | Deal/sale pipeline |
| Sales pipeline | ✅ | Configurable stages |
| Activity logging | ✅ | Calls, emails, notes |
| Sales forecasting | ✅ | Pipeline-based |
| CRM AI tools | ✅ | Search, create, move, close |

### No External CRM Systems

```
❌ MagicAI CRM - Not installed, not integrated
❌ Duplicate lead tracking - WorkCore only
❌ Separate contact lists - WorkCore only
❌ External pipeline sync - WorkCore only
```

### Data Authority Matrix

| Entity | Authority | Scope |
|--------|-----------|-------|
| Customer | WorkCore | Company operating in the business |
| Contact | WorkCore | Person associated with customer |
| Lead | WorkCore | Prospect in sales pipeline |
| Opportunity | WorkCore | Deal/sale opportunity |
| Pipeline | WorkCore | Sales workflow stages |
| Activity | WorkCore | Interaction history |
| Invoice | WorkCore Finance | Customer-facing document |
| Payment | WorkCore Finance | Payment record + evidence |
| Work Order | WorkCore Operations | Job execution container |
| Job | WorkCore Operations | Actual work performed |

---

## 3. Three AI Suites Integration

### AiChatPro (Platform AI)

**Purpose:** Customer/business intelligence platform for knowledge work

**WorkCore Integration:**
- Read CRM data: Customers, leads, opportunities, pipeline
- Read Finance data: Invoice status, payment records, forecasts
- Read Operations data: Job status, scheduling, dispatch
- Read Workforce data: Team capacity, skills, availability

**AI Tools:**
```php
workcore_crm_search_customers
workcore_crm_search_leads
workcore_crm_list_sales_pipelines
workcore_crm_view_pipeline_board
workcore_crm_search_opportunities
workcore_finance_view_invoice
workcore_finance_view_payment_status
workcore_operations_search_jobs
workcore_operations_view_schedule
workcore_workforce_check_availability
```

**Context Injection:**
- User's company data
- Customer information
- Recent activities
- Sales performance metrics
- Pipeline forecast

### Chatbot (PWA AI)

**Purpose:** Customer-facing chatbot for self-service and support

**WorkCore Integration:**
- Read-only access to public customer data
- Resolve customer inquiries (status, history, next steps)
- Create new inquiries/tickets
- Access knowledge base

**AI Tools:**
```php
workcore_customer_lookup           // By ID or name
workcore_customer_view_open_jobs
workcore_customer_view_invoices
workcore_customer_view_payments
workcore_create_customer_inquiry
workcore_get_service_history
```

**Context Injection:**
- Authenticated customer context
- Open work orders
- Recent invoices/payments
- Service history
- Available services

### AIAgent (Autonomous AI)

**Purpose:** Workflow automation (scheduled jobs, event-triggered actions)

**WorkCore Integration:**
- Create/update leads and opportunities (sales funnel automation)
- Schedule work orders and jobs (field automation)
- Record fieldwork evidence (completion automation)
- Create invoices from jobs (financial automation)
- Process payments (collection automation)

**AI Tools (Governed Actions Only):**
```php
workcore_lead.create               // Governed action
workcore_opportunity.create        // Governed action
workcore_work_order.create         // Governed action
workcore_job.record_evidence       // Governed action
workcore_finance.generate_invoice  // Governed action
workcore_payment.record            // Governed action
```

**Safeguards:**
- Every write is a governed action (audit trail)
- Requires explicit permission/entitlement
- Approval workflows for financial writes
- Idempotency keys prevent duplicates
- Correlation IDs trace automation cause

---

## 4. Unified AI Context Router

All three suites feed a single router for multi-domain queries:

### How It Works

```
User Query: "Show me customer ABC's status and next scheduled job"

Router Logic:
  ├─ Intent: "customer status" → AiChatPro/Chatbot CRM tools
  ├─ Intent: "schedule check" → AIAgent operations tools
  └─ Combine results into one answer
```

### Tool Namespaces (Clear Separation)

```
AiChatPro namespace:
  workcore_crm_*
  workcore_finance_*
  workcore_operations_read_*   (read-only for display)

Chatbot namespace:
  workcore_customer_*          (public customer data only)
  workcore_service_*

AIAgent namespace:
  workcore_*.create            (governed actions for automation)
  workcore_*.update            (governed actions for automation)
  workcore_job.*               (dispatch/execution automation)
```

### Authorization Gates

```php
// Every AI tool call checks:
1. Is actor authenticated?
2. Does actor have company context?
3. Does actor have entitlement for this tool?
4. Is actor's role permitted for this action?
5. Does actor have record-level access?
6. Is this a read or write?
   ├─ Read: Grant if access allowed
   └─ Write: Require governed action + approval
```

---

## 5. Finance Authority (WorkCore Only)

### WorkCore Finance is Authoritative For

```
✅ Customer invoices (generated from jobs)
✅ Payment records and evidence
✅ GST calculation and compliance
✅ Reconciliation and ledger posting
✅ Profitability analysis
✅ Collections and payment plans
✅ Supplier expenses and payables
✅ Financial reporting and forecasts
```

### No Alternative Finance Systems

```
❌ MagicAI Sales - Not installed
❌ Duplicate invoice ledger - Not created
❌ External payment processor (direct) - WorkCore processes all
❌ Separate accounting system - WorkCore is the ledger
```

### Invoice Lifecycle (WorkCore Only)

```
WorkCore CRM/Sales
  ├─ Customer identified (CRM)
  ├─ Opportunity created (CRM)
  └─ Deal won (CRM)
       ↓
WorkCore Operations
  ├─ Work order created
  ├─ Job scheduled and executed
  └─ Evidence recorded
       ↓
WorkCore Finance
  ├─ Invoice generated (from job completion)
  ├─ GST calculated
  ├─ Sent to customer
  ├─ Payment received
  ├─ Allocated and matched
  ├─ Ledger posted
  └─ Reconciled

AI Suites
  ├─ AiChatPro shows invoice status (read-only)
  ├─ Chatbot allows payment (customer portal)
  └─ AIAgent processes collection if needed (via governed action)
```

---

## 6. Configuration & Activation

### Simplified Environment Variables

```env
# WorkCore CRM
WORKCORE_CRM_ENABLED=true           # Always true
WORKCORE_CRM_MODE=standalone        # No bridge mode needed

# AI Suite Features
AICHATPRO_WORKCORE_ENABLED=true
CHATBOT_WORKCORE_ENABLED=true
AIAGENT_WORKCORE_ENABLED=true

# Finance
WORKCORE_FINANCE_AUTHORITATIVE=true # Always true
WORKCORE_FINANCE_GST_ENABLED=true

# Operations
WORKCORE_OPERATIONS_ENABLED=true
WORKCORE_DISPATCH_ENABLED=true

# AI Tools
WORKCORE_CRM_AI_TOOLS_ENABLED=true
WORKCORE_FINANCE_AI_TOOLS_ENABLED=true
WORKCORE_OPERATIONS_AI_TOOLS_ENABLED=true
AIAGENT_GOVERNED_ACTIONS_ENABLED=true
```

### Database Setup (No Migrations Needed)

```
WorkCore prerequisite tables:
  ✅ tz_crm_* (customers, contacts, leads, opportunities)
  ✅ tm_* (invoices, payments, ledger, reconciliation)
  ✅ tz_operations_* (work orders, jobs, dispatch)
  ✅ tz_workforce_* (schedules, attendance)

All included in WorkCore shared foundation migrations.
```

---

## 7. AI Tool Registry

### CRM Tools (Read + Governance)

```php
// Search/Read
'workcore_crm_search_customers'
'workcore_crm_get_customer'
'workcore_crm_search_contacts'
'workcore_crm_search_leads'
'workcore_crm_list_sales_pipelines'
'workcore_crm_view_pipeline_board'
'workcore_crm_search_opportunities'
'workcore_crm_get_forecast'
'workcore_crm_list_activities'

// Create/Update (Governed Actions)
'workcore_crm.create_customer'
'workcore_crm.create_contact'
'workcore_crm.create_lead'
'workcore_crm.create_opportunity'
'workcore_crm.move_opportunity'
'workcore_crm.mark_opportunity_won'
'workcore_crm.record_activity'
```

### Finance Tools

```php
// Search/Read
'workcore_finance_search_invoices'
'workcore_finance_get_invoice'
'workcore_finance_view_payment_status'
'workcore_finance_view_forecast'
'workcore_finance_view_customer_balance'

// Create/Update (Governed Actions)
'workcore_finance.generate_invoice'  // From job
'workcore_finance.record_payment'
'workcore_finance.allocate_payment'
'workcore_finance.create_credit_note'
```

### Operations Tools

```php
// Search/Read
'workcore_operations_search_jobs'
'workcore_operations_get_job'
'workcore_operations_view_schedule'
'workcore_operations_view_dispatch_board'
'workcore_dispatch_check_availability'

// Create/Update (Governed Actions)
'workcore_operations.create_work_order'
'workcore_operations.schedule_job'
'workcore_operations.dispatch_job'
'workcore_job.record_evidence'
'workcore_job.complete'
```

---

## 8. Three AI Suites: Specific Integration Points

### AiChatPro (Platform AI)

**Features:**
- Business intelligence dashboard
- Customer/opportunity analysis
- Sales performance tracking
- Pipeline forecasting
- Financial overview (read-only)
- Operational status (read-only)

**WorkCore Data:**
- All CRM tables (read-only)
- All Finance tables (read-only)
- All Operations tables (read-only)

**User:** Team leaders, managers, analysts

### Chatbot (PWA AI)

**Features:**
- Customer portal (web/mobile)
- Order status lookup
- Payment tracking
- Service history
- Support ticket creation
- Self-service capabilities

**WorkCore Data:**
- Customer data (filtered by auth)
- Order/job status
- Invoice/payment status
- Service history

**User:** End customers, external

### AIAgent (Autonomous AI)

**Features:**
- Lead scoring and nurturing (automated)
- Work order creation (scheduled/triggered)
- Dispatch optimization
- Invoice generation (from job completion)
- Payment collection (if payment fails)

**WorkCore Data:**
- All CRM (write via governed actions)
- All Operations (write via governed actions)
- All Finance (write via governed actions, approval-gated)

**User:** Automation workflows, scheduled tasks, event handlers

---

## 9. Implementation Roadmap

### Phase 1: Foundation (Week 1)
- [x] Integrate MagicAI base app
- [x] Confirm WorkCore extensions available
- [ ] Verify WorkCore CRM module health
- [ ] Verify WorkCore Finance module readiness
- [ ] Document current state

### Phase 2: AI Tool Registry (Weeks 2-3)
- [ ] Create CRM AI tool definitions (read + governed actions)
- [ ] Create Finance AI tool definitions (read + governed actions)
- [ ] Create Operations AI tool definitions (read + governed actions)
- [ ] Register all tools in WorkCore AI registry
- [ ] Write tool unit tests

### Phase 3: AiChatPro Integration (Weeks 4-5)
- [ ] Wire AiChatPro to WorkCore CRM/Finance/Operations tools
- [ ] Create system prompts for business intelligence
- [ ] Add WorkCore data context injection
- [ ] Build sample queries/use cases
- [ ] User acceptance testing

### Phase 4: Chatbot Integration (Weeks 6-7)
- [ ] Wire Chatbot to customer-scoped WorkCore tools
- [ ] Implement customer authentication filtering
- [ ] Create self-service workflows
- [ ] Add support ticket integration
- [ ] Test customer portal

### Phase 5: AIAgent Integration (Weeks 8-9)
- [ ] Wire AIAgent to WorkCore governed actions
- [ ] Implement approval workflows for financial writes
- [ ] Create automation recipes (lead scoring, dispatch, invoicing)
- [ ] Add audit logging for all automated writes
- [ ] Load test concurrent automation

### Phase 6: Security & Testing (Week 10)
- [ ] Complete security audit (access gates, data isolation)
- [ ] Write comprehensive integration test suite (50+ tests)
- [ ] Load test AI tool performance
- [ ] User training and documentation
- [ ] Production readiness review

---

## 10. Success Criteria

- [x] MagicAI base app integrated
- [x] WorkCore extensions available
- [ ] WorkCore CRM is single customer source
- [ ] WorkCore Finance is single financial source
- [ ] All three AI suites operational
- [ ] No MagicAI CRM data in system
- [ ] No dual-master synchronization
- [ ] All AI tools properly namespaced
- [ ] All writes use governed actions
- [ ] 50+ integration tests passing
- [ ] Zero security audit findings
- [ ] Production deployment approved

---

## 11. Prohibited Patterns

### ❌ DO NOT

```
- Use MagicAI CRM instead of WorkCore CRM
- Create duplicate customer records
- Sync CRM data between external system
- Use last-write-wins for conflicts (shouldn't happen)
- Bypass governed actions for automated writes
- Store financial data outside WorkCore Finance
- Create independent invoice ledger
- Allow dual-master synchronization
- Direct database writes (use actions only)
- Assume user context without verification
```

### ✅ DO

```
- Use WorkCore CRM exclusively
- Wire all AI tools to WorkCore
- Require governed action for all writes
- Implement approval workflows for sensitive operations
- Audit all automated writes
- Test all access gates
- Document AI tool usage
- Monitor tool performance
- Log all errors and retries
- Review automation results regularly
```

---

## 12. Key Differences from Bridge Mode

### Before (If MagicAI CRM was installed)
- ❌ 2 CRM systems (complex sync)
- ❌ 2 customer records (duplicates)
- ❌ 2 financial ledgers (conflicts)
- ❌ Complex authority rules (confusing)
- ❌ Bridge conversions (overhead)

### Now (WorkCore Only)
- ✅ 1 CRM system (simple, unified)
- ✅ 1 customer record (authoritative)
- ✅ 1 financial ledger (trusted)
- ✅ Clear authority (no conflicts)
- ✅ Direct AI tool access (no translation)

---

## 13. Documentation & Resources

**Core WorkCore Modules:**
- `app/Domains/WorkCore/System/Modules/CRM/` - Customer, Lead, Opportunity
- `app/Domains/WorkCore/System/Modules/Finance/` - Invoices, Payments, Ledger
- `app/Domains/WorkCore/System/Modules/Operations/` - Jobs, Dispatch, Evidence
- `app/Domains/WorkCore/System/Modules/Workforce/` - Schedules, Rosters, Compliance

**AI Suite Integration:**
- Three suites in `app/extensions/` folder
- Each has hooks into unified AI context router
- Namespaced tool access per suite

**Architectural Scans:**
- `docs/magicai-integration/` - Full integration documentation
- `docs/MAGICAI-WORKCORE-INTEGRATION-STRATEGY.md` - This file
- `docs/reports/` - Deep analysis of each system

---

## 14. Next Steps

1. **Week 1:** Verify WorkCore CRM/Finance module health
2. **Week 2-3:** Build AI tool registry (read + governed actions)
3. **Week 4-5:** Integrate AiChatPro with WorkCore
4. **Week 6-7:** Integrate Chatbot with customer-scoped access
5. **Week 8-9:** Integrate AIAgent with automation workflows
6. **Week 10:** Security audit + production readiness

---

**Status:** Ready for Implementation  
**Architecture:** WorkCore Canonical System Only  
**MagicAI CRM:** Not Installed, Not Needed  
**Last Updated:** 2026-08-04

