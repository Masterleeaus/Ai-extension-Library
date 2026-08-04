---
title: "MagicAI 11 Operations Hooks and WorkCore Menu Integration"
date: "2026-08-02"
status: "Evidence-backed architecture reference"
source: "MagicAI 11 server-file deep scan"
---

# MagicAI 11 Operations Hooks and WorkCore Menu Integration

## Executive conclusion

MagicAI 11 does **not** currently provide a native field-service Operations suite. Its operationally relevant navigation is divided between CRM, Sales and general platform features such as AI Chat, AI Agents, Team, Integrations, API Keys, Marketplace and Support.

The important architectural result is that MagicAI now exposes enough extension hooks to host WorkCore as a parallel, first-class operational suite.

> **MagicAI CRM manages relationships, prospects, deals and customer-facing sales.**  
> **WorkCore manages operational fulfilment, field service, workforce, properties, compliance and governed business actions.**

WorkCore should add four top-level menu domains:

- Operations
- Workforce
- Resources
- Commercial

---

# 1. Confirmed MagicAI CRM links

## CRM

| Link | Menu key | Named route | Visibility |
|---|---|---|---|
| CRM | `ext_crm_dropdown` | `dashboard.user.crm.index` | CRM registered and `crm_enabled = 1` |
| Dashboard | `crm_dashboard` | `dashboard.user.crm.index` | CRM registered |
| CRM Assistant | `crm_assistant` | `dashboard.user.crm.ai.index` | CRM registered and assistant enabled |
| Contacts | `crm_contacts` | `dashboard.user.crm.contacts.index` | CRM registered |
| Companies | `crm_companies` | `dashboard.user.crm.companies.index` | CRM registered |
| Deals | `crm_deals` | `dashboard.user.crm.deals.index` | CRM registered |
| Projects | `crm_projects` | `dashboard.user.crm.projects.index` | CRM registered |
| Tasks | `crm_tasks` | `dashboard.user.crm.tasks.index` | CRM registered |
| Calendar | `crm_calendar` | `dashboard.user.crm.calendar.index` | CRM registered |
| Presentations | `crm_presentations` | `dashboard.user.crm.presentations.index` | CRM registered and presentations enabled |
| Reports | `crm_reports` | `dashboard.user.crm.reports.index` | CRM registered |

## Sales

| Link | Menu key | Named route |
|---|---|---|
| Sales | `ext_sales_dropdown` | `dashboard.user.sales.invoices.index` |
| Invoices | `crm_sales_invoices` | `dashboard.user.sales.invoices.index` |
| Payments | `crm_sales_payments` | `dashboard.user.sales.payments.index` |
| Proposals | `crm_sales_proposals` | `dashboard.user.sales.proposals.index` |
| Estimates | `crm_sales_estimates` | `dashboard.user.sales.estimates.index` |

## Administration and plans

| Surface | Key or route |
|---|---|
| CRM settings | `dashboard.admin.crm.settings` |
| CRM entitlement | `ext_crm_dropdown` |
| Sales entitlement | `ext_sales_dropdown` |
| Install extension | `dashboard/marketplace/extension/{slug}/install` |
| Uninstall extension | `dashboard/marketplace/extension/{slug}/uninstall` |

The CRM entitlement covers Contacts, Companies, Deals, Projects, Tasks, Calendar, Presentations and CRM Assistant.

The Sales entitlement covers Invoices, Payments, Proposals and Estimates.

---

# 2. Confirmed MagicAI extension hooks

## 2.1 Service-provider registration

MagicAI maintains a central extension-provider registry:

```php
MarketplaceServiceProvider::$extensionProviders
```

CRM follows this model:

```php
'crm' => CrmServiceProvider::class,
'ai-agent-tool-crm' => AIAgentToolCrmServiceProvider::class,
```

WorkCore should follow the same pattern:

```php
'workcore' => WorkCoreServiceProvider::class,
'ai-agent-tool-workcore' => AIAgentToolWorkCoreServiceProvider::class,
```

