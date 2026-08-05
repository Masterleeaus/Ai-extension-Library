# WorkCore Vertical Dashboards Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the generic WorkCore home experience with nine governed, tenant-scoped Titan Zero vertical dashboards assembled from reusable WorkCore domain widgets, while retaining CRM, Operations, Workforce, Resources and Commercial as drill-down workspaces and AI Governance as an administrator-only surface.

**Architecture:** The parent WorkCore extension owns the vertical-dashboard registry, resolver, composition schema, HTTP routes and presentation components. Canonical WorkCore packages continue to own their records, calculations and read models; they expose versioned dashboard widgets through registered providers. A company selects one primary vertical and may enable additional verticals per business line, producing tabs that share filters and terminology without duplicating business data.

**Tech Stack:** PHP 8.2, Laravel 10, Blade, existing WorkCore `ReadModelRegistry` and `ReadModelExecutor`, tenant/permission/entitlement contracts, existing vertical catalogue and terminology resolver, MagicAI `<x-layouts.app>`, PHPUnit/Pest-compatible feature tests, Python architecture tests.

## Global Constraints

- Do not create an executive or universal management dashboard.
- Do not add dashboard-owned business tables or copy canonical records into a dashboard database.
- Preserve extension folders, namespaces, route families, capability keys, migration history and canonical table ownership.
- All dashboard queries must be scoped to the active `company_id`; business-line, branch, territory, property and location filters must narrow that scope.
- Cross-extension reads must use registered read models, contracts or stable query services. Cross-extension writes must use governed actions or domain events.
- Missing tenant context, permission, capability or entitlement must fail closed.
- The primary vertical must be resolved server-side from an authorised company setting; request parameters cannot activate an unentitled vertical.
- A company may enable multiple verticals. One is primary; the others appear as authorised tabs.
- CRM, Operations, Workforce, Resources and Commercial remain canonical drill-down workspaces and reusable widget engines.
- AI Governance is administrator-only and must never appear as a customer vertical.
- Dashboard responses must be versioned, deterministic and free of fabricated values.
- Desktop, tablet, mobile and Titan Flow consume the same response contract with different presentation density.
- Realtime refresh is limited to dispatch, attendance, urgent compliance, active bookings, asset availability and critical finance exceptions.
- Cache keys must include company, user access scope, permission revision, entitlement revision, vertical, business line and normalized filters.
- No new frontend framework is introduced until the contracts and read models are stable.

---

## Approved Vertical Catalogue

| Key | Customer label | Business-type entries |
|---|---|---:|
| `field-services` | Field and Home Services | 24 |
| `accommodation` | BnB, Hotel and Rooming Services | 22 |
| `real-estate` | Real Estate | 20 |
| `salons-personal-care` | Salons and Personal Care | 20 |
| `fitness-membership` | Fitness and Membership Businesses | 22 |
| `automotive-services` | Automotive Services | 23 |
| `ecommerce-retail` | E-commerce and Retail | 24 |
| `hire-rental` | Hire and Rental | 22 |
| `booking-capacity` | Booking, Reservation and Capacity-Based Businesses | 28 |

The catalogue must contain exactly **9 verticals and 205 business-type entries**. Business-type labels are copied from the approved Titan Zero coverage list; normalized slugs are unique within their vertical.

## Required Dashboard Keys

```text
workcore.dashboard.vertical.field_services
workcore.dashboard.vertical.accommodation
workcore.dashboard.vertical.real_estate
workcore.dashboard.vertical.salons_personal_care
workcore.dashboard.vertical.fitness_membership
workcore.dashboard.vertical.automotive_services
workcore.dashboard.vertical.ecommerce_retail
workcore.dashboard.vertical.hire_rental
workcore.dashboard.vertical.booking_capacity
workcore.dashboard.ai_governance
```

The following key must not exist:

```text
workcore.dashboard.executive
```

## Shared Response Contract

