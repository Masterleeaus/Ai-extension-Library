---
title: "MagicAI 11 Menu, Navigation and Dashboard Hooks"
scan_number: 4
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
scope: "Magicai-Server-Files(3).zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/04-magicai-menu-navigation-and-dashboard-hooks.md"
---

# Scan 04 — MagicAI 11 Menu, Navigation and Dashboard Hooks

## Executive conclusion

MagicAI 11 has a mature, database-backed navigation system that can host WorkCore as a first-class operational suite.

The system supports:

- Parent and child menu hierarchies
- User and administrator entries
- Labels and collapsible groups
- Route names and optional route slugs
- Active-route patterns
- Conditional visibility
- Extension visibility
- Plan entitlements
- Administrator permission coupling
- Reordering
- Enabling and disabling
- Custom menu records
- Icons, SVG, badges and letter icons
- Bolt/highlight menu styling
- Theme-aware rendered-menu caching
- Optional per-user rendered-menu caching
- Mobile and desktop navigation components
- Administrator dashboard widget ordering and visibility

The static menu catalogue contains:

| Measure | Count |
|---|---:|
| Static menu definitions | 299 |
| Root definitions | 125 |
| Child definitions | 174 |
| Extension-labelled definitions | 162 |
| Administrator definitions | 118 |
| Item definitions | 293 |
| Label definitions | 4 |
| Duplicate static keys | 0 |

However, WorkCore should not be integrated by simply adding dozens of arrays directly to `MenuService`.

The current implementation has several important weaknesses:

1. Menu definitions are hard-coded in a service with approximately 5,800 lines.
2. Extension packages do not have a general menu-contributor interface.
3. `MenuSeeder` uses `firstOrCreate`, so changed labels, routes, icons, orders and conditions are not updated during upgrades.
4. Some menu visibility expressions depend on the current authenticated user.
5. The merged menu is cached globally and forever under `dynamic_menu_key`.
6. The rendered navigation cache is shared by users on the same plan, locale, user type and theme unless optional per-user caching is enabled.
7. WorkCore company membership, branch, role and capability revision are absent from cache keys.
8. Administrator route access is derived from menu keys and active-route patterns, making menu registration part of the authorisation system.
9. Dropdown active-state handling often compares exact URLs rather than applying all declared route patterns.
10. The mobile bottom menu is a fixed AI/content menu, not a configurable field-worker navigation surface.
11. The user dashboard is a large static Blade page and has no native extension widget registry.
12. The administrator dashboard widget registry is platform-focused and not reusable as a complete WorkCore manager dashboard.

The recommended approach is:

> Add one generic menu-contributor and dashboard-contributor contract to the Titan Zero host, then let WorkCore register its menus and operational dashboard surfaces from its own extension provider.

WorkCore should introduce four manager navigation domains:

- Operations
- Workforce
- Resources
- Commercial

It should also introduce a separate field-worker shell:

- My Day
- Current Job
- Schedule
- Messages
- Forms
- Knowledge

---

# 1. Scan scope

This scan inspected:

- `MenuService`
- Menu database model
- Menu migrations and indexes
- Menu seeding
- Rendered navigation templates
- Plan checks
- Plan feature extraction
- Administrator permissions
- Menu cache keys
- Global merged-menu cache
- Theme-specific navigation behaviour
- Bolt menu
- Bottom/mobile menu
- Floating creation menu
- Administrator dashboard widgets
- User dashboard composition
- Dashboard widget routes and controls
- CRM and Sales menu contracts
- WorkCore navigation requirements

---

# 2. Menu architecture

The central service is:

```text
App\Services\Common\MenuService
```

Its major responsibilities are:

```text
define static menu catalogue
→ merge static definitions with database records
→ build parent/child hierarchy
→ evaluate visibility expressions
→ expose plan feature lists
→ cache merged menu
→ clear rendered menu caches
→ update parent and child ordering
→ expose bolt-menu styling
```

The runtime menu is a hybrid:

```text
Static PHP definition
+
Database structure and administrator settings
+
Current extension/provider state
+
Feature settings
+
Subscription-plan checks
+
Current theme
```

This hybrid model is powerful but difficult to extend safely because definitions and state are distributed across code, database records, caches and plan logic.

---

# 3. Menu database schema

The `menus` table includes:

