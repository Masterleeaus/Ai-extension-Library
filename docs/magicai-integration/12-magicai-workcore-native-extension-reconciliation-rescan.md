---
title: "MagicAI WorkCore Native Extension Reconciliation Rescan"
scan_number: 12
date: "2026-08-03"
magicai_version: "11.00"
status: "Corrected after direct WorkCore ZIP inspection"
scope:
  - "Masterleeaus/Documents WorkCore MagicAI scans 01-09 and 11"
  - "workcore/2026-08-02-magicai-11-operations-hooks-and-menu-integration.md"
  - "Masterleeaus/workcore-extensions main and generated WorkCore ZIP packages"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/12-magicai-workcore-native-extension-reconciliation-rescan.md"
---

# Scan 12 — MagicAI WorkCore Native Extension Reconciliation Rescan

## Executive conclusion

Direct inspection of the WorkCore packages changes the CRM architecture decision.

> **The WorkCore ZIP suite is intended to replace the paid MagicAI CRM and Sales extension, not operate beside it as a permanent bridge.**

MagicAI remains the host for authentication, SaaS plans, AI engines, shared chat, AI Agents, themes and Marketplace installation. WorkCore becomes the native business operating system for CRM, sales, operations, property, workforce, compliance and operational finance.

The current native MagicAI release consists of:

```text
WorkCore
WorkCoreBusinessNetwork
WorkCoreCommercial
WorkCoreWorkOperations
WorkCorePropertyOperations
WorkCoreWorkforceAssurance
```

The parent `WorkCore` ZIP contains the complete canonical WorkCore runtime and historical migrations. The other five ZIPs are parent-dependent activation packages that load exact module groups without duplicating runtime source or migrations.

The earlier recommendation for a permanent MagicAI CRM bridge is therefore superseded. A one-time importer may still be needed when migrating records from an already-used MagicAI CRM installation, but the paid CRM must not remain a second live authority after cutover.

The correct current status is:

> **WorkCore is package-complete and installation-verified as the CRM/Sales replacement, but it is not yet security-complete, UI-complete or operationally production-ready inside the full MagicAI/Titan Zero shell.**

---

# 1. Two package generations found

The repository contains evidence of two packaging generations.

## 1.1 Original source-split ZIPs

The original generated package catalogue describes:

```text
workcore-shared-foundation.zip
workcore-business-network.zip
workcore-commercial.zip
workcore-work-operations.zip
workcore-property-operations.zip
workcore-workforce-assurance.zip
```

These ZIPs physically divide the canonical source between Shared Foundation and five domain packages.

Their roles are:

| ZIP | Primary ownership |
|---|---|
| Shared Foundation | Tenancy, governance, migrations, shared contracts and runtime |
| Business Network | CRM, catalogue, support, knowledge, reviews, territories, intelligence and AI |
| Commercial | Finance, payroll, inventory, supply, vault and trust accounting |
| Work Operations | Work orders, scheduling, dispatch, recurring work, forms, repairs and fleet |
| Property Operations | Premises, assets, documents and vertical property operations |
| Workforce Assurance | Workforce, people, attendance, rosters, compliance and assurance |

## 1.2 Current MagicAI-native ZIPs

The native release builder produces:

```text
WorkCore-<version>.zip
WorkCoreBusinessNetwork-<version>.zip
WorkCoreCommercial-<version>.zip
WorkCoreWorkOperations-<version>.zip
WorkCorePropertyOperations-<version>.zip
WorkCoreWorkforceAssurance-<version>.zip
```

This is the correct MagicAI Marketplace generation.

The architecture is deliberately different:

- The parent ZIP carries the complete canonical runtime.
- The parent owns all migrations.
- Add-on ZIPs carry provider, configuration and manifest metadata.
- Each add-on calls `WorkModuleRegistry::loadMany()` for its exact module keys.
- Parent-only installation loads no optional business group.
- An add-on remains dormant when the parent is absent.
- Uninstall preserves WorkCore operational data by default.

The ZIP binaries are generated build outputs rather than permanent Git-history duplication. The expanded source, package manifests and deterministic builders are the repository authority.

---

# 2. CRM replacement ownership

The paid MagicAI CRM must not remain authoritative once the WorkCore replacement is enabled.

## 2.1 WorkCore Business Network replaces CRM

`WorkCoreBusinessNetwork` activates:

```text
crm
catalogue
support
knowledge
reviews
territories
intelligence
expansion
wizards
ai
```

The CRM provider registers company-scoped repositories, governed write actions and read models for:

```text
customers
customer contacts
leads
lead conversion
sales pipelines
pipeline boards
opportunities
opportunity movement
won and lost outcomes
CRM activities
CRM forecast
```

Confirmed governed writes include:

```text
workcore.customer.create
workcore.contact.create
workcore.contact.set_primary
workcore.lead.create
workcore.lead.update
workcore.lead.convert
workcore.sales_pipeline.create
workcore.opportunity.create
workcore.opportunity.update
workcore.opportunity.move
workcore.opportunity.mark_won
workcore.opportunity.mark_lost
workcore.crm_activity.create
```

Confirmed read models include:

```text
workcore.customer.search
workcore.lead.search
workcore.sales_pipeline.list
workcore.sales_pipeline.board
workcore.opportunity.search
workcore.crm.forecast
workcore.crm_activity.list
```

This is a real CRM engine, not an adapter waiting for the paid MagicAI CRM.

## 2.2 WorkCore Commercial replaces Sales and operational finance

`WorkCoreCommercial` activates:

```text
finance
payroll
inventory
supply
vault
trust_accounting
```

The final Finance completion layer registers governed actions for:

```text
quote creation and transition
invoice creation, issue and transition
credit-note creation
credit allocation
receivable allocation
expense recording and approval
account creation
accounting-period creation and close
journal posting
payment-provider configuration
payment-session creation
payment-attempt recording
```

It also retains the deeper Titan Money runtime for:

```text
invoice generation from completed jobs
payment evidence ingestion
payment matching and allocation
receipts
collections
bank-statement import
reconciliation
GST
ledger posting
budgets
procurement
payables
e-invoicing
POS and sales orders
job profitability
```

Therefore the MagicAI Sales surfaces for proposals, estimates, invoices and payments should be replaced by WorkCore Commercial and Finance surfaces.

## 2.3 WorkCore Work Operations replaces CRM projects, tasks and calendar

`WorkCoreWorkOperations` activates:

```text
operations
scheduling
dispatch
recurring
forms
repairs
fleet
```

The operational replacements are:

| MagicAI CRM concept | WorkCore replacement |
|---|---|
| Project | Work order, recurring service or operational workflow |
| Task | Work-order task, CRM activity, checklist item or corrective action |
| Calendar event | Appointment and scheduling record |
| Assignment | Dispatch assignment |
| Project status | Work-order and appointment lifecycle |

Confirmed routes include work orders, work-order status, time entries, work-order task status, appointments, rescheduling and appointment status.

WorkCore does not need to reproduce a generic CRM project metaphor when field and home service delivery is more accurately represented by jobs, work orders, visits, tasks, appointments and dispatch.

## 2.4 Property and Workforce go beyond CRM

`WorkCorePropertyOperations` adds premises, service sites, property records, assets and documents.

`WorkCoreWorkforceAssurance` adds workers, people, attendance verification, rosters, attendance, compliance and assurance.

These domains are not ordinary CRM features. They are why WorkCore is a business operating system rather than a CRM clone.

---

# 3. Final feature mapping

| MagicAI CRM/Sales surface | WorkCore authority |
|---|---|
| Contacts | WorkCore Customer Contacts |
| Companies | WorkCore Customers or customer organisations |
| Deals | WorkCore Opportunities |
| Pipelines | WorkCore Sales Pipelines |
| Sales activities | WorkCore CRM Activities |
| Projects | WorkCore Work Orders and operational workflows |
| Tasks | Work-order tasks, activities, checklists and corrective actions |
| Calendar | WorkCore Scheduling and Appointments |
| Reports | WorkCore read models, forecast, intelligence and domain reports |
| Estimates | WorkCore estimate/quote foundation; quote runtime currently stronger |
| Proposals | WorkCore Quotes and accepted commercial scope |
| Invoices | WorkCore Finance |
| Payments | WorkCore payment evidence, matching, allocation and reconciliation |
| Customer records | WorkCore CRM |
| Service sites | WorkCore Premises |
| Job delivery | WorkCore Operations |
| Workforce | WorkCore Workforce Assurance |
| Compliance | WorkCore Compliance and Assurance |
| Inventory and procurement | WorkCore Commercial |

## Presentation gap

MagicAI CRM Presentations has no direct equivalent inside the inspected WorkCore ZIPs.

That should be supplied by Titan Creative, Titan Docs or a dedicated presentation/document surface. It does not justify keeping the paid CRM installed as a second CRM authority.

---

# 4. Correct host architecture

The intended installed architecture becomes:

```text
MagicAI core
├── authentication
├── SaaS subscription and plan billing
├── AI Engine and Entity managers
├── shared AI Chat
├── AI Agent runtime
├── themes
├── Marketplace
└── platform administration

WorkCore suite
├── Business Network
│   ├── CRM
│   ├── catalogue
│   ├── support
│   ├── knowledge
│   ├── reviews
│   ├── territories
│   └── intelligence and AI
├── Commercial
│   ├── quotes
│   ├── invoices
│   ├── payments
│   ├── ledger and GST
│   ├── payroll
│   ├── inventory and supply
│   └── trust accounting and vault
├── Work Operations
│   ├── jobs and work orders
│   ├── tasks
│   ├── scheduling
│   ├── dispatch
│   ├── recurring services
│   ├── forms
│   ├── repairs
│   └── fleet
├── Property Operations
│   ├── premises and service sites
│   ├── property operations
│   ├── assets
│   └── documents
└── Workforce Assurance
    ├── workers and people
    ├── rosters and attendance
    ├── attendance verification
    ├── compliance
    └── assurance
```

The paid providers should not be part of the final runtime authority graph:

```text
crm
ai-agent-tool-crm
```

Any retained compatibility code should exist only to import records or redirect legacy routes during migration.

---

# 5. Replacement rules

## 5.1 No dual master

After cutover:

```text
WorkCore CRM owns customers, contacts, leads, pipelines and opportunities.
WorkCore Commercial owns quotes, invoice truth and operational payments.
WorkCore Operations owns jobs, work orders, tasks, scheduling and dispatch.
```

The paid MagicAI CRM must not continue creating or updating parallel contacts, deals, invoices or payments.

## 5.2 One-time migration only

When a business already has MagicAI CRM data, build a one-time migration process:

```text
extract CRM records
→ validate ownership and tenancy
→ normalize contacts and organisations
→ deduplicate
→ import through WorkCore governed actions
→ map old IDs to WorkCore public IDs
→ verify counts and financial balances
→ freeze old CRM writes
→ cut over menus and tools
→ archive or uninstall old CRM code
```

An external-record mapping table may be useful during import and audit, but it is not an ongoing dual-system synchronization contract.

## 5.3 Route and menu compatibility

Legacy MagicAI route names may be redirected temporarily:

```text
dashboard.user.crm.*
→ WorkCore Business Network views

dashboard.user.sales.*
→ WorkCore Commercial and Finance views
```

Long term, use explicit WorkCore menu roots rather than pretending the paid CRM is still installed.

Recommended roots remain:

```text
CRM or Customers
Sales
Operations
Workforce
Resources
Commercial or Finance
```

The final naming can be simplified for field and home service users, but the authority remains WorkCore.

## 5.4 Plan compatibility

MagicAI currently knows broad CRM and Sales plan keys:

```text
ext_crm_dropdown
ext_sales_dropdown
```

During transition, those keys can map to WorkCore entitlements. Long term, introduce finer WorkCore plan features such as:

```text
ext_workcore_crm
ext_workcore_sales
ext_workcore_operations
ext_workcore_workforce
ext_workcore_property
ext_workcore_commercial
ext_workcore_offline
ext_workcore_ai_actions
```

Plan entitlement grants module access. WorkCore company membership and permissions still control operational authority.

## 5.5 AI tools

Disable or remove duplicate MagicAI CRM tools after replacement.

Use WorkCore-owned namespaces and governance for actions such as:

```text
search customers
create lead
create opportunity
move opportunity
convert lead
create quote
create work order
schedule appointment
assign worker
create invoice
record payment evidence
```

Every consequential tool must pass through WorkCore tenancy, permission, validation, approval, idempotency, audit and outbox controls.

---

# 6. What is already implemented

The current repository confirms:

- Six MagicAI-native packages.
- One canonical runtime owner.
- One migration owner.
- Exact add-on module activation.
- Parent-only zero-domain activation.
- Dormant add-ons when the parent is absent.
- Data-retaining uninstall hooks.
- Legacy and hardened manifests.
- Deterministic package construction.
- Complete file checksums.
- Laravel 10 host matrices.
- Full migration tests for Parent and Full profiles.
- CRM governed actions and read models.
- Work-order, task and appointment APIs.
- Finance completion actions and repositories.
- Payment orchestration foundations.

The replacement is therefore architecturally real, not merely proposed.

---

# 7. Production blockers that remain

Replacing CRM does not remove the security and product-completion work.

## Critical

### Invalid permission level

The native WorkCore permission resolver returns:

```php
WorkCoreAccessLevel::Manage
```

but the enum defines only:

```text
All
Added
Owned
Both
None
```

This can fail on owner/admin permission resolution.

### Wrong API authentication guard

WorkCore still defaults to:

```text
auth:sanctum
```

while the inspected MagicAI host uses Passport:

```text
auth:api
```

### Confirmation bypass

The central confirmation verifier still treats any non-empty confirmation string as valid for confirmation-required and high-risk actions.

### Fail-open tenant reads

`BelongsToCompany` applies a company filter only when tenant context exists. Tenant-owned reads can remain unscoped in commands, queues, schedules, listeners, imports, AI tools and support scripts.

