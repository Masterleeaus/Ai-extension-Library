---
title: "MagicAI 11 Teams, Companies and Tenancy"
scan_number: 6
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/06-magicai-teams-companies-and-tenancy.md"
---

# Scan 06 — MagicAI 11 Teams, Companies and Tenancy

## Executive conclusion

MagicAI 11 does not contain a complete business-tenancy system.

It contains two separate concepts:

1. **MagicAI Team** — a subscription-seat, shared-plan and AI-credit group owned by one user.
2. **MagicAI Company** — a user-owned Brand Voice profile containing company description, audience, products and AI instructions.

Neither concept can safely replace WorkCore's operational company model.

> **MagicAI Team = SaaS seat and credit-sharing group**  
> **MagicAI Company = Brand Voice and marketing context**  
> **WorkCore Company = operational tenant and records authority**

WorkCore already supplies the stronger tenant architecture: companies, memberships, roles, permission overrides, invitations, active-company switching, branches, districts, regions, territories, company settings, vertical configuration, company-scoped records and tenant-aware action context.

## Critical findings

### MagicAI

1. Team routes use unscoped route-model binding.
2. Team member view and update actions do not verify that the authenticated user owns the team.
3. Team member deletion checks ownership of the route team but not whether the member belongs to that team.
4. A team owner can therefore combine their own team ID with a member ID from another team.
5. Invitation creation accepts a submitted `team_id` that can differ from the route-bound team.
6. Invitation logic does not verify ownership of the destination team.
7. The `invitations` route references a controller method that does not exist.
8. Member deletion clears `team_id` and a non-existent `team_member_id` field but does not clear `team_manager_id`.
9. Dashboard Brand Voice edit, update, delete and product routes do not enforce ownership of the route-bound company.
10. The Brand Voice API update path loads a company by ID without user scope and then assigns it to the current user, enabling record takeover.
11. AI generation code accepts arbitrary company and product IDs without verifying ownership.
12. MagicAI has no global tenant scope; ownership checks are manually repeated and inconsistently applied.
13. Team content scoping exists only on selected tables.
14. Team and content `team_id` columns often lack foreign-key enforcement.
15. A MagicAI user can effectively belong to only one team.
16. Team roles are free text and do not provide operational permissions.
17. Team plan inheritance is implemented inconsistently across helper paths.

### WorkCore

18. WorkCore company and permission structures are substantially stronger.
19. WorkCore's `BelongsToCompany` global scope applies only when tenant context exists.
20. When tenant context is missing, Eloquent queries can return records from all companies.
21. Direct query-builder calls bypass Eloquent global scopes entirely.
22. Current WorkCore membership and worker records still reference MagicAI users directly.
23. Company deletion cascades through a very large operational schema and must never be an ordinary user action.
24. The `active_company_id` host column exists in the standalone WorkCore integration migration but must be added safely to MagicAI.
25. WorkCore needs branch and territory scopes, not merely company membership.

## Final architectural decision

```text
MagicAI Team
    → SaaS plan owner and seat entitlement

MagicAI Brand Company
    → Brand Voice and sales/marketing context

WorkCore Company
    → Authoritative operational tenant
```

# 1. MagicAI Team model

The Team model contains:

```text
user_id
name
allow_seats
used_image_credit
word_credit
entity_credits
credit_system_type
shared_credits
```

The owner is identified through `user_id`. Team is primarily a billing, seat and credit object. It does not contain operational membership roles, branches, workers, customers, work orders, compliance settings, territories or data-retention policy.

# 2. TeamMember model

A TeamMember contains:

```text
team_id
user_id nullable
role
email
status
allow_unlimited_credits
daily_shared_credit_limit
remaining_images
remaining_words
used_image_credit
used_word_credit
joined_at
```

Observed statuses are `waiting`, `active` and `cancelled`. The role is a free-text string. There is no role table, permission table, member override table, branch scope, territory scope or capability resolver.

MagicAI TeamMember therefore cannot represent WorkCore roles such as dispatcher, supervisor, field worker, auditor, compliance manager or bookkeeper.

# 3. Team schema and one-team limitation

The `teams` table belongs to one owner user. The `team_members` table belongs to one team and optionally one user. MagicAI also adds these fields directly to `users`:

```text
team_id
team_manager_id
```

The inspected migration does not define foreign keys for those user columns.

Team IDs were also added to:

```text
user_openai
user_openai_chat
folders
```

These are plain numeric fields without confirmed foreign keys.

Invitation validation requires:

```text
unique:team_members,email
unique:users,email
```

Consequences:

- An existing MagicAI user cannot normally be invited by email.
- One email cannot appear in multiple team-member records.
- A user cannot naturally belong to several MagicAI teams.
- MagicAI teams cannot represent WorkCore multi-company membership.

# 4. Team creation, plans and credits

A team is created lazily when its owner opens the Team page. Seat allowance is copied from `plan_allow_seat`; administrators can receive 100 seats. Team availability is controlled by:

```text
settings.team_functionality
plan.is_team_plan
plan.plan_allow_seat
```

This confirms Team is a SaaS-plan feature.

MagicAI permits team members to inherit their manager's plan in `CheckTemplateTypeAndPlan` and `MenuHelper`. However, `PlanHelper::userPlan()` and other direct `relationPlan` reads do not consistently resolve the manager's plan.

A single `EffectiveSubscriptionResolver` should return the subscriber, effective plan, team source, entitlement revision and credit source. WorkCore should consume this only for subscription entitlement, never company authority.

MagicAI supports separated and shared credits. Shared-credit deduction uses transactions and row locking, which is positive. However, credit transactions are primarily user-scoped; team identity is often metadata; daily limits are not clearly partitioned by team; and the service itself does not prove that a supplied team belongs to the user.

# 5. Team-owned content

MagicAI attaches `team_id` to selected AI documents, chats and folders. The dashboard can show current-user records plus records associated with a shared or owned team.

This is selective content sharing, not universal tenancy. At least 21 core models explicitly include `user_id`, while only a small subset include `team_id`. MagicAI's dominant isolation model is individual user ownership.

# 6. Team route-security findings

Routes include:

```text
GET  team
GET  team/{team}/invitations
POST team/{team}/invitation
GET  team/{team}/member/{teamMember}/edit
POST team/{team}/member/{teamMember}/update
GET  team/{team}/member/{teamMember}/delete
```

They are authenticated but do not use scoped bindings.

## Missing invitations method

The route points to `TeamController::invitations`, but no such method exists.

## Member view and update

`teamMember()` and `teamMemberUpdate()` accept route-bound Team and TeamMember records without verifying that the current user owns the team or that `teamMember.team_id` equals `team.id`.

## Cross-team deletion

Deletion checks only:

```php
auth()->id() === $team->user_id
```

It does not check the child belongs to the route team. An owner can therefore supply their own team ID and another team's member ID.

Deletion also updates a non-existent `team_member_id` field and fails to clear `team_manager_id`, leaving stale linkage.

## Required repair

- Use scoped route binding.
- Add Team and TeamMember policies.
- Verify parent-child relationship explicitly.
- Use `DELETE`, not GET, for deletion.
- Clear `team_id` and `team_manager_id` correctly.
- Add cross-team regression tests.

# 7. Team invitation-security findings

Invitation creation receives both a route Team and a client-supplied `team_id`. The seat rule reads the route Team, while record creation uses the submitted ID. The values can differ.

The controller checks that the current user's subscription is a team plan but does not verify that the user owns either the route team or submitted destination team.

Required repair:

- Remove `team_id` from client input.
- Derive it from an authorised route Team.
- Require owner/team-admin policy.
- Add hashed invitation token, expiry and one-time acceptance.
- Bind invitation to email.
- Support existing users safely.

WorkCore's invitation implementation already uses token hashing, expiry, email binding, row locking, a database transaction and one-time status transition.

# 8. MagicAI Company is a Brand Voice profile

The MagicAI Company model contains:

```text
name
industry
description
website
tagline
logo
brand_color
tone_of_voice
target_audience
specific_instructions
user_id
```

It has Products containing name, type, description, features, owner user and company ID.

This is designed for Brand Voice and AI marketing prompts. It is not an operational tenant. Integration code should refer to it as a `MagicAIBrandProfile` even if the database table remains `companies`.

# 9. Critical Brand Voice web IDOR

The web controller lists only current-user companies, but route-bound `edit`, `update`, `delete` and `getProducts` methods do not check ownership.

An authenticated user can potentially read, modify or delete another user's Brand Voice profile by guessing its numeric ID.

Required repair: add a Company policy or ownership-scoped route binding for every route.

# 10. Critical Brand Voice API takeover

