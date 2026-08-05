# WorkCore Domain Dashboards Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the generic WorkCore workspace placeholder with governed, tenant-scoped domain dashboards for CRM, Operations, Workforce, Resources, Commercial, Ground Zero executive oversight, AI governance, and vertical overlays.

**Architecture:** Keep the existing five-workspace navigation catalogue and capability-filtered manifest as the presentation authority. Add dashboard read-model contracts to the canonical owning packages, expose them through the parent WorkCore extension, and render reusable dashboard components through one server-defined schema across desktop, tablet, mobile and Titan Flow. Section routes remain transactional screens; dashboards are decision surfaces that aggregate existing canonical records without creating a second data authority.

**Tech Stack:** PHP 8.2, Laravel 10, Blade, existing WorkCore `ReadModelRegistry`, `ReadModelExecutor`, tenant and permission contracts, MagicAI `<x-layouts.app>`, PHPUnit/Pest-compatible architecture tests, Python repository verification tests.

## Global Constraints

- Preserve existing extension folders, namespaces, route families, capability keys, migration history and canonical table ownership.
- Do not add dashboard-owned business tables.
- All queries must be scoped to the active `company_id` and authorized server-side.
- Cross-extension reads must use read models, contracts or stable query services; cross-extension writes must use governed actions or domain events.
- Menu visibility is not authorization.
- Missing tenant context, missing permissions or missing entitlements must fail closed.
- Dashboard responses must be deterministic, versioned and free of fabricated values.
- Desktop, tablet, mobile and Titan Flow must consume the same dashboard schema.
- Root dashboards summarize decisions and exceptions; section pages remain tables, boards, calendars, maps, workbenches or record profiles.
- Preserve administrator-controlled WorkCore menu order and enabled state.
- Do not introduce Livewire until dashboard read models and interaction contracts are stable.

---

## Dashboard Portfolio

### Core dashboards

1. `crm` — CRM & Growth
2. `operations` — Operations Control Tower
3. `workforce` — Workforce & Assurance
4. `resources` — Resources & Property Readiness
5. `commercial` — Commercial & Titan Money
6. `executive` — Ground Zero Executive
7. `ai-governance` — AI Operations & Governance

### Vertical overlays

1. `field-services` — Field Services Daily Operations
2. `accommodation` — Front Desk & Housekeeping
3. `ndis` — NDIS Delivery, Budget & Claims

### Required read-model keys

```text
workcore.dashboard.crm
workcore.dashboard.operations
workcore.dashboard.workforce
workcore.dashboard.resources
workcore.dashboard.commercial
workcore.dashboard.executive
workcore.dashboard.ai_governance
workcore.dashboard.field_services
workcore.dashboard.accommodation
workcore.dashboard.ndis
```

### Shared response contract

```php
array{
    schema_version: string,
    dashboard: string,
    company_id: int,
    generated_at: string,
    as_of: string,
    filters: array<string,mixed>,
    kpis: list<array{
        key: string,
        label: string,
        value: int|float|string|null,
        unit: string|null,
        comparison: array{value: int|float|null, direction: string|null, label: string|null}|null,
        severity: string|null,
        drilldown: array{route: string, params: array<string,mixed>}|null
    }>,
    panels: list<array{
        key: string,
        component: string,
        title: string,
        data: array<string,mixed>,
        empty_state: array{title: string, message: string}|null,
        drilldown: array{route: string, params: array<string,mixed>}|null
    }>,
    alerts: list<array{
        key: string,
        severity: string,
        title: string,
        message: string,
        count: int|null,
        action: array{route: string, params: array<string,mixed>}|null
    }>,
    freshness: array{
        source_keys: list<string>,
        oldest_source_at: string|null,
        stale: bool,
        stale_after_seconds: int
    }
}
```

---

### Task 1: Shared Dashboard Contracts and Registry

