---
title: "MagicAI 11 CRM Host Contract"
scan_number: 9
date: "2026-08-03"
magicai_version: "11.00"
status: "Completed"
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/09-magicai-crm-host-contract.md"
---

# Scan 09 — MagicAI 11 CRM Host Contract

## Executive conclusion

MagicAI 11 core contains a detailed **CRM integration contract**, but it does not contain the paid CRM implementation.

The core package defines:

- The CRM extension provider registration
- A separate CRM AI Agent tool provider
- CRM and Sales navigation
- CRM and Sales plan entitlements
- Three CRM feature settings
- CRM Assistant chat integration
- A contact-capture event contract
- An administrator settings link
- Shared AI Chat presentation support for CRM action cards

The core package does **not** contain:

- CRM routes
- CRM controllers
- CRM models
- CRM migrations
- CRM database tables
- CRM policies
- CRM permissions
- CRM event listeners
- CRM Assistant service implementation
- CRM AI Agent tool implementation
- Sales proposal, estimate, invoice or payment implementation
- CRM tests

The exact behaviour of Contacts, Companies, Deals, Projects, Tasks, Calendar, Presentations, Reports, Invoices, Payments, Proposals and Estimates remains unverified until the paid CRM extension source is scanned.

## Central integration decision

WorkCore already contains a substantial company-scoped CRM module:

| WorkCore CRM component | Count |
|---|---:|
| Files | 63 |
| Actions | 20 |
| Registered governed write actions | 13 |
| Registered read models | 7 |
| AI tools | 12 |
| API routes | 22 |
| Controllers | 6 |
| Repositories | 6 |
| Repository contracts | 6 |

Running the paid MagicAI CRM and the full WorkCore CRM independently would create duplicate:

- Contacts
- Customer organisations
- Leads
- Opportunities/deals
- Pipelines
- Activities
- Estimates
- Invoices
- Payments

The safe design is therefore a configurable CRM mode:

```text
WorkCore CRM standalone mode
    Used when MagicAI CRM is absent.

MagicAI CRM bridge mode
    MagicAI CRM owns pre-sale relationship and pipeline records.
    WorkCore owns operational customers, service sites, delivery and finance consequences.

Migration/projection mode
    Used while importing, reconciling or transitioning record authority.
```

Dual-master synchronisation is prohibited.

---

# 1. Scan scope

This scan inspected:

- CRM Marketplace registrations
- CRM AI Agent tool registration
- CRM and Sales menu definitions
- CRM administrator menu
- Plan feature registration
- Route-family entitlement middleware
- CRM settings references
- CRM Assistant view and service hooks
- Chat creation and history filtering
- Chat streaming and prompt assembly
- CRM action-card presentation support
- Contact-capture event contract
- Core event dispatchers and listeners
- CRM routes, models, migrations and tables
- WorkCore CRM modules, actions, routes and AI tools
- WorkCore Finance and Operations boundaries
- Required record mappings and conversion events

This scan maps only contracts visible from MagicAI core. It does not infer implementation details that require the missing extension source.

---

# 2. Confirmed Marketplace registrations

MagicAI registers two CRM-related extension providers:

```php
'crm' => CrmServiceProvider::class,

'ai-agent-tool-crm'
    => AIAgentToolCrmServiceProvider::class,
```

Expected namespaces:

```text
App\Extensions\Crm\System\CrmServiceProvider
App\Extensions\AIAgentToolCrm\System\AIAgentToolCrmServiceProvider
```

## Meaning

`crm` is expected to own:

- CRM routes
- CRM data model
- CRM UI
- CRM settings
- CRM Assistant service
- Sales surfaces

`ai-agent-tool-crm` is expected to expose CRM actions to MagicAI AI Agents.

The two packages can be installed independently at the provider-registry level.

The core does not declare a formal dependency requiring:

```text
ai-agent-tool-crm requires crm
```

The Marketplace's parent dependency mechanism may provide that metadata remotely, but it is not proven from the local source.

## WorkCore equivalent

WorkCore should use:

```php
'workcore'
    => WorkCoreServiceProvider::class,

'ai-agent-tool-workcore'
    => AIAgentToolWorkCoreServiceProvider::class,
```

The WorkCore AI tool package must depend explicitly on WorkCore core.

---

# 3. Provider activation semantics

Core visibility checks use:

```php
MarketplaceHelper::isRegistered('crm')
```

Scan 03 established that this checks the loaded provider, not the marketplace database `installed` flag.

Therefore:

```text
provider class present and loaded
    → CRM considered registered

database installed = false
    → does not necessarily disable CRM
```

## Integration consequence

CRM activation must be reconciled across:

- Marketplace state
- Provider class
- Provider loaded state
- Route availability
- Migration state
- Settings state
- Entitlement state
- Health state

WorkCore must not infer CRM availability from the marketplace database row alone.

---

# 4. Confirmed CRM navigation contract

MagicAI core defines one CRM root and ten children.

| Order | Menu key | Label | Named route | Icon |
|---:|---|---|---|---|
| 4 | `ext_crm_dropdown` | CRM | `dashboard.user.crm.index` | `tabler-briefcase` |
| 1 | `crm_dashboard` | Dashboard | `dashboard.user.crm.index` | `tabler-layout-dashboard` |
| 2 | `crm_assistant` | CRM Assistant | `dashboard.user.crm.ai.index` | `tabler-message-chatbot` |
| 3 | `crm_contacts` | Contacts | `dashboard.user.crm.contacts.index` | `tabler-address-book` |
| 4 | `crm_companies` | Companies | `dashboard.user.crm.companies.index` | `tabler-building` |
| 5 | `crm_deals` | Deals | `dashboard.user.crm.deals.index` | `tabler-coin` |
| 6 | `crm_projects` | Projects | `dashboard.user.crm.projects.index` | `tabler-briefcase` |
| 7 | `crm_tasks` | Tasks | `dashboard.user.crm.tasks.index` | `tabler-checklist` |
| 8 | `crm_calendar` | Calendar | `dashboard.user.crm.calendar.index` | `tabler-calendar` |
| 9 | `crm_presentations` | Presentations | `dashboard.user.crm.presentations.index` | `tabler-presentation` |
| 10 | `crm_reports` | Reports | `dashboard.user.crm.reports.index` | `tabler-chart-bar` |

Active route family:

```text
dashboard.user.crm.*
```

The CRM root is visible when:

```text
CRM provider is registered
and
crm_enabled is truthy
```

---

# 5. Confirmed Sales navigation contract

MagicAI core defines a separate Sales root and four children.

| Order | Menu key | Label | Named route | Icon |
|---:|---|---|---|---|
| 5 | `ext_sales_dropdown` | Sales | `dashboard.user.sales.invoices.index` | `tabler-receipt` |
| 1 | `crm_sales_invoices` | Invoices | `dashboard.user.sales.invoices.index` | `tabler-file-invoice` |
| 2 | `crm_sales_payments` | Payments | `dashboard.user.sales.payments.index` | `tabler-cash` |
| 3 | `crm_sales_proposals` | Proposals | `dashboard.user.sales.proposals.index` | `tabler-file-text` |
| 4 | `crm_sales_estimates` | Estimates | `dashboard.user.sales.estimates.index` | `tabler-calculator` |

Active route family:

```text
dashboard.user.sales.*
```

The Sales root is visible when:

```text
CRM provider is registered
and
crm_enabled is truthy
```

No independent core setting for Sales was found.

---

# 6. Administrator settings contract

MagicAI core defines:

```text
menu key: crm_admin_settings
parent: settings
route: dashboard.admin.crm.settings
label: CRM Settings
order: 79
administrator item: true
```

The CRM extension must provide that named route.

## Permission gap

MagicAI core does not define or seed:

```text
crm_admin_settings
```

as one of its global platform permissions.

Because administrator access is menu-permission derived, the extension must:

- Register the permission
- Assign it to intended administrator roles
- Regenerate permission and menu caches
- Cover nested CRM settings routes

Without extension-side permission registration, the route may be available only to super administrators.

---

# 7. CRM plan entitlements

MagicAI adds two plan items when the CRM provider is loaded:

```text
ext_crm_dropdown
ext_sales_dropdown
```

Tooltips describe:

## CRM entitlement

```text
Contacts
Companies
Deals
Projects
Tasks
Calendar
Presentations
AI Assistant
```

## Sales entitlement

```text
Invoices
Payments
Proposals
Estimates
```

Route-family mappings:

```text
dashboard.user.crm.*
    → ext_crm_dropdown

dashboard.user.sales.*
    → ext_sales_dropdown
```

Administrators bypass the plan check.

Team members inherit the team manager's plan in this middleware.

## Limitations

The host provides only two broad entitlements.

It does not provide separate plan keys for:

- CRM Assistant
- Presentations
- Reports
- Projects
- Invoices
- Payments
- Proposals
- Estimates

The extension may add its own finer controls, but that is not proven.