```text
id
parent_id
key
route
route_slug
label
icon
svg
order
is_active
params
type
badge
extension
bolt_menu
bolt_background
bolt_foreground
letter_icon
letter_icon_bg
custom_menu
created_at
updated_at
```

## 3.1 Confirmed constraints and indexes

- `key` is unique.
- `parent_id` is indexed.
- `parent_id + order` is indexed.
- `is_active` is indexed.
- Parent relationships are managed by application logic.
- No confirmed foreign-key constraint protects `parent_id`.

## 3.2 Model capabilities

The model exposes:

- `parent()`
- `children()`
- `active()` scope
- `parent()` scope

Children are ordered by their `order` value.

## 3.3 Type inconsistency

The database migration defines `extension` as a string.

Static definitions commonly provide:

```text
true
false
null
```

This creates ambiguous meaning.

WorkCore should not depend on this column as a reliable owner identifier.

## Recommended ownership fields

Add:

```text
owner_extension
definition_version
vendor_order
custom_order
vendor_enabled
administrator_enabled
```

This separates extension defaults from administrator customisation.

---

# 4. Static menu definition contract

A typical menu definition supports:

```php
[
    'parent_key'       => null,
    'key'              => '...',
    'route'            => '...',
    'route_slug'       => null,
    'label'            => '...',
    'icon'             => '...',
    'svg'              => null,
    'order'            => 1,
    'is_active'        => true,
    'params'           => [],
    'type'             => 'item',
    'badge'            => null,
    'extension'        => true,
    'active_condition' => ['route.pattern.*'],
    'show_condition'   => true,
    'is_admin'         => false,
]
```

Additional fields observed include:

```text
class
onclick
data-name
letter_icon
letter_icon_bg
bolt_menu
bolt_background
bolt_foreground
```

## Supported menu types

The core templates support:

- Item
- Dropdown item
- Label
- Collapsible label
- Divider

---

# 5. Menu generation pipeline

The effective generation sequence is:

```text
Load root menu rows
→ eager-load children
→ optionally filter inactive rows
→ order roots
→ call static MenuService::data()
→ merge each database row with matching static definition
→ recursively merge children
→ store merged result in global cache
→ apply demo transformation
→ apply Oupi-specific user check metadata
→ render through plan and administrator filters
→ cache rendered Blade navigation
```

## 5.1 Static definition wins for dynamic behaviour

Static data supplies fields such as:

- `show_condition`
- `active_condition`
- Extension registration checks
- Settings checks
- Administrator flags

Database records supply:

- Current hierarchy
- Order
- Enabled status
- Stored label/icon/route values
- Custom menus

The merge order means database fields can overwrite matching static scalar fields, while dynamic-only static fields remain available.

---

# 6. Menu seeding and upgrade weakness

`MenuSeeder` does:

```php
Menu::firstOrCreate(
    ['key' => $item['key']],
    [/* full definition */]
);
```

This creates missing records but does not update existing ones.

## Consequences

An extension update that changes:

- Route
- Label
- Icon
- Default order
- Parent
- Type
- Badge
- Extension marker

will not automatically update the database record.

This can leave old navigation after an extension upgrade.

## WorkCore requirement

WorkCore menus need an owner-aware upsert process:

```text
create missing vendor definitions
update vendor-controlled fields
preserve administrator-controlled order and enabled state
record definition version
disable removed vendor keys
regenerate caches
```

Do not delete administrator-created custom menu records.

---

# 7. Menu cache architecture

MagicAI uses two major cache layers.

## 7.1 Global merged-menu cache

`MenuService::generate()` stores the merged data forever under:

```text
dynamic_menu_key
dynamic_menu_key_active
```

These keys do not include:

- User
- Plan
- Company
- Operational role
- Branch
- Capability set
- Theme
- Locale

## 7.2 Rendered Blade cache

The rendered navigation is cached for 24 hours.

Its default key contains:

```text
locale
user type
plan ID
theme
navbar-menu
```

An optional setting can append user ID.

## 7.3 Critical user-context issue

`MenuService::data()` reads authenticated-user state for conditions such as:

- Administrator status
- Affiliate visibility
- Team functionality
- Plan-related behaviour

The result can then enter the global forever cache.

This means user-sensitive visibility can be evaluated in one request and reused for another user until regeneration.

The rendered cache adds another layer that is shared by users on the same plan unless per-user caching is enabled.