**Files:**
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardDefinition.php`
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardRegistry.php`
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardResponse.php`
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardFilter.php`
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardExecutor.php`
- Create: `app/extensions/WorkCore/System/Dashboards/dashboard-definitions.php`
- Modify: `app/extensions/WorkCore/System/WorkCoreServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_contract.py`

**Interfaces:**
- Consumes: `WorkCoreTenantResolver`, `WorkCorePermissionResolver`, `WorkCoreWorkspaceCatalogue`, `ReadModelExecutor`.
- Produces: `DashboardRegistry::get(string $key): ?DashboardDefinition`, `DashboardExecutor::execute(string $key, DashboardFilter $filter): DashboardResponse`.

- [ ] **Step 1: Write the failing architecture test**

Assert that the five workspace dashboards, executive dashboard, AI-governance dashboard and three vertical overlays have unique keys, registered read-model keys, capability requirements and route names.

- [ ] **Step 2: Run the targeted test and verify failure**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_contract.py -v
```

Expected: failure because dashboard contracts do not exist.

- [ ] **Step 3: Implement immutable dashboard definitions**

Each definition must contain:

```php
new DashboardDefinition(
    key: 'crm',
    label: 'CRM & Growth',
    readModel: 'workcore.dashboard.crm',
    routeName: 'dashboard.user.workcore.crm.index',
    capabilities: ['workcore.crm'],
    cacheSeconds: 60,
)
```

The registry must reject duplicate dashboard keys, route names and read-model keys.

- [ ] **Step 4: Implement filter normalization**

`DashboardFilter` must normalize and validate:

```text
as_of
period_from
period_to
business_line_public_id
branch_public_id
territory_public_id
owner_user_id
property_public_id
worker_public_id
currency
```

Unknown filter keys must be ignored at the HTTP boundary and never passed into raw SQL.

- [ ] **Step 5: Implement the executor**

The executor must:

1. Resolve active company and user.
2. Confirm at least one required capability is registered and entitled.
3. Invoke the registered read model through `ReadModelExecutor`.
4. Validate the returned dashboard schema.
5. Stamp `company_id`, `generated_at`, normalized filters and freshness.
6. Cache only after permission and tenant resolution.

- [ ] **Step 6: Run tests**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_contract.py -v
php app/extensions/WorkCore_Platform/tools/verify_workcore_entitlement_projection.php
```

- [ ] **Step 7: Commit**

```bash
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_contract.py
git commit -m "feat(workcore): add governed dashboard contracts"
```

---

### Task 2: Dashboard HTTP Contract and Workspace Rendering

**Files:**
- Create: `app/extensions/WorkCore/System/Http/Controllers/DashboardController.php`
- Create: `app/extensions/WorkCore/System/Http/Resources/DashboardResource.php`
- Create: `app/extensions/WorkCore/resources/views/dashboard.blade.php`
- Create: `app/extensions/WorkCore/resources/views/components/dashboard/kpi-card.blade.php`
- Create: `app/extensions/WorkCore/resources/views/components/dashboard/alert-list.blade.php`
- Create: `app/extensions/WorkCore/resources/views/components/dashboard/panel.blade.php`
- Modify: `app/extensions/WorkCore/routes/web.php`
- Modify: `app/extensions/WorkCore/routes/api.php`
- Modify: `app/extensions/WorkCore/System/Http/Controllers/WorkspaceController.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_routes.py`

**Interfaces:**
- Consumes: `DashboardExecutor`, existing workspace manifest and route middleware.
- Produces: `GET /api/v1/workcore/dashboards/{dashboard}` and dashboard Blade rendering for workspace root routes.

- [ ] **Step 1: Write route tests**

Cover:

- root workspace routes render `workcore::dashboard`;
- section routes continue to render `workcore::workspace` until replaced by transactional screens;
- API responses use the shared schema;
- missing tenant, entitlement or permission fails closed;
- unknown dashboard returns 404;
- inaccessible dashboards are not discoverable through the manifest.

- [ ] **Step 2: Run the test and verify failure**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_routes.py -v
```

- [ ] **Step 3: Add API route**

```php
Route::get('/dashboards/{dashboard}', DashboardController::class)
    ->name('api.workcore.dashboards.show');
```

