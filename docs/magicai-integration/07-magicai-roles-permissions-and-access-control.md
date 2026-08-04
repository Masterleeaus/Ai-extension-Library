---
title: "MagicAI 11 Roles, Permissions and Access Control"
scan_number: 7
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/07-magicai-roles-permissions-and-access-control.md"
---

# Scan 07 — MagicAI 11 Roles, Permissions and Access Control

## Executive conclusion

MagicAI and WorkCore have fundamentally different access-control responsibilities.

MagicAI provides:

- Three global platform roles
- Twenty-five platform-administration permissions
- Subscription-plan feature entitlements
- Extension visibility
- Menu-derived administrator route access
- Basic administrator and super-administrator bypasses

WorkCore provides:

- Company-scoped memberships
- Company roles
- Role permissions
- Per-member overrides
- Record-level access levels
- Capability entitlements
- Governed business actions
- Confirmation requirements
- AI-tool permissions
- Approval records
- Audit, idempotency and domain events

The correct integrated model is:

> **MagicAI role controls platform administration.**  
> **MagicAI plan controls purchased features.**  
> **WorkCore company membership controls tenant entry.**  
> **WorkCore capability controls whether a module exists for the company.**  
> **WorkCore permission controls what the actor may do.**  
> **Record access controls which records the actor may see or change.**  
> **Approval policy controls whether the permitted action may execute now.**

No single layer can replace the others.

## Critical findings

### MagicAI

1. MagicAI has only one effective delegated administrator role.
2. Every user whose `type` is `admin` shares the same global administrator permission set.
3. Spatie roles are not visibly assigned to individual users; the `users.type` enum decides whether someone is an administrator.
4. The administrator role is seeded with every platform permission.
5. A delegated administrator with User Management access can reach the page that edits the global administrator permission set.
6. That administrator can therefore change the permissions granted to all administrators, including itself.
7. Administrator route access is derived from menu keys and active-route patterns rather than policies on the underlying records.
8. MagicAI defines only one policy class, and it is empty.
9. The policy mapping points to a generic `App\Models\Model` placeholder rather than real application models.
10. No broad model-level ownership policy system exists.
11. Most ownership checks are therefore implemented manually and inconsistently in controllers.
12. Super administrators bypass all administrator permission checks.
13. All administrators bypass subscription-plan checks.
14. Extension admin pages are inaccessible to delegated administrators unless their menu keys also exist as Spatie permissions.
15. Permission and menu caches can temporarily disagree with changed role permissions.
16. MagicAI's Spatie `teams` mode is disabled, so its permissions are installation-global rather than tenant-scoped.

### WorkCore

17. WorkCore's configured default permission resolver references a non-existent `WorkCoreAccessLevel::Manage` enum case.
18. The safer `MagicAIPermissionResolver` exists but is not the configured default in the supplied package.
19. WorkCore module routes are configured with `auth:sanctum` in 22 locations, while MagicAI uses Passport through the `api` guard.
20. The capability middleware checks company entitlement only; it does not check actor permission.
21. Read endpoints that do not pass through the business-action dispatcher require their own permission enforcement.
22. The generic business-action confirmation verifier accepts any non-empty confirmation ID.
23. It does not verify company, actor, action key, payload hash, approval status, expiry or prior consumption.
24. WorkCore's separate AI approval store does implement expiry and one-time consumption, but that stronger verification is not the generic dispatcher verifier.
25. Company owners receive unconditional `All` access in the MagicAI permission adapter.
26. That owner bypass may violate separation-of-duties requirements for payroll, restricted incidents, medical data or financial approvals.
27. Platform permission fallback is supported, but correctly defaults to disabled.
28. The WorkCore permission system is structurally stronger than MagicAI's, but the integration must standardise guards, resolvers, confirmation verification and read-side enforcement.

---

# 1. MagicAI platform roles

MagicAI declares three roles:

```text
user
admin
super_admin
```

The user's `type` field is cast to the role enum.