The API update path loads:

```php
Company::where('id', $request->item_id)->firstOrFail();
```

without `user_id` scope, then sets:

```php
$item->user_id = auth()->id();
```

This permits a user to submit another company ID and take ownership of the record.

Required repair:

```php
Company::whereKey($id)
    ->where('user_id', auth()->id())
    ->firstOrFail();
```

and add a regression test proving cross-user update is rejected.

# 11. AI prompt tenant leakage

Generator and API controller paths load Brand Voice context using unscoped calls such as:

```php
Company::find($companyId)
Product::find($productId)
```

without verifying company ownership, product ownership or product-company relationship.

A user may therefore reference another user's company ID and inject private business context into an AI prompt.

Create an ownership-aware `BrandContextResolver::forUser(userId, companyId, productId)` and use it everywhere.

# 12. No general MagicAI tenant scope

MagicAI has no global company-tenant middleware or Eloquent tenant scope. Isolation depends on individual queries adding `where user_id = current user`, with occasional `team_id` sharing.

The confirmed Team and Brand Voice omissions demonstrate why WorkCore must not rely on this pattern.

MagicAI records fall into four broad groups:

- Platform-global
- User-owned
- Selectively team-shared
- Extension-owned

Core MagicAI has no universal company-owned category.

# 13. WorkCore Company model

WorkCore Company includes:

```text
public_id
name
slug
status
timezone
currency_code
country_code
abn
gst_registered
owner_user_id
settings
soft deletion
```

It owns memberships, verticals, business lines, feature overrides, terminology, locales, settings, document templates and setup sessions.

Company creation is transactional and provisions default company roles, owner membership, lead sources, lead statuses and a sales pipeline.

# 14. WorkCore memberships and permissions

The membership table contains:

```text
company_id
user_id
role_id
role_key
status
is_owner
joined_at
```

with a unique company/user pair. One user may belong to many companies.

WorkCore supports:

- Company roles
- Role permissions
- Member permission overrides
- Access levels such as all, added, owned, both and none

Permission resolution is:

```text
active membership
→ owner access
→ member override
→ role permission
→ optional platform fallback
→ deny
```

Platform fallback should remain disabled by default.

# 15. WorkCore invitations

WorkCore invitations include company, role, email, hashed token, status, inviter, accepter and expiry. Acceptance:

- Hashes the submitted token.
- Locks the invitation row.
- Checks pending status and expiry.
- Verifies email.
- Creates or updates membership.
- Marks the invitation accepted.
- Runs transactionally.

This is the stronger pattern.

# 16. Active-company switching

WorkCore resolves company from:

```text
X-Titan-Company header
session titan_company_id
user active_company_id
```

It verifies active membership before accepting the company. Client-supplied headers are selectors, not authority.

MagicAI's user table needs a WorkCore-owned compatibility migration for nullable indexed `active_company_id`, referencing `tz_companies` with null-on-delete behaviour.

# 17. Tenant operation context

`ResolveWorkCoreTenant` establishes:

```text
company ID
user ID
correlation ID
causation ID
company locale
company timezone
```

Future context should also include actor subject, membership, role, worker, branch, territories, device, authentication assurance and security revision.

# 18. WorkCore global scope is fail-open without context

The `BelongsToCompany` trait adds a `company_id` scope only when tenant context exists. It throws on record creation without tenant context when tenancy is required, but it does not block reads.

Therefore a query such as:

```php
Model::query()->get();
```

outside tenant middleware can return every company's records. Risk areas include commands, queues, schedules, listeners, AI agents, imports and tests.

Required correction:

- Make tenant-owned reads fail closed when context is required.
- Require explicit privileged cross-company APIs.
- Use tenant-aware repositories.
- Add static analysis and tests for unscoped queries.

# 19. Direct database-query risk

Eloquent global scopes do not affect `DB::table(...)`. WorkCore often supplies `company_id` explicitly, which is good, but every direct query must be audited for:

```text
company restriction
company-scoped references
soft deletion
membership authorisation
```

No query may obtain company identity solely from unverified request input.

# 20. Company-scoped reference validation

WorkCore already validates that public records, users and roles belong to the active company. This should be mandatory for worker assignment, sites, jobs, inventory, assets, invoices, branches, territories and AI tool arguments.

# 21. Branches and territories