Retain `auth:sanctum`, active tenant and dashboard-capability middleware.

- [ ] **Step 4: Render root dashboard routes through `DashboardController`**

Do not dynamically resolve Blade paths from request input. Resolve the dashboard key from the immutable registry.

- [ ] **Step 5: Add reusable Blade components**

Components must render only supplied values and provide explicit loading, empty, stale and error states. No decorative placeholder numbers are permitted.

- [ ] **Step 6: Run tests and syntax checks**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_routes.py -v
php -l app/extensions/WorkCore/System/Http/Controllers/DashboardController.php
php -l app/extensions/WorkCore/System/Http/Resources/DashboardResource.php
```

- [ ] **Step 7: Commit**

```bash
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_routes.py
git commit -m "feat(workcore): render governed dashboard surfaces"
```

---

### Task 3: CRM & Growth Dashboard

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/CRM/ReadModels/GetCrmDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/CRM/Providers/WorkCRMServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-business-network/tests/Feature/Dashboards/GetCrmDashboardTest.php`

**Interfaces:**
- Consumes: `OpportunityRepositoryContract`, lead, customer and activity repositories.
- Produces: read model `workcore.dashboard.crm`.

- [ ] **Step 1: Write failing feature tests**

Use two companies and assert strict tenant isolation. Cover:

```text
lead_count
qualified_lead_count
lead_conversion_rate
open_pipeline_value
weighted_pipeline_value
won_value
lost_value
overdue_opportunity_count
activities_due_today
stale_lead_count
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetCrmDashboardTest
```

- [ ] **Step 3: Implement CRM aggregates**

Reuse `GetCrmForecast` and repository methods where possible. Add only focused read queries for lead conversion, activity due dates and stale leads.

Required panels:

```text
pipeline_by_stage
lead_funnel
forecast_by_close_period
activities_due
stale_relationships
```

- [ ] **Step 4: Add drilldowns**

Every KPI and panel must link to existing CRM routes with compatible query parameters.

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter=GetCrmDashboardTest
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
```

- [ ] **Step 6: Commit**

```bash
git add app/extensions/WorkCore_Platform/packages/workcore-business-network
git commit -m "feat(workcore-crm): add growth dashboard read model"
```

---

### Task 4: Operations Control Tower Dashboard

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Operations/ReadModels/GetOperationsDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Operations/Providers/WorkOperationsServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/tests/Feature/Dashboards/GetOperationsDashboardTest.php`

**Interfaces:**
- Consumes: work-order, dispatch, scheduling, repair and roster repositories.
- Produces: read model `workcore.dashboard.operations`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
jobs_today
unassigned_jobs
late_jobs
sla_risk_jobs
appointments_today
available_workers
open_repairs
failed_checklists
compliance_blocked_jobs
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetOperationsDashboardTest
```

- [ ] **Step 3: Implement time-windowed aggregates**

Use `SearchDispatchBoard` semantics for worker, premises, assignment type, status and date filters. Keep map data bounded to the active window and maximum required fields.

Required panels:

```text
dispatch_queue
schedule_timeline
unassigned_work
worker_capacity
operational_exceptions
territory_load
```

- [ ] **Step 4: Implement severity rules**

```text
critical: emergency work unassigned or compliance-gated work dispatched
high: SLA breach, late arrival or worker unavailable
medium: recurring occurrence not generated or repair awaiting parts
low: non-blocking documentation gap
```

- [ ] **Step 5: Run tests and repository validation**

```bash
php artisan test --filter=GetOperationsDashboardTest
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
```

- [ ] **Step 6: Commit**

```bash
git add app/extensions/WorkCore_Platform/packages/workcore-work-operations
git commit -m "feat(workcore-operations): add control tower dashboard"
```

---

### Task 5: Commercial & Titan Money Dashboard

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Application/ReadModels/GetCommercialDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/WorkCoreFinanceServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-commercial/tests/Feature/Dashboards/GetCommercialDashboardTest.php`