## Existing-plan migration risk

CRM plan keys appear only while the CRM provider is registered.

Plans created before CRM installation may not contain:

```text
ext_crm_dropdown
ext_sales_dropdown
```

The core installer reseeds menus but does not visibly migrate all existing plan feature arrays.

The extension must explicitly decide whether existing plans:

- Receive CRM disabled by default
- Receive CRM according to a migration rule
- Require administrator selection

Silent automatic enablement is not recommended.

---

# 8. CRM settings contract

MagicAI core references three settings:

```text
crm_enabled
crm_assistant_enabled
crm_presentations_enabled
```

Defaults are treated as enabled when the settings are absent:

```text
setting(..., '1')
```

## Confirmed usage

- `crm_enabled`: CRM and Sales root menu visibility
- `crm_assistant_enabled`: CRM Assistant menu visibility
- `crm_presentations_enabled`: Presentations menu visibility

## Critical limitation

These settings are referenced only in `MenuService` within the scanned core.

They are not mapped in `CheckTemplateTypeAndPlan::settingSlug()`.

Core middleware therefore does not enforce them as route-level feature switches.

Unless the paid extension enforces them independently, a user may directly access a hidden route.

## Required rule

Each setting must be enforced at:

```text
route middleware
controller/service boundary
AI context boundary
tool registration
menu presentation
```

Menu hiding alone is not access control.

---

# 9. Core CRM route absence

No CRM or Sales route definitions were found in MagicAI's core route files.

The following names are contracts only:

```text
dashboard.user.crm.index
dashboard.user.crm.ai.index
dashboard.user.crm.contacts.index
dashboard.user.crm.companies.index
dashboard.user.crm.deals.index
dashboard.user.crm.projects.index
dashboard.user.crm.tasks.index
dashboard.user.crm.calendar.index
dashboard.user.crm.presentations.index
dashboard.user.crm.reports.index

dashboard.user.sales.invoices.index
dashboard.user.sales.payments.index
dashboard.user.sales.proposals.index
dashboard.user.sales.estimates.index

dashboard.admin.crm.settings
```

The paid extension must register all routes and their middleware.

No route behaviour can be verified from core.

---

# 10. Core CRM data absence

No CRM-specific core models were found for:

```text
CRM Contact
CRM Company
Deal
CRM Project
CRM Task
CRM Calendar Event
CRM Presentation
CRM Invoice
CRM Payment
CRM Proposal
CRM Estimate
```

No CRM-specific core migrations or table references were found for:

```text
crm_contacts
crm_companies
crm_deals
crm_projects
crm_tasks
crm_invoices
crm_payments
crm_proposals
crm_estimates
```

MagicAI's core `Company` model is Brand Voice data and is not the CRM Company model.

## Three company concepts

Integration code must distinguish:

```text
MagicAI Brand Profile
    Marketing and prompt context.

MagicAI CRM Account/Organisation
    Prospect or customer organisation in the paid CRM.

WorkCore Operating Company
    Tenant that runs the business.
```

A CRM Company must never be mapped to `tz_companies`.

It may map to a WorkCore customer organisation.

---

# 11. Contact-capture event

MagicAI core defines:

```php
ContactCapturedEvent
```

Payload:

```text
userId: int
name: string
email: nullable string
phone: nullable string
countryCode: nullable int
source: string, default "unknown"
```

The class comment says the CRM extension listens and mirrors the record into:

```text
crm_contacts
```

## Confirmed limitations

The event has no:

- WorkCore company ID
- CRM workspace ID
- External record ID
- Event ID
- Idempotency key
- Correlation ID
- Causation ID
- Actor ID distinct from owner
- Consent status
- Marketing permission
- Contact type
- Organisation link
- Source record reference
- Event version
- Captured timestamp
- Update-versus-create indication

`countryCode` is typed as an integer, which is unsuitable for preserving values such as:

```text
+61
```

## No core dispatcher

No dispatch of `ContactCapturedEvent` was found in MagicAI core.

No core listener was found.

The contract is available for extensions to dispatch and consume, but core registration or chatbot use does not automatically fire it.

## Multi-company failure

`userId` alone cannot select a WorkCore company because one user may belong to several companies.

An asynchronous listener must not infer company from the user's currently selected company after the event was emitted.

Company context must be captured in the event.

---

# 12. Recommended contact event v2

Create a versioned integration event:

```text
BusinessContactCapturedV2
```

Suggested payload:

```text
event_id
event_version
occurred_at
source_system
source_workspace_id
source_record_type
source_record_id
idempotency_key
platform_user_id
actor_subject_id
workcore_company_id
name
email
phone
phone_country_code
organisation_name
contact_role
consent
metadata
correlation_id
causation_id
```

The receiving listener must:

1. Verify mapped company/workspace.
2. Reserve the idempotency key.
3. Normalize email and phone.
4. Run duplicate detection.
5. Create or update through a governed action.
6. Record the external mapping.
7. Emit a result event.
8. Complete the idempotency record.

---

# 13. CRM Assistant host contract

MagicAI core integrates the CRM Assistant into the shared AI Chat runtime.

## Contract values

| Purpose | Value |
|---|---|
| CRM provider slug | `crm` |
| CRM page route name | `dashboard.user.crm.ai.index` |
| Assumed URL prefix in chat JavaScript | `/dashboard/user/crm/assistant` |
| Website/chat context | `crm-assistant` |
| Stored `chat_type` | `crm-assistant` |
| Streaming `template_type` | `crmAssistant` |
| CRM scope request field | `crm_scope` |
| Default CRM scope | `all` |
| Extension chat view | `crm::assistant.includes.chat_area_container` |
| Context service | `CrmAssistantChatService` |
| Service method | `systemPrompt(int userId, string scope)` |
| Structured card CSS class | `.crm-assistant-action-card` |

## Naming drift

The same feature is represented as:

```text
crm-assistant
crmAssistant
crm
ai
assistant
```

This is a fragile implicit contract.

A route can have the correct name but a different path and fail automatic chat initialization.

A frontend can store `crm-assistant` but send the wrong streaming `template_type`.

## Recommended normalization

Introduce a typed chat-context definition:

```text
key: crm_assistant
route
path
stored_chat_type
stream_template_type
view
context_provider
required_entitlement
required_setting
```

Maintain legacy aliases only at the adapter boundary.

---

# 14. CRM Assistant view integration

When:

```text
website_url = crm-assistant
and
CRM provider is registered
```

MagicAI renders:

```text
crm::assistant.includes.chat_area_container
```

The extension can therefore supply:

- Scope selector
- CRM-specific toolbar
- Action cards
- Extra scripts
- Record links
- CRM suggestions

Core chat components also:

- Label messages as CRM Assistant
- Separate CRM Assistant history from normal chat
- Auto-create a CRM Assistant conversation
- Hide the ordinary realtime toggle
- Hide the canvas trigger when a CRM action card exists

This is a useful reusable shell for WorkCore's Operations Assistant.

---

# 15. CRM system-prompt injection

For streaming requests whose `template_type` is:

```text
crmAssistant
```

MagicAI calls:

```php
CrmAssistantChatService::systemPrompt(
    Auth::id(),
    $crmScope
)
```

and appends the returned text as a system message.

The call is guarded by:

```text
CRM provider is registered
authenticated user exists
```

## Critical tenant limitation

The context service receives only:

```text
MagicAI user ID
scope string
```

It does not receive:

- Active WorkCore company
- CRM workspace
- Company membership
- Operational role
- Record-access level
- Branch
- Territory
- Device
- Assurance level
- Correlation ID

This signature is insufficient for WorkCore data.

## Scope validation gap

Core accepts:

```text
crm_scope
```

directly from the request and defaults to:

```text
all
```

No allowlist is applied in core.

The extension must validate the value, but that cannot be verified without its source.

## WorkCore rule

WorkCore context must use a typed context object:

```text
actor
company
membership
branch/territory scope
capabilities
permissions
requested domain scope
allowed record IDs or filters
correlation ID
```

Never expose WorkCore data through `systemPrompt(userId, "all")`.

---

# 16. CRM Assistant plan bypass

The shared streaming endpoint:

```text
dashboard.user.generator.stream.generate
```

does not use `CheckTemplateTypeAndPlan`.

It uses only:

```text
surveyMiddleware
```

An authenticated user can submit:

```text
template_type = crmAssistant
```

directly.

The CRM context injector checks only:

```text
CRM provider registered
Auth::check()
```

It does not check:

- `ext_crm_dropdown` entitlement
- `crm_enabled`
- `crm_assistant_enabled`
- CRM permission
- Active CRM workspace

## Consequence

A user who is not entitled to CRM may be able to invoke CRM Assistant context through the shared stream endpoint when CRM is installed.

## Required correction

Before context injection:

```text
CRM provider healthy
→ CRM globally enabled
→ CRM Assistant enabled
→ effective plan contains CRM
→ actor has CRM assistant permission
→ active CRM workspace/company mapping exists
→ requested scope is allowed
```