MagicAI checks that provider classes exist before loading them, so an absent or disabled extension does not need to break the host.

## 2.2 Laravel lifecycle

WorkCore receives the standard extension lifecycle:

```php
register()
boot()
```

This allows registration of contracts, repositories, tenant resolution, permissions, middleware, routes, migrations, views, translations, events, listeners, queues, scheduled commands, storage adapters, notification adapters, AI tools and internal WorkCore modules.

## 2.3 Marketplace installation

MagicAI exposes install and uninstall routes and permits an extension-owned uninstall method:

```php
public static function uninstall(): void
```

WorkCore uninstall must disable code, menus, routes, queues and schedules while preserving operational data by default. Data destruction should require a separate explicit purge.

## 2.4 Registered-extension detection

MagicAI uses:

```php
MarketplaceHelper::isRegistered('crm')
```

WorkCore should use:

```php
MarketplaceHelper::isRegistered('workcore')
```

This can govern menu visibility, routes, settings, dashboards, AI tools, plan options and vertical packs.

---

# 3. Menu-system hooks

MagicAI's menu system merges database records with definitions from:

```php
App\Services\Common\MenuService::data()
```

A menu definition can contain:

```php
[
    'parent_key'       => null,
    'key'              => '...',
    'route'            => '...',
    'label'            => '...',
    'icon'             => '...',
    'order'            => 1,
    'is_active'        => true,
    'params'           => [],
    'type'             => 'item',
    'extension'        => true,
    'active_condition' => ['route.pattern.*'],
    'show_condition'   => true,
]
```

Confirmed menu capabilities include:

- Parent and child nesting
- Reordering
- Enable and disable controls
- Custom entries
- Badges and highlights
- Route parameters
- Extension marking
- Active-route conditions
- Plan-aware and user-aware caching
- Theme-specific menu output

After extension installation or configuration changes:

```php
app(MenuService::class)->regenerate();
```

should be called.

WorkCore views should remain namespaced:

```php
$this->loadViewsFrom($path, 'workcore');
```

Example views:

```text
workcore::dashboard
workcore::jobs.index
workcore::dispatch.board
workcore::workforce.index
workcore::field.job
workcore::compliance.inspections
```

---

# 4. Subscription and entitlement hooks

MagicAI maps route families to plan features:

```php
'wildcardRoutes' => [
    'dashboard.user.crm.'   => 'ext_crm_dropdown',
    'dashboard.user.sales.' => 'ext_sales_dropdown',
]
```

Recommended WorkCore mappings:

```php
'wildcardRoutes' => [
    'dashboard.user.operations.' => 'ext_workcore_operations',
    'dashboard.user.workforce.'  => 'ext_workcore_workforce',
    'dashboard.user.resources.'  => 'ext_workcore_resources',
    'dashboard.user.commercial.' => 'ext_workcore_commercial',
]
```

MagicAI already handles administrator bypass, free-plan restrictions, subscriber access, disabled features, team-member plan inheritance and upgrade redirection.

Recommended WorkCore plan features:

- WorkCore Core
- Work Operations
- Property Operations
- Workforce
- Compliance
- Assets and Inventory
- Offline Field App
- WorkCore AI Actions
- Vertical Packs

Recommended global settings:

```text
workcore_enabled
workcore_operations_enabled
workcore_workforce_enabled
workcore_property_enabled
workcore_compliance_enabled
workcore_resources_enabled
workcore_offline_enabled
workcore_ai_enabled
workcore_rewind_enabled
```

Global settings enable a subsystem for the installation. Plan entitlements determine which subscribers may access it.

---

# 5. Administration hooks

WorkCore should add extension-owned administration pages beneath MagicAI Settings:

- WorkCore Settings
- Companies and Memberships
- Operational Roles and Permissions
- Module Management
- Vertical Packs
- Offline and Synchronisation
- AI Action Governance
- Integrations
- Event Outbox
- Rewind and Audit
- Data Retention
- Operational Numbering and Sequences

---

# 6. AI Chat hooks