**Interfaces:**
- Consumes: canonical Titan Money repositories and `TitanMoneyHealth`.
- Produces: read model `workcore.dashboard.commercial`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
quote_value
quote_conversion_rate
issued_invoice_value
overdue_receivables
cash_received
unmatched_payment_evidence
reconciliation_exceptions
expenses_awaiting_approval
purchase_orders_open
gross_margin
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetCommercialDashboardTest
```

- [ ] **Step 3: Implement canonical finance aggregates**

Use `tm_*` authority only. Do not query or introduce `tz_finance_*` or `tz_payment_*` tables.

Required panels:

```text
receivables_ageing
cash_movement
quote_to_cash_funnel
reconciliation_queue
collection_actions
profitability_by_job_or_business_line
money_health
```

- [ ] **Step 4: Preserve generative UI compatibility**

Expose the result through the shared dashboard schema and retain Titan Money's existing workspace/mobile/tablet generative-UI semantics.

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter=GetCommercialDashboardTest
php tests/Architecture/verify_magicai_workcore_extraction.php
php tests/Standalone/WorkCoreExtraction/run.php
```

- [ ] **Step 6: Commit**

```bash
git add app/extensions/WorkCore_Platform/packages/workcore-commercial
git commit -m "feat(titan-money): add commercial dashboard"
```

---

### Task 6: Workforce & Assurance Dashboard

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-workforce-assurance/src/Domains/WorkCore/System/Modules/Workforce/ReadModels/GetWorkforceDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-workforce-assurance/src/Domains/WorkCore/System/Modules/Workforce/Providers/WorkWorkforceServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-workforce-assurance/tests/Feature/Dashboards/GetWorkforceDashboardTest.php`

**Interfaces:**
- Consumes: worker, roster, attendance, attendance-verification, compliance, assurance and payroll repositories.
- Produces: read model `workcore.dashboard.workforce`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
workers_scheduled
unfilled_shifts
attendance_exceptions
verification_failures
certifications_expiring_30_days
workers_blocked
open_incidents
overdue_corrective_actions
payroll_exceptions
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetWorkforceDashboardTest
```

- [ ] **Step 3: Implement workforce aggregates**

Required panels:

```text
coverage_by_day
attendance_exception_queue
credential_expiry
skills_gap
incident_and_corrective_action_queue
leave_and_availability_pressure
```

Sensitive payroll and personal fields must be omitted unless field-level permission explicitly allows them.

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter=GetWorkforceDashboardTest
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
```

- [ ] **Step 5: Commit**

```bash
git add app/extensions/WorkCore_Platform/packages/workcore-workforce-assurance
git commit -m "feat(workcore-workforce): add assurance dashboard"
```

---

### Task 7: Resources & Property Readiness Dashboard

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Modules/Premises/Application/ReadModels/GetResourcesDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Modules/Premises/Providers/WorkPremisesServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/tests/Feature/Dashboards/GetResourcesDashboardTest.php`

**Interfaces:**
- Consumes: premises readiness, assets, fleet, inventory, supply and documents.
- Produces: read model `workcore.dashboard.resources`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
premises_not_ready
open_premises_hazards
assets_available
assets_overdue
assets_due_maintenance
fleet_unavailable
stockout_count
reorder_alert_count
expiring_documents
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetResourcesDashboardTest
```

- [ ] **Step 3: Implement readiness composition**

Reuse `GetPremiseServiceReadinessQuery` for access, service windows, service plans and key blockers. Batch queries to avoid one query per property.

Required panels:

```text
premises_readiness
asset_custody_and_maintenance
fleet_readiness
inventory_risk
supplier_delays
document_expiry
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter=GetResourcesDashboardTest
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
```

- [ ] **Step 5: Commit**

```bash
git add app/extensions/WorkCore_Platform/packages/workcore-property-operations
git commit -m "feat(workcore-resources): add readiness dashboard"
```

---

### Task 8: Ground Zero Executive Dashboard

**Files:**
- Create: `app/extensions/WorkCore/System/Dashboards/ReadModels/GetExecutiveDashboard.php`
- Modify: `app/extensions/WorkCore/System/WorkCoreServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_executive_dashboard.py`

**Interfaces:**
- Consumes: the five domain dashboard read models through the dashboard executor.
- Produces: read model `workcore.dashboard.executive`.

- [ ] **Step 1: Write failing tests**

Assert that the executive dashboard:

- invokes only dashboards the actor may access;
- omits unauthorized domains completely;
- returns no duplicated transactional rows;
- surfaces top exceptions and period comparisons;
- retains source freshness metadata.

- [ ] **Step 2: Verify failure**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_executive_dashboard.py -v
```