The same gate must protect every CRM tool call.

---

# 17. CRM chat ownership defects

The shared chat host contains unscoped chat lookups.

## Open chat container

`openChatAreaContainer()` loads:

```text
UserOpenaiChat by chat ID
+ optional chat-type filter
```

It does not apply the existing user/team ownership query.

## Chat send

`chatOutput()` loads:

```text
UserOpenaiChat by chat ID
```

without user ownership filtering.

## Stream prompt extraction

`GeneratorController::extractChatParameters()` loads:

```text
UserOpenaiChat by chat ID
```

without user ownership filtering.

The previous message history is then assembled from that chat.

## Consequence

An authenticated user who discovers another chat ID may potentially:

- Open another user's chat
- Read rendered history
- Append a message
- Stream using another conversation's history
- Access CRM Assistant output or CRM-derived context present in messages

CRM Assistant messages can contain sensitive:

- Contacts
- Deals
- Tasks
- Invoice information
- Action proposals

## Severity

**Critical**

## Required correction

All chat reads and writes must use one ownership-aware resolver:

```php
ChatAccessResolver::forActor(
    actor,
    chatId,
    expectedContext
)
```

It must verify:

```text
user ownership
or valid team sharing
chat type
extension entitlement
workspace/company mapping
record-level permission
```

Do not call `UserOpenaiChat::find()` directly from chat endpoints.

---

# 18. CRM action-card contract

Core CSS recognizes:

```text
.crm-assistant-action-card
```

and hides the normal canvas trigger for that message.

This confirms that the CRM extension can render structured actions in chat.

Core does not define:

- Action-card schema
- Allowed action types
- Confirmation token
- Payload hash
- Permission check
- Approval status
- Expiry
- Execution endpoint
- Idempotency behaviour
- Audit contract

Those remain extension-owned and unverified.

## WorkCore rule

WorkCore action cards must be presentation wrappers around:

```text
governed action proposal
verified approval
payload hash
one-time execution token
```

They must never encode a direct model update URL.

---

# 19. CRM AI Agent tool contract

MagicAI core only proves that a provider named:

```text
ai-agent-tool-crm
```

can be loaded.

It does not expose:

- Tool names
- JSON schemas
- Read/write separation
- Permissions
- Tenant resolution
- Confirmation policy
- Idempotency
- Audit
- Tool result format
- CRM record ownership

The tool package must be scanned separately.

## WorkCore boundary

MagicAI CRM tools should act only on CRM-owned records.

WorkCore tools should act only through:

```text
BusinessActionDispatcher
ReadModelRegistry
NativeAiAccessGate
verified approvals
```

Avoid ambiguous duplicate tool names such as:

```text
create_customer
create_invoice
```

Use namespaces:

```text
magicai_crm_create_contact
workcore_create_operational_customer
workcore_prepare_invoice
```

---

# 20. WorkCore CRM capabilities

WorkCore CRM currently provides company-scoped:

- Customers
- Customer contacts
- Leads
- Lead sources
- Lead statuses
- Lead conversion
- Sales pipelines
- Pipeline stages
- Opportunities
- Opportunity movement
- Won/lost closure
- CRM activity timelines
- Forecasts
- Duplicate detection
- Subject reference validation
- Default pipeline provisioning
- Governed actions
- Permission checks
- Read models
- AI tools
- API resources

## Registered governed write actions

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

## Registered read models

```text
workcore.customer.search
workcore.lead.search
workcore.sales_pipeline.list
workcore.sales_pipeline.board
workcore.opportunity.search
workcore.crm.forecast
workcore.crm_activity.list
```

## AI tools

```text
crm_search_customers
crm_search_leads
crm_list_sales_pipelines
crm_view_pipeline_board
crm_search_opportunities
crm_get_forecast
crm_list_activities
crm_create_customer
crm_create_lead
crm_create_opportunity
crm_move_opportunity
crm_record_activity
```

This is a real CRM implementation, not only a host contract.

---

# 21. WorkCore CRM route contract

WorkCore CRM exposes 22 optional API routes under:

```text
api/workcore/v1
```

They include:

```text
customers
contacts
leads
lead conversion
sales pipelines
pipeline board
forecast
opportunities
opportunity stage movement
won/lost closure
CRM activities
```

The supplied WorkCore configuration currently uses:

```text
auth:sanctum
```

MagicAI uses Passport.

Scan 07 already requires this to become host-configurable.

---

