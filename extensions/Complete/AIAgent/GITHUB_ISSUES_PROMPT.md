# GitHub Issues - AIAgent

Issues to address for AIAgent extension.

## Issue #204: [Integration] WorkCoreWorkforceAssurance → AIAgent HR Automation

**URL**: https://github.com/masterleeaus/ai-extensions/issues/204
**State**: OPEN
**Labels**: AIAgent, workcore, integration, hr

## Summary
Integrate WorkCoreWorkforceAssurance with AIAgent to enable autonomous HR operations, compliance monitoring, roster optimization, and workforce analytics.

## Scope
- **Source**: WorkCoreWorkforceAssurance (Issue #186)
- **Target**: AIAgent (autonomous AI)
- **Integration Type**: Workforce automation

## Features to Enable
- Autonomous roster optimization
- Shift scheduling automation
- Attendance tracking automation
- Compliance monitoring and alerts
- Credential verification automation
- Leave request processing
- HR analytics and insights generation

## Acceptance Criteria
- [ ] AIAgent optimizes rosters autonomously
- [ ] Shift scheduling automated
- [ ] Attendance tracking automatic
- [ ] Compliance alerts working
- [ ] Credentials verified automatically
- [ ] Leave requests processed (with approval)
- [ ] HR analytics generated
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #186: WorkCoreWorkforceAssurance
- Issue #181: WorkCore Shared Foundation (dependency)

---

## Issue #203: [Integration] WorkCorePropertyOperations → AIAgent Property Automation

**URL**: https://github.com/masterleeaus/ai-extensions/issues/203
**State**: OPEN
**Labels**: AIAgent, workcore, assets, integration

## Summary
Integrate WorkCorePropertyOperations with AIAgent to enable autonomous property management, asset maintenance, and document lifecycle automation.

## Scope
- **Source**: WorkCorePropertyOperations (Issue #185)
- **Target**: AIAgent (autonomous AI)
- **Integration Type**: Property automation

## Features to Enable
- Autonomous maintenance scheduling
- Asset monitoring and alerts
- Document lifecycle management (creation, updates, archival)
- Property inspection automation
- Vertical operation profile optimization
- Predictive maintenance

## Acceptance Criteria
- [ ] AIAgent schedules maintenance autonomously
- [ ] Asset monitoring automated
- [ ] Documents managed by agent
- [ ] Inspections scheduled automatically
- [ ] Predictive maintenance working
- [ ] Profile optimization functioning
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #185: WorkCorePropertyOperations
- Issue #181: WorkCore Shared Foundation (dependency)
- Issue #83: TitanDocs (document integration)

---

## Issue #202: [Integration] WorkCoreWorkOperations → AIAgent Autonomous Dispatch

**URL**: https://github.com/masterleeaus/ai-extensions/issues/202
**State**: OPEN
**Labels**: AIAgent, workcore, operations, integration

## Summary
Integrate WorkCoreWorkOperations with AIAgent to enable autonomous job scheduling, dispatch optimization, fleet management, and work order automation.

## Scope
- **Source**: WorkCoreWorkOperations (Issue #184)
- **Target**: AIAgent (autonomous AI)
- **Integration Type**: Operations automation

## Features to Enable
- Autonomous job scheduling and optimization
- Dispatch optimization and assignment
- Fleet routing and tracking automation
- Recurring service automation
- Forms completion and inspection via agent
- Repairs tracking and status updates
- Customer notifications automation

## Acceptance Criteria
- [ ] AIAgent optimizes job scheduling autonomously
- [ ] Dispatch optimized automatically
- [ ] Fleet routing optimal
- [ ] Recurring services managed by agent
- [ ] Forms auto-filled where possible
- [ ] Repairs status updated automatically
- [ ] Customer notifications sent proactively
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #184: WorkCoreWorkOperations
- Issue #181: WorkCore Shared Foundation (dependency)
- Issue #61: Connector Runtime (maps integration)

---

## Issue #201: [Integration] WorkCoreCommercial → AIAgent Financial Automation

**URL**: https://github.com/masterleeaus/ai-extensions/issues/201
**State**: OPEN
**Labels**: AIAgent, workcore, finance, integration

## Summary
Integrate WorkCoreCommercial with AIAgent to enable autonomous financial operations, inventory management, procurement automation, and financial reporting.

## Scope
- **Source**: WorkCoreCommercial (Issue #183)
- **Target**: AIAgent (autonomous AI)
- **Integration Type**: Financial automation

## Features to Enable
- Autonomous inventory management and reordering
- Automated procurement workflows
- Financial reconciliation and reporting
- Payroll calculation and processing
- Budget forecasting and alerts
- Vault operations with approval workflows
- Financial analysis and anomaly detection

## Acceptance Criteria
- [ ] AIAgent manages inventory autonomously
- [ ] Procurement workflows automated
- [ ] Financial reporting automatic
- [ ] Payroll calculations autonomous (with approval)
- [ ] Budget forecasting working
- [ ] Vault operations governed and audited
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #183: WorkCoreCommercial
- Issue #181: WorkCore Shared Foundation (dependency)
- Issue #145: Credential Vault References

---

## Issue #200: [Integration] WorkCoreBusinessNetwork → AIAgent CRM Automation

**URL**: https://github.com/masterleeaus/ai-extensions/issues/200
**State**: OPEN
**Labels**: AIAgent, crm, workcore, integration

## Summary
Integrate WorkCoreBusinessNetwork with AIAgent to enable autonomous CRM operations, customer outreach, catalogue management, and business intelligence automation.

## Scope
- **Source**: WorkCoreBusinessNetwork (Issue #182)
- **Target**: AIAgent (autonomous AI)
- **Integration Type**: Business automation

## Features to Enable
- Autonomous customer outreach and follow-up
- CRM data automation and updates
- Catalogue management automation
- Knowledge base maintenance via agent
- Automated review and feedback analysis
- Territory optimization
- Business intelligence generation

## Acceptance Criteria
- [ ] AIAgent can autonomously update CRM
- [ ] Customer outreach automatable
- [ ] Catalogue managed by agent
- [ ] Knowledge base kept current
- [ ] Feedback analyzed automatically
- [ ] Territory optimization working
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #182: WorkCoreBusinessNetwork
- Issue #181: WorkCore Shared Foundation (dependency)

---

## Issue #199: [Integration] WorkCore Shared Foundation → AIAgent Autonomous Operations

**URL**: https://github.com/masterleeaus/ai-extensions/issues/199
**State**: OPEN
**Labels**: AIAgent, foundation, workcore, integration

## Summary
Integrate WorkCore Shared Foundation with AIAgent to enable enterprise-grade autonomy with tenancy isolation, permissions, and governed actions for autonomous AI operations.

## Scope
- **Source**: WorkCore Shared Foundation (Issue #181)
- **Target**: AIAgent (autonomous AI)
- **Integration Type**: Foundation layer integration

## Requirements
- [ ] TenantContext for isolated autonomous operations per tenant
- [ ] Authorization policies governing autonomous actions
- [ ] Governed actions with approval workflows for risky operations
- [ ] Credential vault for autonomous agent credentials
- [ ] EventEnvelope for idempotent autonomous workflows
- [ ] Audit trails for all autonomous decisions and actions
- [ ] Rate limiting and cost tracking for autonomous operations

## Acceptance Criteria
- [ ] AIAgent runs under TenantContext isolation
- [ ] Authorization enforced for autonomous operations
- [ ] Governed actions require approval for high-risk tasks
- [ ] Autonomous audit logs comprehensive
- [ ] Cost tracking working
- [ ] Rate limiting operational
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #181: WorkCore Shared Foundation
- Issue #143: TenantContext & Authorization Policies
- Issue #144: EventEnvelope & Idempotent Event Consumers
- Issue #145: Credential Vault References

---

## Issue #147: [URGENT] [Phase 0] Add AIAgent Conformance Test Suite (20–30 tests)

**URL**: https://github.com/masterleeaus/ai-extensions/issues/147
**State**: OPEN
**Labels**: AIAgent, phase-0, critical-path, testing

## Summary
Add comprehensive test coverage for AIAgent workflow engine, webhooks, and action execution.

## Problem
- **AIAgent has ZERO test coverage** — highest reliability risk
- Webhook authentication unverified
- Action idempotency not guaranteed
- Permissions not enforced during action execution
- Budget/rate-limit controls missing
- Timeout and retry policy untested

## Solution
Build conformance test suite covering workflow execution, webhook security, permissions, and governance.

## Deliverables
- [ ] **Webhook Tests (5–7 tests)**
  - [ ] Valid webhook acceptance
  - [ ] Invalid signature rejection
  - [ ] Replay prevention
  - [ ] Tenant resolution
  - [ ] Multi-tenant isolation

- [ ] **Idempotency Tests (3–4 tests)**
  - [ ] Idempotency key deduplication
  - [ ] Duplicate action rejection
  - [ ] Concurrent action handling

- [ ] **Permission Tests (3–4 tests)**
  - [ ] Action permission enforcement
  - [ ] Unauthorized action rejection
  - [ ] Scope validation

- [ ] **Budget & Rate-Limit Tests (2–3 tests)**
  - [ ] Budget depletion blocking
  - [ ] Rate-limit enforcement
  - [ ] Usage accounting

- [ ] **Timeout & Retry Tests (2–3 tests)**
  - [ ] Timeout policy enforcement
  - [ ] Retry backoff
  - [ ] Max retry limit

- [ ] **Workflow State Tests (3–4 tests)**
  - [ ] Workflow branching
  - [ ] Nested workflows
  - [ ] State machine transitions
  - [ ] Cancellation handling

## Exit Criteria
- ✅ 20–30 tests pass
- ✅ All webhook endpoints verified
- ✅ Permissions enforced
- ✅ Idempotency guaranteed
- ✅ Budget controls work
- ✅ Timeout and retry policies tested

## Relates to
- #67 (AIAgent hardening)
- #64 (Smoke and contract tests)

## Effort
1–2 weeks

---