- [ ] **Step 3: Implement cross-domain composition**

Required KPI groups:

```text
revenue_and_cash
pipeline_and_growth
service_delivery
workforce_capacity
customer_experience
compliance_exposure
resource_readiness
```

Required panels:

```text
management_action_queue
cross_domain_trends
business_line_comparison
location_comparison
```

- [ ] **Step 4: Implement action ranking**

Rank alerts deterministically by:

1. safety or legal exposure;
2. customer harm or service failure;
3. cash or revenue impact;
4. operational blockage;
5. expiry or upcoming risk.

- [ ] **Step 5: Run tests**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_executive_dashboard.py -v
```

- [ ] **Step 6: Commit**

```bash
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_executive_dashboard.py
git commit -m "feat(workcore): add Ground Zero executive dashboard"
```

---

### Task 9: AI Operations & Governance Dashboard

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/AI/ReadModels/GetAiGovernanceDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/AI/Providers/WorkCoreAiServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/tests/Feature/AI/GetAiGovernanceDashboardTest.php`

**Interfaces:**
- Consumes: AI approval requests, runs, orchestration steps, tool runs, usage ledger, policy versions and outbox diagnostics.
- Produces: read model `workcore.dashboard.ai_governance`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
pending_approvals
runs_total
run_success_rate
run_failure_rate
high_risk_actions
human_overrides
failed_tool_runs
usage_tokens
estimated_cost
outbox_failures
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetAiGovernanceDashboardTest
```

- [ ] **Step 3: Implement governance aggregates**

Required panels:

```text
approval_queue
run_health
failure_clusters
usage_by_agent_and_model
high_risk_action_history
policy_adoption
outbox_and_delivery_health
```

Do not expose conversation or memory content in aggregate results. Drilldowns must respect the same field-level authorization as source screens.

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter=GetAiGovernanceDashboardTest
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
```

- [ ] **Step 5: Commit**

```bash
git add app/extensions/WorkCore_Platform/packages/workcore-shared-foundation
git commit -m "feat(workcore-ai): add governance dashboard"
```

---

### Task 10: Field Services Vertical Overlay

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Verticals/ReadModels/GetFieldServicesDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Verticals/Providers/WorkCoreVerticalOperationsServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/tests/Feature/Verticals/GetFieldServicesDashboardTest.php`

**Interfaces:**
- Consumes: operations, workforce, resources and commercial dashboard services.
- Produces: read model `workcore.dashboard.field_services`.

- [ ] **Step 1: Write failing tests**

Cover route load, first-time-fix rate, callbacks, warranty work, material readiness, travel/productive-time ratio and trade-compliance gates.

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetFieldServicesDashboardTest
```

- [ ] **Step 3: Implement overlay**

Expose only when the company vertical profile includes `field_services_core` or a descendant profile and the required capabilities are entitled.

- [ ] **Step 4: Run tests and commit**

```bash
php artisan test --filter=GetFieldServicesDashboardTest
git add app/extensions/WorkCore_Platform/packages/workcore-property-operations
git commit -m "feat(workcore-verticals): add field services dashboard"
```

---