```php
array{
    schema_version: string,
    dashboard_key: string,
    vertical_key: string,
    company_id: int,
    business_line_public_id: string|null,
    generated_at: string,
    as_of: string,
    tabs: list<array{
        key: string,
        label: string,
        active: bool,
        route: string
    }>,
    filters: array<string,mixed>,
    kpis: list<array{
        key: string,
        label: string,
        value: int|float|string|null,
        unit: string|null,
        severity: 'neutral'|'info'|'warning'|'critical'|null,
        comparison: array{value: int|float|null, direction: 'up'|'down'|'flat'|null, label: string|null}|null,
        drilldown: array{route: string, params: array<string,mixed>}|null
    }>,
    widgets: list<array{
        key: string,
        provider: string,
        component: string,
        title: string,
        size: 'small'|'medium'|'large'|'full',
        refresh_seconds: int|null,
        data: array<string,mixed>,
        unavailable_reason: string|null,
        drilldown: array{route: string, params: array<string,mixed>}|null
    }>,
    alerts: list<array{
        key: string,
        severity: 'info'|'warning'|'critical',
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

## Shared Domain Engines

| Engine | Canonical responsibility |
|---|---|
| Customer & CRM | Leads, customers, contacts, activities, pipelines, reviews and support |
| Scheduling & Capacity | Appointments, calendars, rooms, seats, staff, assets and availability |
| Operations & Dispatch | Jobs, assignments, routes, work status, recurring work and exceptions |
| Workforce | Workers, rosters, availability, attendance, credentials and payroll signals |
| Property & Resources | Premises, rooms, vehicles, assets, equipment, custody and maintenance |
| Inventory & Supply | Stock, reservations, materials, suppliers and purchase orders |
| Commercial | Quotes, invoices, payments, expenses, deposits, profitability and reconciliation |
| Compliance & Assurance | Inspections, hazards, incidents, credentials, corrective actions and evidence |
| Membership & Agreements | Memberships, subscriptions, leases, service agreements, hires and waivers |

---

### Task 1: Vertical Dashboard Catalogue and Contracts

**Files:**
- Create: `app/extensions/WorkCore/System/VerticalDashboards/VerticalDashboardDefinition.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/VerticalDashboardRegistry.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/VerticalDashboardResponse.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/VerticalDashboardFilter.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/VerticalWidgetDefinition.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Modify: `app/extensions/WorkCore/System/WorkCoreServiceProvider.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_catalogue.py`

**Interfaces:**
- Consumes: existing WorkCore vertical catalogue, terminology resolver, capability registry and entitlement resolver.
- Produces: `VerticalDashboardRegistry::get(string $key): ?VerticalDashboardDefinition` and `VerticalDashboardRegistry::all(): array`.

- [ ] **Step 1: Write the failing catalogue test**

Assert:

```text
vertical count = 9
business-type count = 205
dashboard keys are unique
vertical keys are unique
business-type slugs are unique within each vertical
each definition has a label, capability requirements, widgets and route name
workcore.dashboard.executive is absent
```

- [ ] **Step 2: Run the test and verify failure**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_catalogue.py -v
```

Expected: failure because the vertical dashboard registry does not exist.

- [ ] **Step 3: Implement immutable definitions**

Use these exact keys and labels:

```php
[
    'field-services' => 'Field and Home Services',
    'accommodation' => 'BnB, Hotel and Rooming Services',
    'real-estate' => 'Real Estate',
    'salons-personal-care' => 'Salons and Personal Care',
    'fitness-membership' => 'Fitness and Membership Businesses',
    'automotive-services' => 'Automotive Services',
    'ecommerce-retail' => 'E-commerce and Retail',
    'hire-rental' => 'Hire and Rental',
    'booking-capacity' => 'Booking, Reservation and Capacity-Based Businesses',
]
```

Each definition contains `key`, `label`, `readModel`, `routeName`, `capabilities`, `businessTypes`, `widgets`, `defaultPeriod`, `realtimeChannels` and `terminologyProfile`.

- [ ] **Step 4: Copy all approved business types into the definitions**

Preserve the user-approved labels. Generate normalized slugs with lowercase kebab case and reject duplicates during registry construction.

- [ ] **Step 5: Implement filter normalization**

Accept only:

```text
as_of
period_from
period_to
business_line_public_id
branch_public_id
territory_public_id
location_public_id
property_public_id
worker_public_id
resource_public_id
currency
timezone
```

Unknown keys are discarded at the HTTP boundary. Dates are normalized to the company timezone and converted to UTC for queries.

- [ ] **Step 6: Run tests**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_catalogue.py -v
php app/extensions/WorkCore_Platform/tools/verify_workcore_entitlement_projection.php
```

- [ ] **Step 7: Commit**