## High

- WorkCore menus and dashboard contributors are not complete.
- CRM and Finance browser workspaces are not complete.
- Finance still declares `production_ready = false`.
- Finance has action-key drift between `money.create_invoice_from_job` and `money.invoice.create_from_job`.
- Plan entitlement projection is not complete.
- Branch, territory, device and durable actor context are incomplete.
- The hardened signed, staged and rollback-capable Titan installer is not implemented.
- MagicAI queue boot, CSRF and extension-management defects remain host work.
- Presentation functionality needs Titan Creative/Docs integration.

---

# 8. Correct implementation sequence

## Phase 1 — Make the replacement safe

1. Fix the invalid WorkCore permission access level.
2. Change native WorkCore API authentication to Passport.
3. Replace arbitrary confirmation strings with bound, expiring, one-time approvals.
4. Make tenant-owned reads fail closed.
5. Audit direct database queries for company scope.
6. Run authenticated customer, lead, opportunity, work-order, appointment, quote, invoice and payment flows in the real MagicAI shell.

## Phase 2 — Replace CRM surfaces

7. Add WorkCore CRM, Sales, Operations, Workforce, Resources and Finance menus.
8. Add route compatibility or redirects from old CRM/Sales names.
9. Add WorkCore plan entitlements and migrate existing plan settings.
10. Replace CRM Assistant with WorkCore CRM and Operations Assistant contexts.
11. Replace `ai-agent-tool-crm` with WorkCore tool registration.
12. Build CRM and Finance manager workspaces.
13. Build customer-facing quote, invoice and payment views.

## Phase 3 — Migrate and remove the paid CRM

14. Build a one-time CRM data importer only if existing CRM records need preservation.
15. Reconcile counts, relationships, accepted documents and balances.
16. Freeze MagicAI CRM writes.
17. Cut menus, routes and AI tools to WorkCore.
18. Archive or uninstall the CRM and CRM Agent tool extensions.
19. Verify there is only one live authority for every record type.

## Phase 4 — Complete the broader business OS

20. Add Titan Flow dashboards.
21. Add stable field-worker mobile navigation.
22. Complete Finance end-to-end testing and retain `production_ready=false` until it passes.
23. Integrate Titan Creative/Docs for presentations and rich customer documents.
24. Implement signed staged installation, operation journals, health validation and rollback.

---

# 9. Final authority matrix

| Record or decision | Final authority |
|---|---|
| Titan Zero login | MagicAI core |
| Titan Zero subscription | MagicAI core |
| AI provider/model access | MagicAI core, adapted through WorkCore policy |
| WorkCore operating company | WorkCore |
| Customer and contacts | WorkCore Business Network |
| Lead | WorkCore Business Network |
| Sales pipeline and opportunity | WorkCore Business Network |
| Service catalogue | WorkCore Business Network |
| Quote and accepted commercial terms | WorkCore Commercial |
| Job and work order | WorkCore Work Operations |
| Work-order task | WorkCore Work Operations |
| Appointment and schedule | WorkCore Work Operations |
| Dispatch | WorkCore Work Operations |
| Premises and service site | WorkCore Property Operations |
| Assets and property documents | WorkCore Property Operations |
| Worker, roster and attendance | WorkCore Workforce Assurance |
| Compliance and assurance | WorkCore Workforce Assurance |
| Customer invoice | WorkCore Commercial/Finance |
| Payment evidence and allocation | WorkCore Commercial/Finance |
| GST, ledger and reconciliation | WorkCore Commercial/Finance |
| Presentation generation | Titan Creative/Docs integration |
| Paid MagicAI CRM runtime | Replaced; not a final authority |

---

# 10. Release gate

The WorkCore ZIP suite may replace the CRM extension only after the complete MagicAI/Titan Zero host passes:

```text
Passport-authenticated request tests
active-company membership tests
permission and record-access tests
cross-company denial tests
no-context fail-closed tests
verified approval and replay-denial tests
idempotent retry tests
audit and outbox tests
CRM menu and plan tests
work-order and scheduling tests
quote and invoice lifecycle tests
payment allocation and reconciliation tests
legacy CRM cutover tests
one-authority assertions
```

Until then, the packages are valid development and integration artifacts but not a production cutover release.

---

# Evidence limitations

This correction inspected the WorkCore source-split packages, the current MagicAI-native package builder, the generated native ZIP structure and the expanded current repository source.

It did not inspect the paid MagicAI CRM implementation because that package is not required as the future runtime. Its source would only be required to build a reliable importer for existing CRM data or to reproduce selected presentation features.

No live production MagicAI deployment, Passport request, CRM data migration, external gateway, queue worker or browser workspace was executed during this document correction.