### Task 11: Accommodation Vertical Overlay

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Modules/Premises/Application/Accommodation/ReadModels/GetAccommodationDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Verticals/Providers/WorkCoreVerticalOperationsServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/tests/Feature/Accommodation/GetAccommodationDashboardTest.php`

**Interfaces:**
- Consumes: `GetAccommodationBoard`, availability and folio read models.
- Produces: read model `workcore.dashboard.accommodation`.

- [ ] **Step 1: Write failing tests**

Cover occupancy, arrivals, departures, in-house count, open housekeeping, rooms not ready, unpaid balances and maintenance-blocked spaces.

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetAccommodationDashboardTest
```

- [ ] **Step 3: Implement overlay using canonical accommodation records**

Required panels:

```text
occupancy_board
arrivals_and_departures
housekeeping_queue
room_readiness
folio_exceptions
channel_mix
```

- [ ] **Step 4: Run tests and commit**

```bash
php artisan test --filter=GetAccommodationDashboardTest
git add app/extensions/WorkCore_Platform/packages/workcore-property-operations
git commit -m "feat(workcore-accommodation): add operations dashboard"
```

---

### Task 12: NDIS Vertical Overlay

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-workforce-assurance/src/Domains/WorkCore/System/Modules/NDIS/Application/ReadModels/GetNDISDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Verticals/Providers/WorkCoreVerticalOperationsServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-workforce-assurance/tests/Feature/NDIS/GetNDISDashboardTest.php`

**Interfaces:**
- Consumes: participant profile, budget position and claim queue read models.
- Produces: read model `workcore.dashboard.ndis`.

- [ ] **Step 1: Write failing tests**

Cover active participants, plans expiring, allocated, committed, delivered, claimed and available funds, delivered-but-unclaimed value, rejected claims, missing case notes and linked incidents.

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetNDISDashboardTest
```

- [ ] **Step 3: Implement overlay**

Required panels:

```text
budget_position
projected_budget_exhaustion
claim_queue
service_agreement_utilisation
worker_matching_exceptions
case_note_completion
participant_incidents
```

- [ ] **Step 4: Run tests and commit**

```bash
php artisan test --filter=GetNDISDashboardTest
git add app/extensions/WorkCore_Platform/packages/workcore-workforce-assurance app/extensions/WorkCore_Platform/packages/workcore-property-operations
git commit -m "feat(workcore-ndis): add delivery and claims dashboard"
```

---

### Task 13: Performance, Caching and Freshness

**Files:**
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardCacheKey.php`
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardFreshnessPolicy.php`
- Modify: `app/extensions/WorkCore/System/Dashboards/DashboardExecutor.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_performance.py`

**Interfaces:**
- Consumes: dashboard definition cache seconds, entitlement revision and normalized filters.
- Produces: permission-safe cache keys and freshness metadata.

- [ ] **Step 1: Write failing tests**

Cache keys must include:

```text
company_id
actor access fingerprint
entitlement revision
dashboard key
normalized filters
schema version
```

Assert that two companies, two access levels or two entitlement revisions never share cache entries.

- [ ] **Step 2: Implement caching**

Do not cache authorization failures. Invalidate through short TTL plus domain-event tags where host cache support exists.

Suggested TTLs:

```text
operations: 15 seconds
workforce: 30 seconds
commercial: 60 seconds
crm: 60 seconds
resources: 120 seconds
executive: 60 seconds
ai-governance: 30 seconds
```

- [ ] **Step 3: Add query-count and response-time assertions**

Target warm response below 500 ms under the repository's supported database fixture. Prevent per-record query loops.

- [ ] **Step 4: Run tests and commit**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_performance.py -v
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_performance.py
git commit -m "perf(workcore): harden dashboard caching and freshness"
```

---

### Task 14: Mobile, Tablet and Titan Flow Contract

**Files:**
- Create: `app/extensions/WorkCore/System/Dashboards/DashboardSurfaceProjector.php`
- Create: `app/extensions/WorkCore/resources/views/dashboard-mobile.blade.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_surfaces.py`
- Modify: `app/extensions/WorkCore/System/Http/Resources/DashboardResource.php`

**Interfaces:**
- Consumes: canonical dashboard response.
- Produces: `workspace`, `tablet_panel`, `mobile_card` and `titan_flow` projections without recomputing business values.

- [ ] **Step 1: Write failing tests**

Assert all surfaces retain identical KPI keys, severity and drilldown intent while permitting different panel density and ordering.

- [ ] **Step 2: Implement deterministic projection**

Mobile cards show no more than four primary KPIs, three alerts and one primary queue. Tablet panels show eight KPIs and two queues. Workspace and Titan Flow may expose the full authorized response.

- [ ] **Step 3: Run tests and commit**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_surfaces.py -v
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_dashboard_surfaces.py
git commit -m "feat(workcore): add multi-surface dashboard projections"
```