```bash
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_catalogue.py
git commit -m "feat(workcore): add Titan Zero vertical dashboard catalogue"
```

---

### Task 2: Primary Vertical Resolver and Multi-Vertical Tabs

**Files:**
- Create: `app/extensions/WorkCore/System/VerticalDashboards/CompanyVerticalSelection.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/CompanyVerticalResolver.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/VerticalTabBuilder.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Exceptions/NoAccessibleVertical.php`
- Modify: `app/extensions/WorkCore/System/Navigation/WorkCoreWorkspaceManifestBuilder.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_resolver.py`

**Interfaces:**
- Consumes: company profile, business-line profile, enabled verticals, capability registry, permission resolver and entitlement resolver.
- Produces: `CompanyVerticalResolver::resolve(int $companyId, int $userId, ?string $businessLinePublicId): CompanyVerticalSelection`.

- [ ] **Step 1: Write failing resolver tests**

Cover:

```text
single enabled vertical becomes primary
configured primary wins when accessible
inaccessible configured primary falls back to first accessible enabled vertical
request cannot activate an unentitled vertical
secondary verticals become ordered tabs
business-line vertical overrides company default only for that business line
no accessible vertical throws NoAccessibleVertical
AI Governance never appears in customer tabs
```

- [ ] **Step 2: Verify failure**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_resolver.py -v
```

- [ ] **Step 3: Implement server-side resolution**

Resolution order:

```text
1. authorised business-line primary vertical
2. authorised company primary vertical
3. first authorised enabled vertical using administrator order
4. fail closed
```

Do not trust `vertical` from the request until the selected key is checked against the authorised tab set.

- [ ] **Step 4: Build tab metadata**

Each tab returns key, translated label, active state and route. Tabs with no enabled capability are omitted, not disabled client-side.

- [ ] **Step 5: Run tests and commit**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_resolver.py -v
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_resolver.py
git commit -m "feat(workcore): resolve primary and secondary vertical dashboards"
```

---

### Task 3: Widget Provider Registry and Vertical Composer

**Files:**
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Contracts/DashboardWidgetProvider.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/DashboardWidgetRegistry.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/VerticalDashboardComposer.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/DashboardCacheKey.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Widgets/CrmWidgetProvider.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Widgets/OperationsWidgetProvider.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Widgets/WorkforceWidgetProvider.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Widgets/ResourcesWidgetProvider.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Widgets/CommercialWidgetProvider.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Widgets/AssuranceWidgetProvider.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_vertical_widget_composition.py`

**Interfaces:**
- Produces:

```php
interface DashboardWidgetProvider
{
    public function key(): string;

    public function supports(VerticalWidgetDefinition $widget): bool;

    public function load(
        VerticalWidgetDefinition $widget,
        CompanyVerticalSelection $selection,
        VerticalDashboardFilter $filter
    ): array;
}
```

- [ ] **Step 1: Write failing composition tests**

Assert deterministic widget order, tenant propagation, capability filtering, unavailable-widget reasons, duplicate-provider rejection, schema validation and cache-key isolation.

- [ ] **Step 2: Verify failure**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_widget_composition.py -v
```

- [ ] **Step 3: Implement provider registry**

Registration fails when two providers claim the same provider key. Providers execute only registered read models; they cannot use table names from another package.

- [ ] **Step 4: Implement the composer**

The composer:

1. Resolves the company and selected vertical.
2. Loads the immutable definition.
3. Filters widgets by entitlement, permission and access level.
4. Calls providers through registered contracts.
5. Converts missing optional capabilities to `unavailable_reason`.
6. Fails the whole response only when the vertical’s required core capability is unavailable.
7. Produces tabs, KPIs, widgets, alerts and freshness metadata.
8. Caches after tenant and permission resolution.

- [ ] **Step 5: Implement cache keys**

```php
sha1(json_encode([
    'company' => $companyId,
    'user' => $userId,
    'access_revision' => $accessRevision,
    'permission_revision' => $permissionRevision,
    'entitlement_revision' => $entitlementRevision,
    'vertical' => $verticalKey,
    'business_line' => $businessLinePublicId,
    'filters' => $filter->toArray(),
]))
```