# 22. CRM authority modes

## Mode A — WorkCore standalone CRM

Used when MagicAI CRM is absent.

WorkCore owns:

```text
leads
contacts
customers
pipelines
opportunities
activities
operational conversion
```

WorkCore provides a lightweight CRM suitable for the free/device-first core.

## Mode B — MagicAI CRM bridge

Used when the paid CRM extension is installed and deliberately selected.

MagicAI CRM owns:

```text
pre-sale leads
prospect contacts
prospect organisations
deals and sales pipeline
sales follow-up
proposals and estimates before acceptance
```

WorkCore owns:

```text
operational customers
service sites
service agreements
work orders
appointments and dispatch
job costing
delivery evidence
invoice-ready state
finance ledger and reconciliation
```

WorkCore's duplicate pre-sale UI and AI write tools should be disabled or converted to bridge calls.

## Mode C — Migration/projection

Used while:

- Importing CRM records
- Reconciling duplicates
- Switching authority
- Running staged rollout

One system remains authoritative for each record type.

The other is a read-only projection.

---

# 23. Provisional record authority matrix

The paid CRM source is missing, so this matrix is provisional.

| MagicAI CRM concept | WorkCore target | Provisional authority |
|---|---|---|
| Contact | Customer Contact | CRM before conversion; WorkCore operational snapshot after conversion |
| Company | Customer Organisation | CRM |
| Deal | Opportunity or conversion trigger | CRM |
| Pipeline | CRM pipeline | CRM in bridge mode |
| Project | Unknown | No mapping until paid extension scan |
| Task | CRM Activity or sales task | CRM; never assume Work Order |
| Calendar Event | Sales appointment | CRM unless converted to operational appointment |
| Presentation | No operational equivalent | CRM |
| CRM Report | Projection | CRM |
| Proposal | Commercial offer | CRM before acceptance |
| Estimate | Quote/estimate bridge | CRM before acceptance; WorkCore receives accepted commercial terms |
| Invoice | Customer invoice projection | Authority undecided pending full scan |
| Payment | Payment status/evidence | WorkCore Finance preferred; final decision pending full scan |

## Important rule

A MagicAI CRM Company maps to:

```text
WorkCore Customer
```

not:

```text
WorkCore Company
```

---

# 24. Sales and Finance boundary

MagicAI core proves that the paid CRM extension intends to provide:

```text
Invoices
Payments
Proposals
Estimates
```

It does not prove:

- Double-entry ledger
- GST handling
- Reconciliation
- Payment evidence
- Job-cost linkage
- Supplier expenses
- Accounting periods
- Collections
- Idempotency
- Company tenancy

WorkCore Finance already provides those operational controls.

## Provisional architecture

```text
MagicAI CRM/Sales
    Customer-facing sales workflow and documents where selected.

WorkCore Commercial
    Service catalogue, accepted scope, variations and job costing.

WorkCore Finance
    Authoritative invoice lifecycle, payment evidence,
    reconciliation, GST, ledger and profitability.
```

If the CRM extension must remain the customer-facing invoice UI, it should act as an adapter/projection over WorkCore Finance rather than create a second financial ledger.

The full CRM scan must confirm whether this is technically feasible.

---

# 25. Conversion lifecycle

Recommended bridge lifecycle:

```text
CRM lead
→ CRM contact and organisation
→ CRM deal
→ proposal or estimate
→ acceptance or deal won
→ conversion command
→ WorkCore customer
→ WorkCore contacts
→ WorkCore service site
→ optional service agreement
→ WorkCore quote/scope snapshot
→ WorkCore work order or onboarding workflow
→ scheduling and dispatch
→ field delivery and evidence
→ job costing
→ invoice-ready event
→ WorkCore Finance invoice
→ CRM receives status projection
```

Conversion must be one governed, idempotent operation.

---

# 26. External record mapping

Add:

```text
tz_external_record_links
```

Suggested fields:

```text
id
public_id
company_id
system
workspace_id
record_type
external_id
workcore_record_type
workcore_public_id
authority
sync_direction
sync_status
source_version
last_payload_hash
last_synced_at
conflict_reason
metadata
created_at
updated_at
```

Recommended unique key:

```text
company_id
+ system
+ workspace_id
+ record_type
+ external_id
```

This prevents duplicate conversion and preserves traceability.

---

# 27. Bridge events

## MagicAI CRM to WorkCore

Recommended events:

```text
magicai.crm.contact.created
magicai.crm.contact.updated
magicai.crm.company.created
magicai.crm.company.updated
magicai.crm.deal.won
magicai.sales.proposal.accepted
magicai.sales.estimate.accepted
magicai.sales.invoice.issued
magicai.sales.payment.recorded
```

Not all should create WorkCore records.

The bridge policy decides according to authority mode.

## WorkCore to MagicAI CRM

Recommended events:

```text
workcore.customer.created
workcore.customer.updated
workcore.service_site.created
workcore.work_order.created
workcore.work_order.status_changed
workcore.job.completed
workcore.job.invoice_ready
workcore.invoice.issued
workcore.payment.confirmed
```

Events must travel through a durable outbox with idempotency and mapping.

---

# 28. Conflict rules

Never use last-write-wins across CRM and WorkCore.

Define field authority.

Example after conversion:

| Field | Authority |
|---|---|
| Prospect marketing status | MagicAI CRM |
| Customer display name | Mapped policy |
| Operational billing name | WorkCore |
| Service site | WorkCore |
| Contact marketing consent | MagicAI CRM |
| Contact operational instructions | WorkCore |
| Deal value | MagicAI CRM |
| Job actual cost | WorkCore |
| Work status | WorkCore |
| Invoice balance | WorkCore Finance |
| Sales follow-up | MagicAI CRM |

Conflicts should enter a review queue.

---

# 29. CRM Assistant integration with WorkCore

WorkCore should not extend `CrmAssistantChatService::systemPrompt()` to read operational tables directly.

Recommended split:

## CRM Assistant

```text
CRM contacts
CRM companies
CRM deals
CRM tasks
CRM sales documents
```

## Operations Assistant

```text
customers
sites
jobs
schedule
dispatch
workforce
compliance
inventory
job costing
invoice-ready state
```

## Unified Titan Zero Assistant

The user may ask one assistant, but routing should dispatch to separate domain tools and context providers with explicit authority.

Writes always use domain tools.

---

# 30. Required MagicAI host fixes

## Critical

1. Scope every chat lookup to the authenticated actor or authorised team.
2. Protect streaming generation with entitlement and feature gates.
3. Enforce `crm_enabled` server-side.
4. Enforce `crm_assistant_enabled` server-side.
5. Enforce `crm_presentations_enabled` server-side.
6. Validate and allowlist `crm_scope`.
7. Add CRM workspace/company context.
8. Replace user-only CRM context signatures.
9. Prevent direct chat ID access across users.
10. Add plan checks to CRM Assistant AJAX endpoints.

## High

11. Normalize CRM Assistant identifiers.
12. Add typed CRM chat-context registration.
13. Version action-card schemas.
14. Add verified action-card approvals.
15. Add CRM administrator permission registration.
16. Migrate existing plans deliberately.
17. Add route-existence and provider-health checks.
18. Add event IDs and company context to contact capture.
19. Add core or extension dispatch tests.
20. Add CRM integration contract tests.

---

# 31. Required WorkCore integration work

1. Add CRM authority-mode configuration.
2. Disable duplicate WorkCore lead/opportunity writes in bridge mode.
3. Retain WorkCore operational customer/contact records.
4. Add external record mapping.
5. Add MagicAI CRM event adapter.
6. Add conversion action from accepted sale to operations.
7. Add company/workspace mapping.
8. Add conflict queue.
9. Namespace MagicAI CRM and WorkCore AI tools.
10. Keep Operations Assistant separate from CRM Assistant internally.
11. Add WorkCore status projections back to CRM.
12. Preserve WorkCore Finance as operational ledger unless full scan proves a deliberate alternative.
13. Replace `auth:sanctum` for MagicAI hosting.
14. Use actor/company/permission-aware context for all AI access.

---

# 32. Required tests

## Provider and routes

- CRM menus remain hidden when provider is absent.
- CRM menus appear only when provider and routes are healthy.
- CRM settings route requires correct administrator permission.
- Disabling CRM blocks direct routes.
- Disabling CRM Assistant blocks stream requests.
- Disabling Presentations blocks direct route access.

## Plans

- User without CRM entitlement cannot access CRM route.
- User without CRM entitlement cannot call shared CRM streaming endpoint.
- User with CRM but without Sales cannot access Sales.
- Team member inherits only intended entitlement.
- Existing plans are migrated according to explicit policy.

## Chat security

- User cannot open another user's CRM chat.
- User cannot append to another user's CRM chat.
- User cannot stream using another user's history.
- Chat type must match requested assistant.
- CRM scope is allowlisted.
- Active company/workspace is verified.
- Hidden action card cannot bypass permission.