---

### Task 15: Full Verification and Documentation

**Files:**
- Create: `docs/workcore/dashboard-contract.md`
- Create: `docs/workcore/dashboard-permissions.md`
- Modify: `app/extensions/WorkCore_Platform/README.md`
- Test: all dashboard and repository suites.

- [ ] **Step 1: Document dashboard schema and read-model keys**

Include filters, response examples, permission behavior, cache rules, empty states and drilldown conventions.

- [ ] **Step 2: Document ownership boundaries**

Record canonical package ownership for each source:

```text
CRM and growth -> workcore-business-network
Finance, catalogue and supply -> workcore-commercial
Jobs, scheduling and dispatch -> workcore-work-operations
Premises, assets, accommodation and vertical profiles -> workcore-property-operations
Workers, attendance, assurance and NDIS -> workcore-workforce-assurance
Dashboard orchestration, tenancy, permissions and AI governance -> shared foundation / parent WorkCore boundary
```

- [ ] **Step 3: Run targeted tests**

```bash
python -m unittest discover app/extensions/WorkCore_Platform/tests -v
php artisan test --testsuite=Feature
```

- [ ] **Step 4: Run repository verification**

```bash
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
php tests/Architecture/verify_magicai_workcore_extraction.php
php tests/Standalone/WorkCoreExtraction/run.php
find app/extensions/WorkCore app/extensions/WorkCore_Platform/packages -type f -name '*.php' -print0 | xargs -0 -n1 -P4 php -l
```

- [ ] **Step 5: Verify no duplicate authority**

Confirm:

- no new dashboard business tables;
- no duplicate `Schema::create` owners;
- no new `tz_finance_*` or `tz_payment_*` authority;
- every read model is tenant-scoped;
- every route is permission and entitlement gated;
- section routes remain compatible.

- [ ] **Step 6: Commit**

```bash
git add docs/workcore app/extensions/WorkCore_Platform/README.md
git commit -m "docs(workcore): document dashboard contracts and verification"
```

---

## Recommended Pull Request Sequence

The portfolio is too large for one implementation PR. Deliver it as independently reviewable PRs:

1. **Dashboard foundation and HTTP contract** — Tasks 1–2
2. **CRM dashboard** — Task 3
3. **Operations dashboard** — Task 4
4. **Commercial dashboard** — Task 5
5. **Workforce dashboard** — Task 6
6. **Resources dashboard** — Task 7
7. **Ground Zero and AI governance** — Tasks 8–9
8. **Vertical overlays** — Tasks 10–12
9. **Performance and multi-surface projection** — Tasks 13–14
10. **Final documentation and verification** — Task 15

Each PR must be deployable without requiring later dashboard PRs. The parent dashboard registry must omit read models that are not registered or entitled.

## Acceptance Criteria

- The five WorkCore root routes render real domain dashboards rather than the structural placeholder.
- All ten dashboard read-model keys are registered only by their canonical owning package.
- Dashboard values are derived exclusively from canonical tenant-scoped records.
- Unauthorized fields, panels and whole dashboards are omitted rather than masked client-side.
- Existing 37 section routes remain stable.
- Every KPI and alert has a valid drilldown or explicitly declares no action.
- Empty companies render useful empty states with no fabricated sample data.
- Operations, attendance, urgent compliance and finance exception surfaces respect their freshness policies.
- Desktop, tablet, mobile and Titan Flow projections share identical business values.
- Repository ownership, checksum, migration and syntax verification remains green.