- [ ] **Step 6: Run tests and commit**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_widget_composition.py -v
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_vertical_widget_composition.py
git commit -m "feat(workcore): compose vertical dashboards from domain widgets"
```

---

### Task 4: Vertical Dashboard Routes and Presentation

**Files:**
- Create: `app/extensions/WorkCore/System/Http/Controllers/VerticalDashboardController.php`
- Create: `app/extensions/WorkCore/System/Http/Resources/VerticalDashboardResource.php`
- Create: `app/extensions/WorkCore/resources/views/vertical-dashboard.blade.php`
- Create: `app/extensions/WorkCore/resources/views/components/vertical-dashboard/tabs.blade.php`
- Create: `app/extensions/WorkCore/resources/views/components/vertical-dashboard/kpi-card.blade.php`
- Create: `app/extensions/WorkCore/resources/views/components/vertical-dashboard/widget.blade.php`
- Create: `app/extensions/WorkCore/resources/views/components/vertical-dashboard/alert-list.blade.php`
- Modify: `app/extensions/WorkCore/routes/web.php`
- Modify: `app/extensions/WorkCore/routes/api.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_routes.py`

**Interfaces:**
- Produces:
  - `GET /dashboard/workcore`
  - `GET /dashboard/workcore/vertical/{vertical}`
  - `GET /api/v1/workcore/vertical-dashboards/{vertical}`

- [ ] **Step 1: Write failing route tests**

Cover default resolution, authorised tab switching, unknown key 404, inaccessible key 404, missing tenant failure, API schema, stale state and empty state.

- [ ] **Step 2: Verify failure**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_routes.py -v
```

- [ ] **Step 3: Register routes**

```php
Route::get('/dashboard/workcore', [VerticalDashboardController::class, 'default'])
    ->name('dashboard.user.workcore.vertical.default');

Route::get('/dashboard/workcore/vertical/{vertical}', [VerticalDashboardController::class, 'show'])
    ->name('dashboard.user.workcore.vertical.show');
```

The API route uses the existing authenticated tenant middleware and returns `VerticalDashboardResource`.

- [ ] **Step 4: Build reusable Blade components**

Components must render explicit loading, empty, partial, stale and error states. They must not display sample metrics when data is absent.

- [ ] **Step 5: Preserve drill-down workspaces**

CRM, Operations, Workforce, Resources and Commercial routes remain available as operational tables, boards, calendars, maps, workbenches and 360 profiles. They are linked from widget drilldowns rather than used as the customer home page.

- [ ] **Step 6: Run tests and commit**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_routes.py -v
php -l app/extensions/WorkCore/System/Http/Controllers/VerticalDashboardController.php
php -l app/extensions/WorkCore/System/Http/Resources/VerticalDashboardResource.php
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_routes.py
git commit -m "feat(workcore): add vertical dashboard routes and views"
```

---

### Task 5: Field and Home Services Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Operations/ReadModels/GetFieldServicesOperationsSnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Operations/Providers/WorkOperationsServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/tests/Feature/Dashboards/GetFieldServicesOperationsSnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.field_services.operations`.

- [ ] **Step 1: Write failing tenant-isolation tests**

Cover:

```text
jobs_booked_today
jobs_dispatched_today
jobs_completed_today
unassigned_jobs
overdue_jobs
emergency_jobs
available_workers
on_time_arrival_rate
first_time_completion_rate
technician_utilisation
callbacks_open
jobs_awaiting_invoice
compliance_blocked_jobs
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetFieldServicesOperationsSnapshotTest
```

- [ ] **Step 3: Implement the bounded snapshot**

Reuse dispatch-board status, assignment, worker, premises and date-window semantics. Add widgets:

```text
today_job_board
dispatch_and_route_map
unassigned_and_overdue_queue
worker_capacity
materials_and_equipment
site_access_and_compliance
completion_evidence
invoice_readiness
callbacks_and_warranty
customer_communications
```

- [ ] **Step 4: Register field-services composition**