CRM demonstrates a dedicated assistant context and extension-owned chat view.

WorkCore should introduce:

```text
website_url = operations-assistant
chat_type = workcoreAssistant
```

with:

```php
workcore::assistant.includes.chat_area_container
```

Recommended context injection:

```php
WorkCoreAssistantContextService::systemPrompt(
    userId: Auth::id(),
    companyId: $activeCompanyId,
    scope: $workCoreScope,
);
```

Recommended scopes:

```text
today
jobs
schedule
dispatch
customers
premises
workforce
compliance
inventory
assets
commercial
all
```

This supports questions such as:

- What jobs are late?
- Who is available tomorrow?
- Which sites have unresolved hazards?
- Which recurring visits need rescheduling?
- What stock is below reorder level?
- Which jobs are complete but not invoice-ready?
- Which certifications expire this month?

WorkCore should add structured action cards:

```css
.workcore-action-card
.workcore-approval-card
.workcore-job-card
.workcore-dispatch-card
.workcore-incident-card
.workcore-inspection-card
```

Consequential actions should be confirmed through action cards and dispatched through WorkCore governance.

---

# 7. AI Agent tool hooks

MagicAI separates the CRM interface from CRM agent tools. WorkCore should do the same:

```text
workcore
ai-agent-tool-workcore
```

Recommended tools include:

```text
search_customers
search_service_sites
search_work_orders
create_customer
create_service_site
create_work_order
schedule_job
assign_worker
reschedule_visit
get_daily_dispatch
start_job
complete_job
complete_checklist
record_job_photo
record_inspection
report_hazard
report_incident
create_corrective_action
check_inventory
create_purchase_request
prepare_invoice
get_job_profitability
```

Every tool must call WorkCore's governed business-action dispatcher rather than write directly to models.

Required protections include company context, permission checks, validation, confirmation policy, idempotency, audit records, domain events, outbox delivery and Rewind metadata.

---

# 8. Event and identity hooks

MagicAI defines a contact-capture event containing user and contact details. CRM can listen and create or update a CRM contact.

WorkCore can listen to the same event, but it must enrich the user-scoped contract with operational context:

```text
companyId
businessUnitId
branchId
customerType
sourceSystem
correlationId
```

MagicAI teams and WorkCore company memberships must remain distinct:

```text
MagicAI team membership
is not equal to
WorkCore company membership
```

WorkCore still requires active company, operational role, worker identity, branch, territory, capabilities and company-level data isolation.

---

# 9. Existing MagicAI links WorkCore should reuse

| Existing link | WorkCore use |
|---|---|
| Dashboard | Titan Flow summary |
| Team | SaaS seats and invitations |
| API Keys | BYO AI and API credentials |
| Support | Platform support |
| Integrations | External connector entry point |
| Marketplace | WorkCore and vertical-pack installation |
| Brand Voice | Customer communications |
| AI Chat | Titan Zero assistant shell |
| AI Agents | Operational automation |
| Google Calendar | Scheduling context |
| Gmail and Outlook | Customer communication |
| Xero extension | Accounting export and reconciliation |

WorkCore should reuse these host capabilities rather than rebuild them.

---

# 10. Recommended WorkCore menus

## Operations

```text
Operations
├── Overview
├── Jobs and Work Orders
├── Schedule
├── Dispatch
├── Map and Territories
├── Recurring Services
├── Customers and Service Sites
├── Forms and Checklists
├── Inspections and Compliance
├── Reports
└── Operations Assistant
```

Recommended root:

```text
ext_workcore_operations
dashboard.user.operations.index
```

## Workforce

```text
Workforce
├── Workers
├── Rosters
├── Attendance
├── Timesheets
├── Leave
├── Skills and Certifications
└── Workforce Reports
```

Recommended root key: `ext_workcore_workforce`

## Resources

```text
Resources
├── Assets
├── Fleet
├── Inventory
├── Stock Locations
├── Suppliers
├── Purchase Orders
└── Knowledge Base
```

Recommended root key: `ext_workcore_resources`