Platform checks use:

```php
$user->isUser()
$user->isAdmin()
$user->isSuperAdmin()
$user->checkPermission($key)
```

`isAdmin()` returns true for both:

```text
admin
super_admin
```

## Meaning

These roles describe the relationship to the MagicAI installation:

- `user`: subscriber or ordinary platform user
- `admin`: delegated platform administrator
- `super_admin`: unrestricted platform administrator

They are not WorkCore business roles.

---

# 2. MagicAI platform permissions

MagicAI defines 25 administrator permission keys:

```text
marketplace
themes
user_management
announcements
google_adsense
support_requests
templates
chat_settings
frontend
finance
pages
blog
affiliates_admin
coupons_admin
email_templates
introductions
mailchimp_newsletter
hubspot
api_integration
settings
site_health
license
update
menu_setting
VIP_CHAT_WIDGET
```

These permissions are platform-management categories.

They do not cover operational actions such as:

```text
assign worker
approve timesheet
view payroll
create work order
record incident
issue purchase order
view restricted compliance record
```

Those belong in WorkCore.

---

# 3. Spatie role architecture

MagicAI uses Spatie Permission with:

```text
teams = false
wildcard permissions = false
permission cache = 24 hours
guard = web
```

Tables include:

```text
roles
permissions
model_has_roles
model_has_permissions
role_has_permissions
```

The user model uses `HasRoles`.

However, no active application code was found assigning Spatie roles to individual users through:

```text
assignRole
syncRoles
givePermissionTo
```

Instead:

- `users.type` decides whether a user is an admin.
- The single Spatie role named `admin` stores the permissions shared by all admins.
- Super-admin access is implemented by direct bypass.

This makes Spatie a **global admin-permission catalogue**, not a true per-user RBAC layer.

---

# 4. Default administrator authority

The role seeder creates:

```text
user
admin
super_admin
```

The permission seeder creates all 25 platform permissions.

`AdminPermissionSeeder` then grants every permission to the `admin` role:

```php
$role->syncPermissions(Permission::all());
```

Therefore delegated administrators begin with full access to every platform administration category unless the super administrator later removes permissions.

---

# 5. Administrator middleware

All routes under:

```text
dashboard/admin/*
```

use:

```text
admin
```

which maps to `AdminPermissionMiddleware`.

The middleware:

1. Allows super administrators immediately.
2. Rejects non-administrators.
3. Loads the global Spatie role named `admin`.
4. Gets that role's permissions.
5. Adds `admin_dashboard`.
6. Generates the MagicAI menu.
7. Finds menu definitions marked `is_admin`.
8. Converts permitted menu keys into route patterns.
9. Allows the request if the current route name matches one of those patterns.

## Consequence

Administrator access depends on:

```text
global admin role permission
+
menu key
+
active route pattern
+
menu cache state
```

There is no model policy check at this layer.

---

# 6. Global administrator-permission escalation

The administrator permission editor is located under:

```text
dashboard.admin.users.permissions
dashboard.admin.users.permissionSave
```

Those routes are covered by the User Management menu pattern:

```text
dashboard.admin.users.*
```

An administrator who already has:

```text
user_management
```

can reach the permission editor.

The save method updates the global role:

```php
Role::findByName('admin')->syncPermissions($submittedPermissions);
```

This changes permissions for every delegated administrator.

## Risk

A delegated administrator with User Management access can potentially:

- Add Marketplace access
- Add Finance access
- Add Settings access
- Add Licence access
- Add Update access
- Add any other registered administrator permission

It also changes the permissions of other administrators.

## Required correction

Only a super administrator should manage delegated administrator roles.

Recommended architecture:

```text
platform administrator roles
platform administrator memberships
per-user role assignment
immutable super-admin role
role-management permission restricted to super-admin
audit and step-up authentication
```

Do not let an ordinary delegated administrator edit the role that authorises its own request.

---

# 7. Menu-derived authorisation

Menu-derived access provides one useful property:

- Navigation and route permission can share the same key.

But it also creates coupling:

- Hiding a menu can alter route access.
- A missing active pattern can deny a legitimate route.
- A broad wildcard can grant too much.
- An extension menu key must also be seeded as a permission.
- Stale menu cache can produce stale access.
- A record can still be accessed incorrectly if its controller lacks ownership checks.

## WorkCore rule

Menus may reflect authority, but they must not be the source of operational authority.

WorkCore actions must enforce permission independently of navigation.

---

# 8. Policies and gates

MagicAI's `AuthServiceProvider` maps:

```text
App\Models\Model
→ App\Policies\ModelPolicy
```

`ModelPolicy` contains no authorisation methods.

No application-wide model policy catalogue was found.

The only clear Gate definition outside package permission integration is for Telescope access.

## Consequence

MagicAI generally relies on:

- Route middleware
- Controller checks
- User ownership filters
- Menu/permission mapping
- Plan checks

rather than model policies.

Scans 05 and 06 already found cross-user access defects caused by missing ownership checks.

## Required correction

Introduce policies for security-sensitive host models, including:

```text
User
Team
TeamMember
Company/Brand Profile
Product
Extension
Plan
Support Ticket
User Integration
```

WorkCore should continue using its own authorisers and permission resolver rather than depending on MagicAI model policies.

---

# 9. Form-request authorisation

Twelve MagicAI Form Request classes were found.

Static inspection showed:

- Two explicitly return true.
- Two contain custom authorisation logic.
- The remainder do not add meaningful model-level authorisation.

Form Requests primarily validate input.

They do not replace policies or tenant-aware record lookup.

---

# 10. Subscription entitlements

`CheckTemplateTypeAndPlan` controls premium feature access.

It:

- Allows every administrator immediately.
- Uses the team manager's plan for team members.
- Maps exact routes to plan slugs.
- Maps route prefixes to plan slugs.
- Checks plan features and AI tools.
- Redirects users to upgrade when denied.

CRM route mappings include:

```text
dashboard.user.crm.*   → ext_crm_dropdown
dashboard.user.sales.* → ext_sales_dropdown
```

Recommended WorkCore mappings:

```text
dashboard.user.operations.* → ext_workcore_operations
dashboard.user.workforce.*  → ext_workcore_workforce
dashboard.user.resources.*  → ext_workcore_resources
dashboard.user.commercial.* → ext_workcore_commercial
```

## Important distinction

Plan entitlement means:

> The subscriber purchased the module.

It does not mean:

> This actor may access every record or execute every action in the module.

---

# 11. MagicAI access-control layers

The current MagicAI layers are:

```text
Authentication
→ platform role
→ administrator permission or plan entitlement
→ manual controller ownership checks
```

Missing or inconsistent layers include:

```text
tenant membership
record-level permission
branch/territory scope
approval authority
separation of duties
action risk
idempotency
durable audit
```

WorkCore supplies those missing layers.

---

# 12. WorkCore company roles

WorkCore creates default company roles:

```text
Owner
Manager
Dispatcher
Field Worker
Bookkeeper
Member
```

Roles are company-scoped.

Memberships contain:

```text
company_id
user_id
role_id
role_key
status
is_owner
joined_at
```

A user can have different roles in different companies.

This is the correct operational model.

---

# 13. WorkCore permission storage

Role permissions are stored in:

```text
tz_company_role_permissions
```

Member overrides are stored in:

```text
tz_company_member_permissions
```

Both include:

```text
company_id
permission
access_level
```

Uniqueness constraints prevent duplicate permission entries for the same role or membership.

## Resolution order

The MagicAI-compatible resolver applies:

```text
active membership required
→ owner bypass
→ member override
→ role permission
→ optional platform fallback
→ deny
```

This is a strong deny-by-default design.

---

# 14. WorkCore access levels

WorkCore defines:

```text
all
added
owned
both
none
```

Meaning:

- `all`: all company-scoped records
- `added`: records created by the actor
- `owned`: records assigned to or owned by the actor
- `both`: created or owned records
- `none`: no record access

`CompanyRecordAuthorizer` narrows already company-scoped queries according to these levels.

## Required caution

The actor ID and owner columns must represent the same identity type.

Do not compare a MagicAI user ID against a `worker_id` column without first resolving the linked worker.

---

# 15. Broken default WorkCore permission resolver

The WorkCore config defaults to:

```text
App\Support\WorkCore\WorkCorePermissionResolver
```

That resolver returns:

```php
WorkCoreAccessLevel::Manage
```

for owners and admin-like members.

The actual enum contains no `Manage` case.

Valid cases are:

```text
All
Added
Owned
Both
None
```

## Consequence

The default owner/admin permission path will fail at runtime when executed.

## Required correction

Either:

- Replace `Manage` with `All`, or
- Add a deliberately designed `Manage` level and update every authoriser.

For MagicAI integration, bind the tested:

```text
MagicAIPermissionResolver
```

after fixing its owner-bypass policy.

Add a boot-time assertion that the configured resolver returns only defined access levels.

---

# 16. Platform fallback

WorkCore can optionally use MagicAI/Spatie permissions when no company permission is found.

The setting is:

```text
WORKCORE_ALLOW_PLATFORM_PERMISSION_FALLBACK
```

Default:

```text
false
```

This default is correct.

Turning it on could allow a global platform permission to grant access inside every WorkCore company.

## Rule

Keep platform fallback disabled.

Create explicit, audited support-access workflows instead of global fallback.

---

# 17. Capabilities versus permissions

WorkCore capabilities describe installed or enabled company functionality.

Examples include:

```text
workcore.core
workcore.operations
workcore.scheduling
workcore.workforce
workcore.inventory
workcore.crm
workcore.finance
workcore.ai
```

The capability registry rejects duplicate registrations.

The capability middleware checks:

```text
active company exists
company is entitled to capability
```

It does not check actor permission.

## Correct route chain

```text
authentication
→ tenant
→ capability entitlement
→ actor permission
→ record access
→ approval policy
```

A route using only `workcore.capability` is not fully authorised.

---

# 18. Guard mismatch

WorkCore route configuration contains 22 uses of:

```text
auth:sanctum
```

MagicAI's active API guard is:

```text
auth:api
```

using Passport.

No `auth:api` occurrences were found in the supplied WorkCore domain configuration.

## Consequence

WorkCore routes may:

- Reject valid MagicAI Passport users
- Accept a different token mechanism than intended
- Produce inconsistent actor identity
- Bypass expected MFA/token controls
- Fail when moved into MagicAI

## Required correction

Make the host guard configurable:

```text
WORKCORE_API_AUTH_MIDDLEWARE=auth:api
```

The MagicAI extension package should default to Passport.

Do not hard-code Sanctum in module manifests.

---

# 19. Governed business actions

`BusinessActionDispatcher` enforces:

```text
registered action
→ active tenant and operation context match
→ company capability entitlement
→ actor permission
→ confirmation requirement
→ idempotency replay/reservation
→ transactional handler
→ domain events
→ audit record
→ idempotency completion
```

This is substantially stronger than MagicAI controller-level authorisation.

It should become the required execution path for consequential WorkCore writes, regardless of whether the caller is:

- Browser UI
- API
- AI Agent
- Chatbot
- Voice agent
- Scheduler
- Integration
- Offline sync

---

# 20. Confirmation bypass defect

`ExplicitConfirmationVerifier` determines that confirmation is required when:

```text
requiresConfirmation = true
or risk is high/critical
```

It then accepts the action when:

```text
confirmationId is any non-empty string
```

It does not verify:

- Approval exists
- Approval belongs to company
- Approval belongs to actor
- Approval matches action key
- Approval matches payload
- Approval is approved
- Approval is unexpired
- Approval is unused
- Approver had authority
- Request is the same request that was reviewed

## Severity

**Critical**