Required engines: Operations and Workforce. Optional engines: CRM, Resources, Inventory, Commercial and Assurance.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetFieldServicesOperationsSnapshotTest
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-work-operations
git commit -m "feat(workcore): add field services dashboard pack"
```

---

### Task 6: Accommodation Dashboard Pack

**Files:**
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Modules/Premises/Application/Accommodation/ReadModels/GetAccommodationBoard.php`
- Create: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Modules/Premises/Application/Accommodation/ReadModels/GetAccommodationDashboardSnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Verticals/Providers/WorkCoreVerticalOperationsServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/tests/Feature/Dashboards/GetAccommodationDashboardSnapshotTest.php`

**Interfaces:**
- Consumes existing accommodation reservations, stays, housekeeping and premises readiness.
- Produces widget source `workcore.vertical.accommodation.operations`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
occupancy_rate
arrivals_today
departures_today
in_house_guests
rooms_available
rooms_dirty
rooms_blocked
open_housekeeping
late_turnovers
maintenance_blocked_nights
outstanding_guest_balances
average_length_of_stay
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetAccommodationDashboardSnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
occupancy_board
arrivals_and_departures
check_in_out_queue
housekeeping_board
room_readiness
maintenance_blocks
guest_requests_and_incidents
folio_and_balance_exceptions
linen_and_supply
rooming_agreements_and_notices
```

- [ ] **Step 4: Preserve accommodation record authority**

Do not create parallel reservation, stay, room, housekeeping or folio tables.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetAccommodationDashboardSnapshotTest
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-property-operations
git commit -m "feat(workcore): add accommodation dashboard pack"
```

---

### Task 7: Real Estate Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Modules/Premises/Application/RealEstate/ReadModels/GetRealEstatePortfolioSnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Verticals/Providers/WorkCoreVerticalOperationsServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/tests/Feature/Dashboards/GetRealEstatePortfolioSnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.real_estate.portfolio`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
properties_managed
occupied_properties
vacant_properties
days_vacant_average
applications_pending
inspections_due
leases_expiring
receivables_overdue
maintenance_open
compliance_blocked_properties
sales_pipeline_value
owner_requests_open
tenant_requests_open
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetRealEstatePortfolioSnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
portfolio_position
vacancy_and_leasing
sales_pipeline
applications_queue
inspection_schedule
lease_and_agreement_expiry
owner_and_tenant_requests
maintenance_coordination
property_compliance
portfolio_profitability
agent_workload
```

- [ ] **Step 4: Use premises readiness for compliance blockers**

Premise access, service windows, plans, keys and readiness blockers remain sourced from the existing premises readiness query.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetRealEstatePortfolioSnapshotTest
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-property-operations
git commit -m "feat(workcore): add real estate dashboard pack"
```

---

### Task 8: Salons and Personal Care Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Scheduling/ReadModels/GetSalonCapacitySnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Operations/Providers/WorkOperationsServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/tests/Feature/Dashboards/GetSalonCapacitySnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.salons.capacity`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
appointments_today
available_slots
late_arrivals
no_shows
waitlist_count
appointment_utilisation
staff_utilisation
rebooking_rate
average_client_spend
retail_attachment_rate
membership_package_balance
unpaid_balances
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetSalonCapacitySnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
today_appointment_book
availability_gaps
staff_room_capacity
walk_in_and_waitlist
client_rebooking
memberships_and_packages
retail_stock
staff_commissions
deposits_and_balances
reviews_and_service_recovery
```

- [ ] **Step 4: Apply terminology profiles**

The same resource type can render as chair, room, practitioner station or treatment room based on the selected business type.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetSalonCapacitySnapshotTest
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-work-operations
git commit -m "feat(workcore): add salons and personal care dashboard pack"
```

---

### Task 9: Fitness and Membership Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/Memberships/ReadModels/GetFitnessMembershipSnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Providers/WorkCoreBusinessNetworkServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-business-network/tests/Feature/Dashboards/GetFitnessMembershipSnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.fitness.membership`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
active_members
new_members
cancellations
net_member_growth
members_at_churn_risk
monthly_recurring_revenue
average_revenue_per_member
class_utilisation
waitlist_count
check_in_frequency
trial_conversion_rate
failed_payment_count
memberships_expiring
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetFitnessMembershipSnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
membership_position
growth_and_churn
class_schedule_and_capacity
trainer_coverage
check_ins_and_attendance
member_engagement
trials_and_leads
payments_and_failures
membership_expiries
equipment_and_facility_issues
staff_credentials
```

- [ ] **Step 4: Fail optional widgets independently**

Where membership or recurring billing capabilities are not enabled, return a clear unavailable reason while preserving schedule and attendance widgets.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetFitnessMembershipSnapshotTest
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-business-network
git commit -m "feat(workcore): add fitness and membership dashboard pack"
```