## Commercial

```text
Commercial
├── Service Catalogue
├── Job Costing
├── Variations
├── Billable Work
├── Invoice-Ready Jobs
└── Profitability
```

Recommended root key: `ext_workcore_commercial`

Recommended top-level order:

```text
Dashboard
CRM
Sales
Operations
Workforce
Resources
Commercial
AI Chat or Titan Zero
Team
Integrations
```

Field workers should receive a separate stable navigation:

```text
My Day
Current Job
Schedule
Messages
Forms
Knowledge
```

---

# 11. CRM and WorkCore authority boundary

## MagicAI CRM should own

- Leads and prospects
- Contacts
- Customer sales companies
- Opportunities and pipelines
- Deal stages
- Sales follow-ups
- Proposals and estimates
- Customer-facing invoices
- Payment status
- Sales reports and presentations

## WorkCore should own

- Operational companies and memberships
- Customers as service recipients
- Properties and service sites
- Jobs and work orders
- Scheduling and dispatch
- Recurring service delivery
- Workforce and attendance
- Forms and checklists
- Inspections and compliance
- Assets, fleet and inventory
- Job costing
- Operational evidence
- Offline execution
- Governed actions
- Audit and Rewind

Recommended lifecycle:

```text
CRM lead
→ CRM contact and company
→ CRM deal
→ accepted proposal or estimate
→ governed conversion
→ WorkCore customer and service site
→ WorkCore work order
→ schedule and dispatch
→ field execution and evidence
→ job costing
→ invoice-ready event
→ MagicAI Sales invoice and payment
```

---

# 12. Implementation rules

1. Keep WorkCore under extension-owned paths such as `app/Extensions/WorkCore`.
2. Minimise modifications to MagicAI core.
3. Present one installable WorkCore extension while retaining internal domain modules.
4. Preserve WorkCore as the authority for operational records and consequences.
5. Route all AI actions through WorkCore governance.
6. Do not treat CRM Projects and Tasks as replacements for work orders, visits, evidence or job costing.
7. Preserve operational data when WorkCore is disabled or uninstalled.
8. Regenerate MagicAI menus after installation, plan changes or feature changes.

---

# 13. Confirmed limitations

The scanned MagicAI server package exposed CRM host integration, menu definitions and feature contracts, but it did not include the complete paid CRM extension source.

The scan confirmed host provider hooks, menu links, route naming contracts, plan entitlements, assistant integration points, settings and visibility controls.

It did not yet prove CRM migration quality, tenant isolation, model relationships, project-management depth, permissions, test coverage, offline behaviour, finance consistency, internal APIs or event implementation.

These require a separate deep scan of the paid CRM extension ZIP.

---

# Final verdict

MagicAI 11 already provides the host mechanisms required to integrate WorkCore:

- Provider registration
- Marketplace installation
- Dynamic nested menus
- Route-family plan protection
- Plan-builder features
- Global settings
- Admin settings
- Extension-owned views
- AI Chat contexts
- Structured action cards
- AI Agent tools
- Events
- Team-plan inheritance
- Menu regeneration and caching

MagicAI does not yet provide a true field-service Operations domain.

WorkCore should fill that gap as a parallel first-class extension rather than being buried inside CRM.

> **MagicAI CRM manages the relationship and sale.**  
> **WorkCore manages operational delivery and business consequences.**  
> **Titan Zero connects both through governed AI, privacy-first device execution and a unified interface.**

---

# Next deep-scan targets

1. Scan the separate paid CRM extension package.
2. Extract CRM routes, models, migrations, controllers, services and permissions.
3. Compare CRM Projects and Tasks against WorkCore operational projects.
4. Compare CRM Sales against WorkCore Commercial.
5. Map the exact extension-installer format.
6. Validate WorkCore against MagicAI's Laravel runtime.
7. Design the WorkCore provider bridge.
8. Produce the WorkCore menu manifest.
9. Produce the WorkCore entitlement manifest.
10. Produce the WorkCore AI Agent tool manifest.