## Contact events

- Event without company/workspace is rejected in multi-company mode.
- Duplicate event does not duplicate contact.
- Contact maps to correct WorkCore company.
- Country code preserves `+`.
- Consent is retained.
- Event result is auditable.

## Bridge

- CRM Company never creates a WorkCore operating company.
- Deal won conversion is idempotent.
- Accepted estimate produces one operational conversion.
- Replayed webhook/event does not create duplicate customer or job.
- Bridge mode disables duplicate WorkCore pre-sale writes.
- Standalone mode works without paid CRM.
- Field-authority conflicts enter review.
- WorkCore invoice balance cannot be overwritten by CRM projection.

---

# 33. Decisions established by Scan 09

1. MagicAI core exposes a CRM host contract, not a CRM implementation.
2. The paid CRM source is required before final authority decisions.
3. WorkCore CRM remains the fallback CRM when paid CRM is absent.
4. Paid MagicAI CRM becomes front-office authority in bridge mode.
5. Dual-master CRM synchronisation is prohibited.
6. CRM Company maps to WorkCore Customer, never WorkCore Company.
7. WorkCore remains operational and tenant authority.
8. WorkCore Finance remains the provisional operational ledger authority.
9. CRM Assistant and Operations Assistant remain separate domain services.
10. Shared chat endpoints require immediate ownership and entitlement hardening.
11. ContactCapturedEvent v1 is insufficient for multi-company integration.
12. All conversions require mapping, idempotency, audit and outbox events.
13. AI Agent tools are namespaced by authority domain.
14. Existing WorkCore CRM writes are disabled or redirected in bridge mode.
15. The next full CRM scan must inspect the paid extension before implementation.

---

# 34. Next scan

The planned next report is:

```text
10-magicai-crm-extension-deep-scan.md
```

It requires the separate paid MagicAI CRM extension package.

That scan must inspect:

- Extension provider
- Routes
- Middleware
- Models
- Migrations
- Controllers
- Services
- Policies
- Permissions
- Contacts
- Companies
- Deals
- Pipelines
- Projects
- Tasks
- Calendar
- Presentations
- Reports
- Proposals
- Estimates
- Invoices
- Payments
- CRM Assistant
- AI Agent tools
- Events
- Tenant isolation
- Tests

If the paid CRM package is not available, the scan programme should temporarily skip to:

```text
11-magicai-sales-estimates-invoices-and-payments.md
```

using only host-level evidence, while marking all extension behaviour unverified.

---

# Evidence files

## MagicAI core

```text
app/Domains/Marketplace/MarketplaceServiceProvider.php
app/Events/ContactCapturedEvent.php
app/Services/Common/MenuService.php
app/Http/Middleware/CheckTemplateTypeAndPlan.php
app/Http/Controllers/AIChatController.php
app/Http/Controllers/OpenAi/GeneratorController.php
resources/views/default/panel/user/openai_chat/components/chat_head.blade.php
resources/views/default/panel/user/openai_chat/components/chat_js.blade.php
resources/views/default/panel/user/openai_chat/components/chat_sidebar_list.blade.php
resources/views/default/panel/user/openai_chat/components/chat_ai_message.blade.php
resources/views/default/scss/components/_chats-v2.scss
routes/panel.php
```

## WorkCore

```text
app/Domains/WorkCore/System/Modules/CRM/
app/Domains/WorkCore/System/Modules/CRM/Providers/WorkCRMServiceProvider.php
app/Domains/WorkCore/System/Modules/CRM/AI/CRMToolRegistry.php
app/Domains/WorkCore/System/Modules/CRM/routes/api.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120005_create_tz_crm_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120009_complete_tz_crm_foundation.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120054_create_tz_sales_pipeline_tables.php
app/Domains/WorkCore/System/Modules/Finance/
app/Domains/WorkCore/System/Actions/BusinessActionDispatcher.php
app/Domains/WorkCore/System/ReadModels/ReadModelRegistry.php
```

---

# Evidence limitations

The paid MagicAI CRM and AI Agent CRM tool packages were not present in the scanned MagicAI ZIP.

This report does not claim to know:

- CRM table names beyond comments and menu labels
- CRM record ownership
- CRM tenant model
- CRM permission quality
- CRM route middleware
- CRM API design
- CRM project depth
- CRM invoice ledger quality
- CRM payment reconciliation
- CRM action-card execution
- CRM Agent tool safety
- CRM tests

All such behaviour remains unverified until the separate extension package is scanned.