---

### Task 10: Automotive Services Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Repairs/ReadModels/GetAutomotiveWorkshopSnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Operations/Providers/WorkOperationsServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/tests/Feature/Dashboards/GetAutomotiveWorkshopSnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.automotive.workshop`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
vehicles_booked_today
awaiting_diagnosis
quotes_awaiting_approval
jobs_awaiting_parts
work_in_progress
ready_for_collection
bay_utilisation
technician_productivity
labour_recovery_rate
average_repair_order_value
comeback_rate
fleet_services_due
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetAutomotiveWorkshopSnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
workshop_job_board
vehicle_arrivals
diagnosis_queue
quote_approvals
jobs_waiting_for_parts
technician_and_bay_allocation
roadworthy_and_inspections
ready_for_collection
parts_stock_and_supplier_eta
warranty_and_comebacks
fleet_maintenance_schedule
towing_and_roadside_queue
```

- [ ] **Step 4: Link vehicle, work-order, parts and invoice drilldowns**

Use public identifiers and existing authorised routes. Never expose registration, VIN or customer details outside the active company and user access scope.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetAutomotiveWorkshopSnapshotTest
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-work-operations
git commit -m "feat(workcore): add automotive services dashboard pack"
```

---

### Task 11: E-commerce and Retail Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Commerce/ReadModels/GetRetailCommerceSnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/WorkCoreFinanceServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-commercial/tests/Feature/Dashboards/GetRetailCommerceSnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.ecommerce_retail.commerce`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
gross_sales
net_sales
order_count
average_order_value
gross_margin
orders_awaiting_payment
orders_awaiting_fulfilment
returns_open
refund_value
stockout_count
low_stock_count
repeat_purchase_rate
subscription_churn
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetRetailCommerceSnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
sales_today
order_fulfilment_queue
click_and_collect
returns_and_refunds
product_performance
channel_performance
location_performance
inventory_availability
purchase_orders_and_supplier_delays
abandoned_carts
subscription_orders
customer_support
product_profitability
payment_reconciliation
```

- [ ] **Step 4: Preserve canonical commercial authority**

Use current catalogue, inventory, supply, order, invoice and Titan Money authorities. Do not create dashboard order or inventory tables.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetRetailCommerceSnapshotTest
php tests/Architecture/verify_magicai_workcore_extraction.php
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-commercial
git commit -m "feat(workcore): add ecommerce and retail dashboard pack"
```

---

### Task 12: Hire and Rental Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Modules/Assets/Application/Hire/ReadModels/GetHireRentalSnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/src/Domains/WorkCore/System/Verticals/Providers/WorkCoreVerticalOperationsServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-property-operations/tests/Feature/Dashboards/GetHireRentalSnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.hire_rental.assets`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
assets_available
assets_reserved
assets_on_hire
returns_due_today
overdue_returns
assets_awaiting_preparation
maintenance_blocked_assets
asset_utilisation
revenue_per_asset
average_hire_duration
damage_rate
deposit_exposure
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetHireRentalSnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
availability_calendar
reservation_queue
preparation_and_dispatch
pickups_and_deliveries
returns_due_and_overdue
assets_on_hire
damage_and_condition
cleaning_and_turnaround
maintenance_blocks
deposits_and_usage_charges
contracts_and_waivers
asset_location
utilisation_and_profitability
```

- [ ] **Step 4: Enforce reservation conflict rules**

The dashboard reads the same availability and reservation rules used by booking actions; it must never infer availability from a simple status column alone.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetHireRentalSnapshotTest
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-property-operations
git commit -m "feat(workcore): add hire and rental dashboard pack"
```

---