## Required verifier

The confirmation record must bind:

```text
company_id
requesting_actor_id
approving_actor_id
action_key
payload_hash
risk
status
expires_at
consumed_at
correlation_id
```

Verification must lock and consume the record atomically.

An arbitrary confirmation string must never satisfy the dispatcher.

---

# 21. AI approval system

WorkCore's AI approval store is stronger than the generic confirmation verifier.

It supports:

- Company-scoped approval records
- Pending/approved/rejected/expired state
- Expiration
- Row locking
- Approver identity
- Decision note
- One-time consumption
- Tool input snapshot
- Risk classification

`ApprovalService` also requires:

```text
WorkCore AI capability
AI tool permission
AI approval permission
```

## Integration requirement

Use the verified AI approval record as one implementation of the central confirmation contract.

The action dispatcher must validate and consume it, not merely accept its public ID.

---

# 22. AI permissions

WorkCore separates:

```text
workcore.ai.use
workcore.ai.execute_tools
workcore.ai.approve_actions
workcore.ai.view_conversations
workcore.ai.manage_memory
workcore.ai.manage_company_memory
```

`NativeAiAccessGate` checks:

- Tenant context
- Operation context
- Company AI capability
- AI use permission
- AI tool permission

This is the correct model.

MagicAI's AI Agent tools should call this gate before exposing WorkCore tools.

---

# 23. Owner bypass and separation of duties

`MagicAIPermissionResolver` grants company owners:

```text
All
```

before checking member overrides or role permissions.

This means an owner cannot be denied a specific permission.

That may be acceptable for ordinary small-business administration.

It may be unacceptable for:

- Payroll privacy
- Restricted medical information
- Sensitive incident records
- Whistleblower reports
- Financial approval limits
- Dual-control payments
- NDIS restricted notes
- External property-owner approvals

## Recommended design

Support a permission classification:

```text
ordinary
sensitive
regulated
dual_control
platform_only
```

Owner bypass can apply to ordinary permissions.

Sensitive and dual-control permissions should still require explicit grants or independent approval.

---

# 24. Read-side authorisation

The business-action dispatcher protects writes that use it.

Read models and list endpoints may not use the dispatcher.

They require:

- Company scope
- Permission check
- Record-access-level filter
- Branch/territory scope
- Sensitive-field projection
- Audit where legally necessary

## Required read gateway

Create a standard read-authorisation service:

```text
WorkCoreReadAuthorizer
```

It should return:

```text
permission decision
access level
allowed branches
allowed territories
field-redaction policy
```

Every read model should declare its required permission.

---

# 25. Approval authority

WorkCore contains several approval systems:

- AI action approvals
- Finance approval requirements
- Budget approvals
- Property and premise approvals
- Work permits
- Supply adjustment approvals
- Timesheet and leave approvals

These need a common approval policy foundation.

## Required dimensions

```text
requester cannot self-approve where prohibited
minimum approver role
permission
value threshold
risk threshold
company scope
branch scope
approval count
expiry
substitution/delegation
one-time consumption
audit
```

Avoid separate approval semantics drifting between modules.

---

# 26. Support and impersonation

No safe WorkCore support-impersonation architecture was found in the inspected access layer.

A platform super administrator should not silently inherit company authority.

Recommended support flow:

```text
support request or documented reason
→ step-up authentication
→ select company
→ time-limited support grant
→ restricted capabilities
→ visible support banner
→ all actions attributed to support actor
→ customer-access notification where appropriate
→ automatic expiry
```

Never use platform-permission fallback as a substitute.

---

# 27. Recommended integrated access stack

```text
1. MagicAI authentication
2. Platform account active
3. Authentication assurance/MFA
4. MagicAI platform role
5. MagicAI plan/module entitlement
6. WorkCore actor subject
7. Active WorkCore company
8. Active company membership
9. WorkCore capability
10. WorkCore permission
11. Record access level
12. Branch/territory restriction
13. Sensitive-field policy
14. Approval/confirmation policy
15. Governed action or read gateway
16. Audit, event and idempotency
```