## WorkCore risk

Two users can share a MagicAI plan but have different WorkCore:

- Companies
- Membership status
- Operational roles
- Branch access
- Territories
- Capabilities
- Employment status
- Certification status

A plan-based cache cannot safely contain final WorkCore navigation.

## Required WorkCore cache key

At minimum include:

```text
locale
theme
user ID
active company ID
membership ID
operational role revision
capability revision
plan ID
menu definition revision
```

Better still, cache the structural catalogue globally but apply company and capability visibility after retrieval.

---

# 8. Cache invalidation

`MenuService::regenerate()` clears:

```text
dynamic_menu_key
dynamic_menu_key_active
navbar_cache_registry
current rendered menu key
plan-based admin and user menu keys
```

When per-user cache mode is enabled, it calls:

```text
optimize:clear
```

rather than enumerating user keys.

## Regeneration triggers found

Menu regeneration is called from areas including:

- Settings updates
- AI tools settings
- Plan creation
- Payments and transfers
- Installation helper
- Activity creation
- Extension installation

## WorkCore requirement

Regenerate or revise WorkCore navigation when:

- Active company changes
- Membership changes
- Role changes
- Capabilities change
- Worker status changes
- Module entitlement changes
- Vertical pack changes
- Extension version changes
- Menu customisation changes

Do not clear all application caches for every user-role update.

Use revisioned cache keys.

---

# 9. Plan entitlement hooks

MagicAI exposes two plan catalogue methods:

```text
planAiToolsMenu()
planFeatureMenu()
```

## 9.1 AI tool plan items

The AI tool catalogue includes many content and extension capabilities.

Plan-only entries are also added for features that do not appear directly in the sidebar.

CRM contributes:

```text
ext_crm_dropdown
ext_sales_dropdown
```

when the CRM extension provider is loaded.

## 9.2 Feature plan items

The general feature catalogue includes selected navigation items such as:

- Personal API key
- Brand Voice
- Support
- Integration
- Custom templates
- Chat training
- Creative Suite
- AI Influencer
- URL to Video
- Viral Clips
- Influencer Avatar

## 9.3 Plan rendering

`PlanHelper::planMenuCheck()`:

1. Lets administrators through.
2. Lets users without a plan through at this layer.
3. Builds current plan AI-tool and feature key lists.
4. Checks the user's plan arrays.
5. Returns whether the requested key is enabled.

## WorkCore plan keys

Recommended plan-level keys:

```text
ext_workcore_core
ext_workcore_operations
ext_workcore_workforce
ext_workcore_resources
ext_workcore_commercial
ext_workcore_offline
ext_workcore_ai_actions
```

Vertical pack keys:

```text
ext_workcore_cleaning
ext_workcore_property
ext_workcore_home_services
```

## Important boundary

Plan access answers:

> Did the subscriber purchase this module?

WorkCore capability checks answer:

> May this actor perform this action in this company?

Both checks are required.

---

# 10. Administrator permission coupling

MagicAI administrator authorisation is tightly coupled to the menu.

For non-super administrators, `AdminPermissionMiddleware`:

1. Loads the global administrator role permissions.
2. Generates the menu.
3. Selects entries marked `is_admin`.
4. Finds menu keys present in the administrator permissions.
5. Collects their active route patterns.
6. Allows the request only if the named route matches.

## Consequences

A WorkCore administrator route can be denied when:

- Its menu key is missing.
- Its permission is missing.
- Its active route pattern is incomplete.
- A nested route is not covered.
- Menu caches are stale.
- The menu is conditionally hidden.

Menu registration is therefore also part of administrator route authorisation.

## WorkCore requirement

For every WorkCore administrator area, define:

```text
menu key
permission key
route family
active patterns
plan requirement
global setting
extension owner
```

Super-admin bypass should remain, but all actions must still be audited.

---

# 11. Active-route behaviour

Individual menu items can use declared active patterns through:

```text
activeRoute(...)
```

Dropdown parents and children also perform exact URL comparisons.

## Limitation

Exact URL comparisons do not reliably highlight parents for:

- Detail routes
- Edit routes
- Nested records
- Wizards
- Action review screens
- Dynamic identifiers

## WorkCore requirement

Every WorkCore root should declare wildcard route patterns.

Examples:

```text
dashboard.user.operations.*
dashboard.user.workforce.*
dashboard.user.resources.*
dashboard.user.commercial.*
dashboard.admin.workcore.*
```