### Task 13: Booking, Reservation and Capacity Dashboard Pack

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Scheduling/ReadModels/GetBookingCapacitySnapshot.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/src/Domains/WorkCore/System/Modules/Operations/Providers/WorkOperationsServiceProvider.php`
- Modify: `app/extensions/WorkCore/System/VerticalDashboards/vertical-dashboard-definitions.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-work-operations/tests/Feature/Dashboards/GetBookingCapacitySnapshotTest.php`

**Interfaces:**
- Produces widget source `workcore.vertical.booking_capacity.schedule`.

- [ ] **Step 1: Write failing tests**

Cover:

```text
capacity_available
capacity_booked
capacity_utilisation
bookings_today
waitlist_count
overbooking_conflicts
cancellations
no_shows
revenue_per_available_slot
average_booking_value
deposit_collection_rate
repeat_booking_rate
```

- [ ] **Step 2: Verify failure**

```bash
php artisan test --filter=GetBookingCapacitySnapshotTest
```

- [ ] **Step 3: Implement widgets**

```text
booking_calendar
capacity_timeline
available_slots
waitlist
overbooking_and_conflicts
staff_and_resource_availability
arrivals_and_check_ins
cancellations_and_no_shows
deposits_and_balances
group_and_recurring_bookings
venue_or_resource_preparation
booking_source_performance
capacity_forecast
```

- [ ] **Step 4: Support terminology without forking logic**

Render capacity units as appointments, seats, rooms, courts, vehicles, equipment, tables or places according to the vertical terminology profile.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetBookingCapacitySnapshotTest
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-work-operations
git commit -m "feat(workcore): add booking and capacity dashboard pack"
```

---

### Task 14: AI Operations and Governance Admin Surface

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/AI/ReadModels/GetAiGovernanceDashboard.php`
- Modify: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Providers/WorkCoreBusinessNetworkServiceProvider.php`
- Create: `app/extensions/WorkCore/System/Http/Controllers/AiGovernanceDashboardController.php`
- Modify: `app/extensions/WorkCore/routes/web.php`
- Modify: `app/extensions/WorkCore/routes/api.php`
- Test: `app/extensions/WorkCore_Platform/packages/workcore-business-network/tests/Feature/AI/GetAiGovernanceDashboardTest.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_ai_governance_routes.py`

**Interfaces:**
- Consumes AI approvals, agents, versions, orchestration runs and steps, tool runs, usage ledger, model/provider profiles, policy versions and audit/outbox records.
- Produces read model `workcore.dashboard.ai_governance`.

- [ ] **Step 1: Write failing authorization and tenant tests**

Cover administrator access, ordinary-user denial, cross-company isolation and absence from vertical tabs.

- [ ] **Step 2: Write failing metric tests**

Cover:

```text
approvals_pending
approvals_overdue
agent_runs_active
agent_runs_failed
tool_runs_failed
retries
human_overrides
reversals
usage_units
estimated_cost
high_risk_actions
policy_versions_active
outbox_failures
```

- [ ] **Step 3: Verify failure**

```bash
php artisan test --filter=GetAiGovernanceDashboardTest
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_ai_governance_routes.py -v
```

- [ ] **Step 4: Implement admin-only route and widgets**