WorkCore contains regions, districts, branches, territories and assignments. Territories can represent postcodes, suburbs, states, radius areas and GeoJSON polygons.

Company membership alone may be too broad. Add optional branch and territory scopes so regional managers, dispatchers and workers see only authorised records.

# 22. Company deletion risk

Hundreds of WorkCore tables include `company_id`, and many cascade from `tz_companies`. Ordinary deletion could remove customers, jobs, workforce, compliance, finance, AI, property and audit records.

Normal lifecycle must be:

```text
suspend
archive
disable access
apply retention
```

Physical purge must be a separate super-admin process with step-up authentication, dry run, dependency analysis, backup confirmation, retention review, audit and resumability.

# 23. MagicAI-to-WorkCore mapping

| MagicAI concept | WorkCore treatment |
|---|---|
| User | Platform login account |
| Team owner | SaaS subscription owner |
| Team member | SaaS seat holder |
| Team role | Not operationally authoritative |
| Team plan | Module and AI entitlement source |
| Team shared credits | AI usage funding source |
| Brand Company | Brand Voice profile |
| Product | Marketing and sales context |
| WorkCore Company | Operational tenant |
| Company membership | Company authority |
| Company role | Operational role |
| Worker | Field/workforce identity |
| Branch | Operating location |
| Territory | Geographic responsibility |

# 24. CRM and AI tenancy requirements

The paid CRM source is absent, so its tenant model remains unverified. Confirm whether CRM records use user, team, company or workspace scope before integration.

Do not assume CRM companies equal WorkCore companies. Use an external-record mapping table containing company, system, external type/ID, WorkCore type/public ID, mapping state and sync timestamp.

Every WorkCore AI call must include active company, actor, membership, branch/territory restrictions, allowed records, purpose and retention policy. AI memory and retrieval indexes must be partitioned by company.

# 25. Cache and storage tenancy

WorkCore already has company-aware cache and storage helpers. Make them mandatory.

Cache keys should include company and revision. Operational storage paths should include company public ID rather than only MagicAI user ID.

# 26. Unified tenant flow

```text
MagicAI authenticates user
→ resolve platform account state
→ resolve durable actor subject
→ resolve requested WorkCore company
→ verify active membership
→ resolve role and overrides
→ resolve branch/territory scope
→ apply plan/module entitlement
→ establish tenant context
→ execute governed WorkCore action
→ record company and actor in audit/outbox
```

MagicAI Team may contribute subscription and credit entitlement. It never grants operational record access by itself.

# 27. Required MagicAI fixes

## Critical

1. Add Team ownership policies.
2. Use scoped bindings for TeamMember.
3. Bind invitation team ID to the authorised route team.
4. Fix cross-team member update and deletion.
5. Fix or remove the missing invitations route.
6. Clear correct team fields on removal.
7. Add Brand Voice ownership policies.
8. Fix API company takeover.
9. Scope Brand Voice AI context to the current user.
10. Add cross-user access tests.

## High

11. Add foreign keys for team linkage.
12. Create one effective-plan resolver.
13. Support safe invitation acceptance by existing users.
14. Add token expiry and one-time invitation acceptance.
15. Add team membership audit events.
16. Standardise team statuses and seat roles.

# 28. Required WorkCore fixes

## Critical

1. Make tenant-owned reads fail closed without context.
2. Audit every direct database query for company restriction.
3. Remove user-delete cascades identified in Scan 05.
4. Prevent ordinary company hard deletion.
5. Add the MagicAI compatibility migration for `active_company_id`.
6. Add durable actor-subject identity to membership and audit context.

## High

7. Add membership and permission revisions.
8. Add branch and territory scopes to actor context.
9. Add company-switch audit events.
10. Add queue-job tenant snapshots and explicit restoration.
11. Partition AI memory and retrieval by company.
12. Add CRM external-record mapping.
13. Add company-aware cache and storage tests.

# 29. Required tests

## MagicAI Team

- A user cannot view or update another team's member.
- A team owner cannot delete another team's member using mixed IDs.
- Invitation team ID cannot differ from route team.
- A non-owner cannot invite members.
- Seat limits cannot be bypassed.
- Removing a member clears all team linkage.
- The missing invitations route is repaired or removed.

## Brand Voice

- A user cannot edit or delete another user's company.
- The API cannot take over a company by numeric ID.
- A Product must belong to the selected Company and user.
- AI prompts cannot load another user's Brand Voice.