Every denial should be fail-closed.

---

# 28. Recommended permission namespaces

## Platform permissions

Keep a small MagicAI platform namespace:

```text
platform.workcore.install
platform.workcore.configure
platform.workcore.health
platform.workcore.support_access
platform.workcore.purge
```

## Company permissions

Use WorkCore namespaces such as:

```text
business.work_orders.view
business.work_orders.create
business.dispatch.assign
business.workers.manage
business.timesheets.approve
business.compliance.incidents.manage
business.inventory.adjust
business.finance.view
workcore.ai.execute_tools
workcore.ai.approve_actions
```

Do not seed hundreds of company permissions into MagicAI's global admin role.

---

# 29. Required MagicAI fixes

## Critical

1. Restrict global administrator-role editing to super administrators.
2. Add per-user delegated administrator role assignment.
3. Add real model policies for Team and Brand Voice records.
4. Remove reliance on menu visibility as the only record-authorisation boundary.
5. Ensure extension administration routes receive explicit platform permissions.
6. Fix previously identified cross-user controller access.

## High

7. Separate platform role from Spatie role drift.
8. Validate permission submissions against an allowlist.
9. Audit administrator permission changes.
10. Require step-up authentication for privileged changes.
11. Add permission revision and cache invalidation.
12. Add policy tests for every sensitive host model.
13. Add extension permission-contributor support.

---

# 30. Required WorkCore fixes

## Critical

1. Fix the undefined `WorkCoreAccessLevel::Manage` reference.
2. Bind the correct permission resolver in the MagicAI extension.
3. Replace hard-coded `auth:sanctum` with configurable Passport middleware.
4. Replace arbitrary-string confirmation verification.
5. Bind confirmation to company, actor, action and payload.
6. Consume confirmation atomically.
7. Add permission checks to every read endpoint.
8. Prevent capability middleware from being mistaken for actor authorisation.

## High

9. Add owner-bypass exceptions for sensitive permissions.
10. Add branch and territory filters to read authorisation.
11. Standardise approval rules across modules.
12. Add permission and capability revision IDs.
13. Cache permission decisions only with company and revision keys.
14. Add support-access grants rather than platform fallback.
15. Add route metadata declaring capability, permission and access-level strategy.

---

# 31. Required tests

## MagicAI administration

- Ordinary user cannot access admin routes.
- Admin sees only permitted platform areas.
- Super admin bypass works.
- Delegated admin cannot change its own global authority.
- Admin permission changes invalidate all relevant caches.
- Missing menu does not accidentally grant route access.
- Broad menu wildcard does not expose unrelated routes.
- Extension permission is unavailable until explicitly registered.

## WorkCore permissions

- No membership returns `None`.
- Inactive membership returns `None`.
- Member override beats role permission.
- Explicit member `none` denies access.
- Role permission applies within one company only.
- Permission cannot cross company boundaries.
- Platform fallback remains disabled.
- Broken enum cases are caught during boot.
- Owner sensitive-access policy behaves as configured.

## Record access

- `all`, `added`, `owned`, `both` and `none` produce correct queries.
- Worker-owned records compare against worker identity, not user ID.
- Branch-limited user cannot read another branch.
- Territory-limited user cannot read another territory.
- Restricted fields are redacted.

## Actions and approvals

- Capability without permission is denied.
- Permission without capability is denied.
- High-risk action without approval is denied.
- Random confirmation ID is denied.
- Approval for another company is denied.
- Approval for another actor is denied.
- Approval for another action or payload is denied.
- Expired approval is denied.
- Consumed approval cannot be replayed.
- Requester cannot self-approve when separation of duties applies.
- Idempotency and approval work together correctly.

## AI tools

- AI model use and AI tool execution are separate permissions.
- Read-only AI user cannot execute tools.
- Tool approver cannot execute unrelated actions.
- Critical AI tools remain blocked.
- High-risk AI tools pause for verified approval.
- Agent never inherits global administrator authority.