```text
approval_queue
run_health
tool_failures
override_and_reversal_log
usage_and_cost
model_provider_agent_breakdown
policy_adoption
high_risk_action_log
outbox_and_audit_failures
```

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=GetAiGovernanceDashboardTest
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_ai_governance_routes.py -v
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/packages/workcore-business-network
git commit -m "feat(workcore): add administrator AI governance dashboard"
```

---

### Task 15: Multi-Surface Projection, Performance and Accessibility

**Files:**
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Projection/DashboardProjection.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Projection/WorkspaceProjection.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Projection/TabletProjection.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Projection/MobileProjection.php`
- Create: `app/extensions/WorkCore/System/VerticalDashboards/Projection/TitanFlowProjection.php`
- Create: `app/extensions/WorkCore/resources/views/vertical-dashboard-mobile.blade.php`
- Create: `app/extensions/WorkCore/resources/views/vertical-dashboard-tablet.blade.php`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_projection.py`
- Test: `app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_performance.py`

**Interfaces:**
- Consumes the shared `VerticalDashboardResponse`.
- Produces presentation-density projections without changing metric meaning or authorization.

- [ ] **Step 1: Write failing projection tests**

Assert:

```text
all surfaces retain dashboard_key, company_id, tabs, alerts and freshness
mobile prioritises urgent alerts, today queues and next actions
tablet retains operational boards and maps
workspace retains complete widget set
Titan Flow exposes concise cards and governed action links
no surface adds data not present in the shared response
```

- [ ] **Step 2: Write performance budgets**

```text
cached dashboard p95 <= 250 ms
uncached dashboard p95 <= 1500 ms
initial payload <= 250 KB before compression
no widget may execute more than one unbounded query
list widgets return at most 100 rows
map widgets return at most 500 lightweight points
```

- [ ] **Step 3: Implement projections and semantic markup**

Use heading order, labelled controls, keyboard-accessible tabs, non-colour severity labels and readable empty/error states.

- [ ] **Step 4: Run tests and commit**

```bash
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_projection.py -v
python -m unittest app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_performance.py -v
git add app/extensions/WorkCore app/extensions/WorkCore_Platform/tests
git commit -m "feat(workcore): project vertical dashboards across Titan surfaces"
```

---

### Task 16: Final Architecture Verification and Documentation

**Files:**
- Create: `app/extensions/WorkCore_Platform/tests/test_workcore_vertical_dashboard_architecture.py`
- Modify: `app/extensions/WorkCore_Platform/README.md`
- Modify: `app/extensions/WorkCore_Platform/native-extensions/catalogue.json`
- Modify: `app/extensions/WorkCore_Platform/docs/superpowers/specs/2026-08-04-workcore-navigation-workspaces-design.md`

**Interfaces:**
- Verifies the complete dashboard architecture and documents its boundaries.

- [ ] **Step 1: Add architecture assertions**

Assert:

```text
exactly 9 customer vertical dashboards
exactly 205 approved business-type entries
no executive dashboard definition, route, read model or navigation item
AI Governance is admin-only
every widget source is registered
every drilldown route exists
every read model enforces tenant context
no package imports another package's Eloquent models
no dashboard-owned business tables or migrations
five domain workspaces remain available
```

- [ ] **Step 2: Run all targeted dashboard tests**

```bash
python -m unittest discover app/extensions/WorkCore_Platform/tests -p "test_workcore_vertical_dashboard*.py" -v
php artisan test --filter=Dashboard
```

- [ ] **Step 3: Run repository verification**

```bash
python app/extensions/WorkCore_Platform/tools/validate_repository.py --repo app/extensions/WorkCore_Platform
php app/extensions/WorkCore_Platform/tools/verify_workcore_entitlement_projection.php
php tests/Architecture/verify_magicai_workcore_extraction.php
php tests/Standalone/WorkCoreExtraction/run.php
```

- [ ] **Step 4: Scan for forbidden architecture residue**

```bash
grep -R "workcore.dashboard.executive" app/extensions/WorkCore app/extensions/WorkCore_Platform
grep -R "Ground Zero" app/extensions/WorkCore app/extensions/WorkCore_Platform
```

Expected: no matches in runtime definitions, routes, tests or customer-facing documentation.

- [ ] **Step 5: Update documentation**

Document:

- nine customer verticals and their 205 business types;
- primary and secondary vertical resolution;
- shared domain widget engines;
- canonical ownership and no-second-authority rule;
- customer dashboard routes;
- administrator-only AI Governance;
- mobile, tablet, workspace and Titan Flow projections.

- [ ] **Step 6: Commit**

```bash
git add app/extensions/WorkCore app/extensions/WorkCore_Platform
git commit -m "docs(workcore): finalize vertical dashboard architecture"
```

---

## Recommended Pull Request Sequence

1. **Vertical foundation** — Tasks 1–3
2. **Routes and reusable presentation** — Task 4
3. **Field services** — Task 5
4. **Accommodation and real estate** — Tasks 6–7
5. **Salons and fitness** — Tasks 8–9
6. **Automotive** — Task 10
7. **E-commerce and retail** — Task 11
8. **Hire and booking** — Tasks 12–13
9. **AI Governance** — Task 14
10. **Multi-surface projection and final verification** — Tasks 15–16

Each pull request must be independently deployable, preserve existing routes and data, include tenant-isolation tests, and leave unavailable optional widgets explicit rather than fabricating metrics.

## Completion Criteria

The implementation is complete only when:

- Every authorised company resolves to one primary vertical dashboard.
- Multi-vertical companies can switch among authorised tabs.
- All nine approved verticals are registered.
- All 205 approved business-type entries are represented.
- The five WorkCore domain workspaces remain operational drilldowns.
- AI Governance is restricted to authorised administrators.
- No executive dashboard key, route or customer navigation entry exists.
- Every displayed metric is derived from canonical tenant-scoped records.
- Desktop, tablet, mobile and Titan Flow use the same response semantics.
- Targeted tests and repository verification commands pass.