## WorkCore tenancy

- A no-context Eloquent query fails closed.
- A no-context queue job cannot read all companies.
- Direct repositories always require company ID.
- Header, session and active-column switches all require membership.
- Cross-company public IDs are rejected.
- Branch-restricted users cannot access other branches.
- Company switching clears company-sensitive caches.
- Audit/outbox records include company and actor.
- Company suspension blocks operations.
- Company deletion cannot run through ordinary user routes.

# 30. Decisions established by Scan 06

1. MagicAI Team remains a SaaS seat and credit group.
2. MagicAI Company is treated as a Brand Profile in integration code.
3. WorkCore Company remains tenant authority.
4. MagicAI team membership never grants WorkCore company access.
5. MagicAI plan inheritance may grant module entitlement only.
6. WorkCore membership determines operational access.
7. WorkCore supports users in multiple companies.
8. Branch and territory restrictions will be added to context.
9. CRM companies will be mapped rather than merged blindly.
10. Brand Voice data remains user-owned until explicitly mapped.
11. WorkCore tenant queries must fail closed.
12. Direct database queries require explicit company restriction.
13. Company hard deletion is prohibited in normal operation.
14. AI context and retrieval must be company-partitioned.
15. Team and Brand Voice cross-user access paths must be repaired before integration.

# 31. Next scan

```text
07-magicai-roles-permissions-and-access-control.md
```

It will map Spatie roles and permissions, administrator access, super-admin bypasses, menu-derived permissions, policies, middleware, plan entitlements, feature flags, Team access, CRM hooks, WorkCore roles, record access levels, member overrides, AI tool authorisation, approval authority and permission-cache invalidation.

# Evidence files

## MagicAI

```text
app/Models/Team/Team.php
app/Models/Team/TeamMember.php
app/Models/User.php
app/Models/Company.php
app/Models/Product.php
app/Http/Controllers/Team/TeamController.php
app/Http/Requests/Team/TeamInviteRequest.php
app/Rules/Team/TeamInviteRule.php
app/Http/Controllers/Dashboard/BrandController.php
app/Http/Controllers/Api/BrandController.php
app/Services/User/BrandService.php
app/Http/Requests/User/Brand/BrandRequest.php
app/Services/SharedCredit/SharedCreditService.php
app/Domains/Entity/Concerns/HasCreditLimit.php
app/Http/Middleware/CheckTemplateTypeAndPlan.php
app/Helpers/Classes/MenuHelper.php
app/Services/Dashboard/UserDashboardService.php
app/Http/Controllers/OpenAi/GeneratorController.php
routes/panel.php
```

## WorkCore

```text
app/Domains/WorkCore/System/Models/Company.php
app/Domains/WorkCore/System/Models/CompanyMember.php
app/Domains/WorkCore/System/Models/CompanyRole.php
app/Domains/WorkCore/System/Models/CompanyRolePermission.php
app/Domains/WorkCore/System/Models/CompanyMemberPermission.php
app/Domains/WorkCore/System/Models/CompanyInvitation.php
app/Domains/WorkCore/System/Company/Actions/CreateCompany.php
app/Domains/WorkCore/System/Company/Actions/InviteCompanyMember.php
app/Domains/WorkCore/System/Company/Actions/AcceptCompanyInvitation.php
app/Domains/WorkCore/System/Company/Actions/TransferCompanyOwnership.php
app/Domains/WorkCore/System/Identity/MagicAIUserCompanyAdapter.php
app/Domains/WorkCore/System/Tenancy/MagicAITenantResolver.php
app/Domains/WorkCore/System/Tenancy/ResolveWorkCoreTenant.php
app/Domains/WorkCore/System/Tenancy/BelongsToCompany.php
app/Domains/WorkCore/System/Permissions/MagicAIPermissionResolver.php
app/Domains/WorkCore/System/Validation/CompanyScopedReferenceValidator.php
app/Domains/WorkCore/System/Authorization/CompanyRecordAuthorizer.php
app/Domains/WorkCore/System/Modules/Territories/
```

# Evidence limitations

This was a static source scan. It did not execute cross-user Team or Brand Voice requests, shared-credit concurrent workloads, CRM tenancy, distributed cache behaviour, queue-worker tenant restoration, company deletion or branch/territory flows.

The reported cross-user paths are directly visible in controller queries and route bindings and require regression testing after repair.