The dropdown renderer should use these patterns rather than only exact child URLs.

---

# 12. Theme behaviour

The menu cache includes the active theme.

The Oupi dashboard theme gets an additional `performUserCheck` value from `MenuHelper`.

Other themes rely on the shared menu partial and plan checks.

## WorkCore requirement

WorkCore should register semantic menu data, not theme-specific markup.

Theme adapters can render the same menu definition differently.

No WorkCore domain code should depend on a particular MagicAI dashboard theme.

---

# 13. Bolt menu

MagicAI supports menu records with:

```text
bolt_menu
bolt_background
bolt_foreground
```

`boltMenu()` extracts those definitions for special middle-navigation presentation.

This can support promoted WorkCore actions such as:

- New job
- Dispatch board
- Current exceptions
- Invoice-ready work

However, it should remain a presentation choice, not an authorisation path.

---

# 14. Mobile bottom menu

The current mobile bottom menu is hard-coded to four positions:

1. AI Chat
2. Documents
3. Search
4. Open template/generator menu

It does not use the database menu system.

It is designed for MagicAI content generation, not field operations.

## WorkCore field-worker replacement

Recommended field-worker bottom navigation:

```text
My Day
Current Job
Messages
More
```

The More panel should include:

```text
Schedule
Forms
Photos
Incidents
Knowledge
Profile
Sync Status
```

Use large touch targets and stable positions.

Do not let AI-generated navigation reorder primary field controls.

---

# 15. Floating creation menu

The desktop floating menu is also hard-coded.

It offers creation shortcuts for MagicAI content types such as:

- Document
- Image
- Code
- Transcription

## WorkCore manager creation menu

Recommended context-aware actions:

```text
New customer
New work order
New appointment
New incident
New purchase request
New inspection
```

Only show actions the actor is authorised to create.

The menu should use the WorkCore capability registry rather than global feature settings alone.

---

# 16. Administrator dashboard widgets

MagicAI provides a database-backed administrator widget registry with 18 widget types:

```text
premium-advantages
what-is-new
usage-overview
finance
revenue-source
api-cost-distribution
top-countries
cost-management
new-customers
recent-transactions
users-and-platform
user-traffic
popular-ai-tools
generated-content
users
user-client
recent-activity
system-status
```

Administrators can:

- Reorder widgets
- Enable or disable widgets

The widget list is cached under:

```text
dashboard_widgets
```

## Limitation

The enum and view set are closed and platform/SaaS oriented.

There is no confirmed generic extension dashboard-widget contributor.

## WorkCore administrator widgets

Potential host administration widgets include:

```text
WorkCore version and health
Migration state
Queue health
Outbox backlog
Offline sync backlog
Failed actions
Storage health
Integration health
Data retention status
```

These belong on the platform administrator dashboard.

---

# 17. User dashboard

The MagicAI user dashboard is a large static Blade page oriented toward:

- Announcements
- AI Assistant
- Subscription plan
- Team invitations
- Recent content
- Documents
- Templates
- Account summary
- Chatbot totals
- Hours saved

It has no native user-dashboard widget registry equivalent to the administrator dashboard.

## WorkCore implication

Titan Flow should not be injected through fragile edits throughout this Blade page.

Recommended options:

### Initial approach

Add a WorkCore-owned route:

```text
dashboard.user.operations.index
```

and make the WorkCore Operations overview the default landing page for eligible operational users.

### Long-term approach

Add a user-dashboard contributor contract:

```php
UserDashboardContributorContract
```

Contributors register:

- Widget key
- Component/view
- Required module
- Required capability
- Supported user role
- Preferred size
- Priority
- Data provider
- Cache policy

---

# 18. Recommended top-level WorkCore menu

## 18.1 Operations root

```php
[
    'parent_key'       => null,
    'key'              => 'ext_workcore_operations',
    'route'            => 'dashboard.user.operations.index',
    'label'            => 'Operations',
    'icon'             => 'tabler-briefcase-2',
    'order'            => 6,
    'is_active'        => true,
    'type'             => 'item',
    'extension'        => true,
    'active_condition' => ['dashboard.user.operations.*'],
    'show_condition'   => WorkCoreMenuPolicy::showOperations(),
]
```

Children:

| Order | Key | Label | Route |
|---:|---|---|---|
| 1 | `workcore_operations_overview` | Overview | `dashboard.user.operations.index` |
| 2 | `workcore_jobs` | Jobs and Work Orders | `dashboard.user.operations.jobs.index` |
| 3 | `workcore_schedule` | Schedule | `dashboard.user.operations.schedule.index` |
| 4 | `workcore_dispatch` | Dispatch | `dashboard.user.operations.dispatch.index` |
| 5 | `workcore_map` | Map and Territories | `dashboard.user.operations.map.index` |
| 6 | `workcore_recurring` | Recurring Services | `dashboard.user.operations.recurring.index` |
| 7 | `workcore_customers_sites` | Customers and Sites | `dashboard.user.operations.customers.index` |
| 8 | `workcore_forms` | Forms and Checklists | `dashboard.user.operations.forms.index` |
| 9 | `workcore_inspections` | Inspections and Compliance | `dashboard.user.operations.inspections.index` |
| 10 | `workcore_operations_reports` | Reports | `dashboard.user.operations.reports.index` |
| 11 | `workcore_operations_assistant` | Operations Assistant | `dashboard.user.operations.assistant.index` |

## 18.2 Workforce root

```php
[
    'key'              => 'ext_workcore_workforce',
    'route'            => 'dashboard.user.workforce.index',
    'label'            => 'Workforce',
    'icon'             => 'tabler-users-group',
    'order'            => 7,
    'extension'        => true,
    'active_condition' => ['dashboard.user.workforce.*'],
    'show_condition'   => WorkCoreMenuPolicy::showWorkforce(),
]
```

Children:

| Order | Key | Label | Route |
|---:|---|---|---|
| 1 | `workcore_workers` | Workers | `dashboard.user.workforce.workers.index` |
| 2 | `workcore_rosters` | Rosters | `dashboard.user.workforce.rosters.index` |
| 3 | `workcore_attendance` | Attendance | `dashboard.user.workforce.attendance.index` |
| 4 | `workcore_timesheets` | Timesheets | `dashboard.user.workforce.timesheets.index` |
| 5 | `workcore_leave` | Leave | `dashboard.user.workforce.leave.index` |
| 6 | `workcore_skills_certifications` | Skills and Certifications | `dashboard.user.workforce.skills.index` |
| 7 | `workcore_workforce_reports` | Reports | `dashboard.user.workforce.reports.index` |

## 18.3 Resources root

```php
[
    'key'              => 'ext_workcore_resources',
    'route'            => 'dashboard.user.resources.index',
    'label'            => 'Resources',
    'icon'             => 'tabler-packages',
    'order'            => 8,
    'extension'        => true,
    'active_condition' => ['dashboard.user.resources.*'],
    'show_condition'   => WorkCoreMenuPolicy::showResources(),
]
```

Children:

| Order | Key | Label | Route |
|---:|---|---|---|
| 1 | `workcore_assets` | Assets | `dashboard.user.resources.assets.index` |
| 2 | `workcore_fleet` | Fleet | `dashboard.user.resources.fleet.index` |
| 3 | `workcore_inventory` | Inventory | `dashboard.user.resources.inventory.index` |
| 4 | `workcore_stock_locations` | Stock Locations | `dashboard.user.resources.stock-locations.index` |
| 5 | `workcore_suppliers` | Suppliers | `dashboard.user.resources.suppliers.index` |
| 6 | `workcore_purchase_orders` | Purchase Orders | `dashboard.user.resources.purchase-orders.index` |
| 7 | `workcore_knowledge` | Knowledge Base | `dashboard.user.resources.knowledge.index` |

## 18.4 Commercial root

```php
[
    'key'              => 'ext_workcore_commercial',
    'route'            => 'dashboard.user.commercial.index',
    'label'            => 'Commercial',
    'icon'             => 'tabler-report-money',
    'order'            => 9,
    'extension'        => true,
    'active_condition' => ['dashboard.user.commercial.*'],
    'show_condition'   => WorkCoreMenuPolicy::showCommercial(),
]
```

Children:

| Order | Key | Label | Route |
|---:|---|---|---|
| 1 | `workcore_service_catalogue` | Service Catalogue | `dashboard.user.commercial.services.index` |
| 2 | `workcore_job_costing` | Job Costing | `dashboard.user.commercial.job-costing.index` |
| 3 | `workcore_variations` | Variations | `dashboard.user.commercial.variations.index` |
| 4 | `workcore_billable_work` | Billable Work | `dashboard.user.commercial.billable.index` |
| 5 | `workcore_invoice_ready` | Invoice-Ready Jobs | `dashboard.user.commercial.invoice-ready.index` |
| 6 | `workcore_profitability` | Profitability | `dashboard.user.commercial.profitability.index` |

---

# 19. WorkCore administrator menu

Root:

```text
workcore_admin
dashboard.admin.workcore.index
```

Recommended children:

```text
WorkCore Overview
Companies and Memberships
Operational Roles
Modules
Vertical Packs
AI Action Governance
Offline and Sync
Integrations
Outbox
Audit and Rewind
Data Retention
Numbering and Sequences
System Health
```

Every key must also become an administrator permission key or be covered by the root permission and wildcard route family.

---

# 20. WorkCore menu visibility policy

Do not embed large user-specific expressions directly in `MenuService`.

Use a dedicated policy service:

```php
WorkCoreMenuPolicy
```

Example decisions:

```text
showOperations
showWorkforce
showResources
showCommercial
showAdmin
showCurrentJob
showOfflineStatus
```

Each decision should consider:

```text
WorkCore installed and enabled
MagicAI plan entitlement
active company
active membership
module enabled for company
actor capability
worker status
branch or territory
route availability
```

The policy should return stable booleans and a revision token for caching.

---

# 21. Proposed menu-contributor contract

```php
interface MenuContributorContract
{
    public function owner(): string;

    public function version(): string;

    public function definitions(): array;
}
```

MagicAI/Titan can collect contributors from the container:

```text
core menu contributor
CRM contributor
WorkCore contributor
vertical-pack contributors
AI tool contributors
```

The host should merge contributors before database synchronisation.

This avoids editing a 5,800-line central service for every extension.

---

# 22. Proposed dashboard-contributor contracts

## User dashboard

```php
interface UserDashboardContributorContract
{
    public function widgets(UserContext $context): array;
}
```

## Administrator dashboard

```php
interface AdminDashboardContributorContract
{
    public function widgets(AdminContext $context): array;
}
```

Widget definitions should include:

```text
key
owner
label
view/component
size
order
required entitlement
required capability
cache policy
data provider
```

---

# 23. Titan Flow recommendation

For managers, Titan Flow should become the operational home.

Recommended initial cards:

```text
Today's jobs
Unassigned work
Late work
Schedule conflicts
Workers unavailable
Pending approvals
Incidents and hazards
Compliance expiry
Low inventory
Invoice-ready jobs
Customer follow-ups
AI recommendations
```

The user can switch views:

```text
Table
Calendar
Board
Map
Timeline
```

Titan Flow should consume WorkCore read models.

It should not duplicate WorkCore records in dashboard-specific tables.

---

# 24. Field-worker navigation recommendation

Field workers require a separate stable shell.

## Primary bottom navigation

```text
My Day
Current Job
Messages
More
```

## Current job screen

```text
Job summary
Address and directions
Customer instructions
Checklist
Photos
Issues
Materials used
Time
Complete job
```

## More

```text
Schedule
Forms
Knowledge
Sync status
Profile
```

This interface should prioritise muscle memory, large touch targets and offline operation.

Do not expose the full manager sidebar to field workers.

---

# 25. Host changes required before WorkCore menus

## Critical

1. Remove authenticated-user conditions from global forever menu cache.
2. Add active company and capability-aware filtering.
3. Add extension menu contributors.
4. Add owner-aware menu upserts.
5. Add WorkCore administrator keys to permission mapping.
6. Correct dropdown wildcard active-state handling.

## High

7. Add cache revision tokens.
8. Separate vendor default order from administrator custom order.
9. Add extension ownership to menu records.
10. Add user-dashboard widget contributors.
11. Add field-worker mobile navigation.
12. Add menu integrity tests.
13. Add route-existence validation for contributed menus.
14. Add duplicate key and route collision validation.

## Medium

15. Standardise the `extension` field type.
16. Add a parent foreign key or integrity verifier.
17. Replace broad cache clears with revisioned keys.
18. Make the floating create menu contributor-driven.
19. Make the bottom menu role/profile driven.
20. Add a navigation preview for each role and plan.

---

# 26. Required tests

## Menu structure