---

# 32. Decisions established by Scan 07

1. MagicAI controls platform roles only.
2. WorkCore controls company operational roles.
3. Plan entitlement and permission remain separate.
4. Capability and permission remain separate.
5. Menus reflect access but do not establish operational authority.
6. WorkCore writes use the governed action dispatcher.
7. WorkCore reads use a standard read-authorisation gateway.
8. The default WorkCore permission resolver must be repaired.
9. MagicAI integration uses Passport-compatible middleware.
10. Arbitrary confirmation strings are prohibited.
11. AI approvals become verified central confirmations.
12. Owner bypass is limited for sensitive and dual-control permissions.
13. Platform fallback remains disabled.
14. Support access uses explicit time-limited grants.
15. Every permission decision is company-scoped and fail-closed.

---

# 33. Next scan

```text
08-magicai-plans-subscriptions-credits-and-billing.md
```

It should map:

- Plan types
- Subscription lifecycle
- Team plans and seat limits
- Plan features
- AI model entitlements
- Shared credits
- Usage limits
- Trials
- Upgrades and downgrades
- Payment gateways
- Orders and invoices
- Taxes and coupons
- Cancellation and failed payment
- WorkCore module entitlements
- Workforce-seat rules
- AI action charging
- Titan plan mapping
- Separation between SaaS billing and operational finance

---

# Evidence files

## MagicAI

```text
app/Enums/Roles.php
app/Enums/Permissions.php
app/Models/User.php
app/SpatiePermissionConfig.php
app/Providers/AuthServiceProvider.php
app/Policies/ModelPolicy.php
app/Http/Middleware/AdminPermissionMiddleware.php
app/Http/Middleware/CheckTemplateTypeAndPlan.php
app/Helpers/Classes/Helper.php
app/Services/Common/MenuService.php
database/seeders/RoleSeeder.php
database/seeders/PermissionSeeder.php
database/seeders/AdminPermissionSeeder.php
database/migrations/2024_11_04_100122_create_permission_tables.php
app/Http/Controllers/Dashboard/AdminController.php
routes/panel.php
```

## WorkCore

```text
app/Domains/WorkCore/Config/workcore.php
app/Support/WorkCore/WorkCorePermissionResolver.php
app/Domains/WorkCore/System/Permissions/MagicAIPermissionResolver.php
app/Domains/WorkCore/System/Permissions/DenyByDefaultPermissionResolver.php
app/Domains/WorkCore/System/Authorization/WorkCoreAccessLevel.php
app/Domains/WorkCore/System/Authorization/CompanyRecordAuthorizer.php
app/Domains/WorkCore/System/Capabilities/CapabilityRegistry.php
app/Domains/WorkCore/System/Capabilities/Middleware/RequireWorkCoreCapability.php
app/Domains/WorkCore/System/Actions/BusinessActionDispatcher.php
app/Domains/WorkCore/System/Actions/Policies/ExplicitConfirmationVerifier.php
app/Domains/WorkCore/System/AI/Security/NativeAiAccessGate.php
app/Domains/WorkCore/System/AI/Orchestration/ApprovalService.php
app/Domains/WorkCore/System/AI/Orchestration/Persistence/DatabaseApprovalStore.php
app/Domains/WorkCore/System/Models/CompanyRole.php
app/Domains/WorkCore/System/Models/CompanyRolePermission.php
app/Domains/WorkCore/System/Models/CompanyMemberPermission.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120004_create_tz_company_permissions_tables.php
```

---

# Evidence limitations

This was a static source scan.

It did not execute:

- Spatie permission cache behaviour
- Administrator permission escalation in a browser
- WorkCore permission resolver owner path
- Passport versus Sanctum routes
- Approval replay attempts
- Branch/territory read filters
- CRM extension permissions
- AI Agent extension permissions

The identified defects are directly supported by the inspected source and should be covered by runtime regression tests after correction.