- Every WorkCore key is unique.
- Every parent key exists.
- Every named route exists.
- Every route family has an active pattern.
- No WorkCore key collides with MagicAI or CRM.
- Removed definitions are disabled safely.

## Visibility

- A user without WorkCore entitlement sees no WorkCore roots.
- A user with entitlement but no company membership sees setup/onboarding only.
- A dispatcher sees dispatch.
- A worker does not see payroll or company administration.
- A suspended member sees no operational data.
- Switching active company changes visible modules.
- Capability changes invalidate navigation.

## Administration

- Super administrator sees all WorkCore settings.
- Restricted administrator sees only permitted WorkCore areas.
- Menu permission patterns cover nested routes.
- Hidden menu entries do not replace server-side permission checks.

## Caching

- Two users on the same plan but different roles receive different menus.
- Two companies with different modules receive different menus.
- Menu changes propagate without clearing unrelated application cache.
- Theme changes do not alter authorisation.
- Company switching does not serve the previous company menu.

---

# 27. Decisions established by Scan 04

1. WorkCore will be a top-level navigation peer of CRM and Sales.
2. WorkCore will use Operations, Workforce, Resources and Commercial roots.
3. Field-worker navigation will be separate from manager navigation.
4. WorkCore menus will not be hard-coded directly into the central catalogue long term.
5. A generic menu-contributor contract should be added.
6. Menu definitions require extension ownership and versioning.
7. Plan entitlements and operational capabilities remain separate.
8. WorkCore menu caches must include active-company and capability revisions.
9. User-specific conditions must not enter a global forever cache.
10. WorkCore administrator menu keys must align with permissions and route patterns.
11. Titan Flow should become the manager operational home.
12. The static user dashboard should gain a widget-contributor system.
13. The existing administrator widgets remain useful for platform health, not business operations.
14. Mobile bottom navigation must be rebuilt for field work.
15. Floating creation actions must be capability-driven.
16. Every contributed route, key, parent and permission requires automated validation.

---

# 28. Next scan

The next report should be:

```text
05-magicai-authentication-and-user-identity.md
```

It should map:

- Login and registration
- Password reset
- Email verification
- OTP
- Two-factor authentication
- Social authentication
- Session behaviour
- Passport and Sanctum
- API token lifecycle
- User status and deletion
- Impersonation
- Team-manager identity inheritance
- Administrator and super-administrator identity
- WorkCore actor mapping
- Worker-to-user linking
- Customer-versus-user separation
- Device identity
- Step-up authentication for consequential actions

---

# Appendix A — Primary evidence files

```text
app/Services/Common/MenuService.php
app/Models/Common/Menu.php
database/seeders/MenuSeeder.php
database/migrations/2024_05_16_092520_create_menus_table.php
database/migrations/2024_06_26_074715_add_custom_menu_to_menus_table.php
database/migrations/2024_09_19_070942_add_bolt_menu_to_menus_table.php
database/migrations/2025_09_26_131447_add_indexes_to_menus_table.php
database/migrations/2026_02_23_093522_add_badge_to_menus_table.php
app/Helpers/Classes/MenuHelper.php
app/Helpers/Classes/PlanHelper.php
app/Http/Middleware/AdminPermissionMiddleware.php
app/Http/Middleware/CheckTemplateTypeAndPlan.php
app/Caches/BladeCache.php
resources/views/default/panel/layout/partials/menu.blade.php
resources/views/default/components/navbar/
resources/views/default/components/bottom-menu.blade.php
resources/views/default/components/floating-menu.blade.php
app/Models/DashboardWidget.php
app/Enums/DashboardWidget.php
app/Services/Dashboard/DashboardService.php
database/seeders/DashboardWidgetSeeder.php
resources/views/default/panel/admin/dashboard/
resources/views/default/panel/user/dashboard.blade.php
```

---

# Appendix B — Evidence limitations

This scan statically mapped the supplied default theme and source package.

It does not yet prove:

- How every separately supplied theme renders all menu types
- Runtime menu state with the CRM extension installed
- Runtime menu state with AI Agent installed
- Database contents from an upgraded production instance
- Behaviour under Redis or distributed cache
- Behaviour under Laravel Octane with long-lived workers
- Accessibility quality across every menu component
- Browser behaviour on all supported mobile devices

Those areas require installed extension packages and dynamic testing.
