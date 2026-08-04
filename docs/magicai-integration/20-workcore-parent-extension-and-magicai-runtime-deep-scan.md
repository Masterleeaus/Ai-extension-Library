---
title: "WorkCore Parent Extension and MagicAI 11 Runtime — Combined Deep Scan"
scan_number: 20
date: "2026-08-03"
status: "Completed"
magicai_source: "Magicai-Server-Files(3).zip"
magicai_version: "11.00"
workcore_repository: "Masterleeaus/workcore-extensions"
workcore_ref: "main"
workcore_commit: "e56389a87db869915a96b7adca1bb12237895d50"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/20-workcore-parent-extension-and-magicai-runtime-deep-scan.md"
---

# Scan 20 — WorkCore Parent Extension and MagicAI 11 Runtime

## Executive conclusion

The WorkCore parent extension is the correct architectural foundation for integrating the WorkCore extension family into MagicAI 11.

It correctly establishes:

- One canonical WorkCore runtime namespace
- One migration owner
- One operational tenant context
- One capability and module registry
- One governed action dispatcher
- One read-model registry
- One idempotency store
- One action audit
- One domain-event store
- One outbox contract
- One host-adapter layer
- Non-destructive disable and uninstall behaviour

The parent package is deliberately not the whole user-facing product.

It provides the foundation required by:

```text
WorkCore Business Network
WorkCore Commercial
WorkCore Work Operations
WorkCore Property Operations
WorkCore Workforce Assurance
```

The packaging architecture is sound.

The current parent runtime is not yet safe for production inside MagicAI because several host and WorkCore defects remain:

1. Owner/admin permission resolution returns a nonexistent enum case.
2. High-risk confirmation accepts any non-empty string.
3. API middleware defaults to Sanctum while MagicAI uses Passport.
4. MagicAI rewrites WorkCore queues and runs a queue worker during web boot.
5. MagicAI exempts all dashboard routes from CSRF.
6. The parent wrapper and canonical runtime use separate enable switches.
7. Parent migrations remain registered when canonical WorkCore is disabled but the wrapper remains enabled.
8. Compatibility aliases can silently retain stale legacy host classes.
9. WorkCore AI configuration is merged under a different namespace from the one its provider reads.
10. The action catalogue exposes all registered actions without actor entitlement/permission filtering.
11. The generic action API generates an idempotency key when callers omit one, so client retries are not actually idempotent unless the client supplies a stable key.
12. The parent outbox defaults to a null transport.
13. The parent menu adapter defaults to a null implementation.
14. The current business-flow endpoint depends on optional add-on actions but is registered by the parent.
15. MagicAI has no `active_company_id`; the parent adds it correctly, but account deletion and durable actor identity remain unresolved.

## Final readiness verdict

```text
Packaging architecture: strong
MagicAI installation compatibility: strongly evidenced
Migration ownership: correct
Tenant architecture: strong foundation
Action governance architecture: strong foundation
Current permission execution: broken
Current confirmation security: unsafe
Current MagicAI API guard integration: incorrect
Current queue integration: incompatible
Production readiness: blocked
```

---

# 1. Parent extension identity

## Native extension

```text
Folder:
app/Extensions/WorkCore

Marketplace map key:
workcore

Extension register key:
workcore

Provider:
App\Extensions\WorkCore\System\WorkCoreServiceProvider

Version:
0.1.1

Compatibility:
MagicAI >= 11.0
PHP ^8.2
Laravel ^10.0
```

## Capabilities declared by the native manifest

```text
workcore
workcore-tenancy
workcore-actions
workcore-audit
workcore-rewind
```

## Data policy

```text
migrations: parent owned
uninstall policy: retain
destructive uninstall: false
```

The parent manifest owns the WorkCore operational schema.

Add-ons do not own duplicate tables or migrations.

---

# 2. MagicAI provider insertion

MagicAI registers optional extensions in:

```text
App\Domains\Marketplace\MarketplaceServiceProvider::$extensionProviders
```

The supplied MagicAI 11 source does not contain the WorkCore provider entries.

Required insertion:

```php
use App\Extensions\WorkCore\System\WorkCoreServiceProvider;
use App\Extensions\WorkCoreBusinessNetwork\System\WorkCoreBusinessNetworkServiceProvider;
use App\Extensions\WorkCoreCommercial\System\WorkCoreCommercialServiceProvider;
use App\Extensions\WorkCoreWorkOperations\System\WorkCoreWorkOperationsServiceProvider;
use App\Extensions\WorkCorePropertyOperations\System\WorkCorePropertyOperationsServiceProvider;
use App\Extensions\WorkCoreWorkforceAssurance\System\WorkCoreWorkforceAssuranceServiceProvider;
```

Provider map:

```php
'workcore' => WorkCoreServiceProvider::class,
'workcore_business_network' => WorkCoreBusinessNetworkServiceProvider::class,
'workcore_commercial' => WorkCoreCommercialServiceProvider::class,
'workcore_work_operations' => WorkCoreWorkOperationsServiceProvider::class,
'workcore_property_operations' => WorkCorePropertyOperationsServiceProvider::class,
'workcore_workforce_assurance' => WorkCoreWorkforceAssuranceServiceProvider::class,
```

The parent must appear before every add-on.

## Why order matters

Add-ons require:

```text
App\Domains\WorkCore\WorkCoreServiceProvider class
WorkModuleRegistry binding
```

If the parent is absent or disabled, add-ons return without loading modules.

---

# 3. Native wrapper boot sequence

The native WorkCore wrapper:

1. Merges `workcore-native.php`.
2. Checks `WORKCORE_NATIVE_ENABLED`.
3. Registers the extension-local WorkCore runtime autoloader.
4. Registers compatibility class aliases.
5. Verifies the canonical WorkCore service provider exists.
6. Registers the canonical provider.
7. Loads parent-owned migrations.
8. Publishes native wrapper configuration.

This is a clean adapter boundary.

The wrapper does not copy WorkCore source into MagicAI host folders outside the extension.

---

# 4. Runtime autoloading

The parent runtime autoloader maps:

```text
App\Domains\WorkCore\*
```

to:

```text
app/Extensions/WorkCore/Runtime/Domains/WorkCore/*
```

It registers the autoloader with:

```text
prepend: true
```

## Positive result

The WorkCore extension can preserve canonical namespaces without requiring Composer regeneration in the MagicAI installation.

## Upgrade risk

If a legacy WorkCore class is already loaded before the native provider registers, the packaged runtime cannot replace that class.

If an old host overlay exists under:

```text
app/Domains/WorkCore
```

or has been preloaded by Composer, the running implementation may be a mixture of old host classes and new extension classes.

## Required diagnostic

At boot, verify critical classes resolve from the expected native runtime paths:

```text
WorkCoreServiceProvider
WorkModuleRegistry
BusinessActionDispatcher
TenantContext
ActionRequest
WorkCoreAccessLevel
```

Fail activation when a conflicting preloaded class is detected.

---

# 5. Compatibility aliases

The native parent aliases legacy host class names to extension-owned adapters:

```text
App\Console\Commands\WorkCoreBootstrapCompanyCommand
App\Http\Controllers\Api\V1\WorkCore\ActionController
App\Http\Controllers\Api\V1\WorkCore\BusinessFlowController
App\Services\WorkCore\CustomerPropertyWorkOrderFlow
App\Support\WorkCore\WorkCoreTenantResolver
App\Support\WorkCore\WorkCorePermissionResolver
```

## Positive result

Canonical WorkCore configuration can retain its historical host class names while native implementations live inside the extension namespace.

## Silent override risk

The alias registrar skips a legacy class when it is already loaded.

It does not verify that the existing class is compatible or current.

An older MagicAI overlay can therefore override:

- tenant resolution
- permission resolution
- action controllers
- business-flow behaviour

without producing an error.

## Required correction

Replace silent skip behaviour with one of:

```text
verify existing class checksum/version
or
require explicit compatibility interface/version
or
fail activation when a conflicting class already exists
```

---

# 6. Dual enable-switch defect

The native wrapper uses:

```text
WORKCORE_NATIVE_ENABLED
```

The canonical runtime uses:

```text
WORKCORE_ENABLED
```

These settings can disagree.

## State matrix

| Native wrapper | Canonical runtime | Result |
|---|---|---|
| false | true | Parent provider not registered |
| true | true | Normal activation |
| false | false | Fully disabled |
| true | false | Wrapper boots and parent migrations remain registered, but canonical bindings/modules/routes do not |

The final state is dangerous and misleading.

## Why it matters

The wrapper's `boot()` checks only `workcore-native.enabled`.

It loads migrations even when canonical `workcore.enabled` is false.

A deployment can therefore describe WorkCore as disabled while still exposing migrations to `artisan migrate`.

## Required correction

Use one authoritative parent enable setting.

Recommended:

```text
WORKCORE_ENABLED
```

The native wrapper should delegate to that same value or require both values and fail on disagreement.

Migrations should not be registered when the canonical runtime is disabled unless an explicit migration-only mode is requested.

---

# 7. Canonical parent provider

The canonical provider binds:

## Context

```text
TenantContextContract
OperationContextContract
```

as scoped services.

## Configured host contracts

```text
TenantResolverContract
PermissionResolverContract
EntitlementResolverContract
OutboxTransportContract
```

## Registries

```text
BusinessActionRegistry
ReadModelRegistry
CapabilityRegistry
WorkModuleRegistry
DonorSourceRegistry
FeatureSourceRegistry
RecordTypeRegistry
```

## Governance

```text
BusinessActionDispatcher
DatabaseActionIdempotencyStore
DatabaseActionAuditRecorder
DatabaseDomainEventRecorder
DatabaseOutboxPublisher
```

## Host adapters

```text
MenuAdapterContract → NullMenuAdapter
NotificationAdapterContract → EventNotificationAdapter
StorageAdapterContract → LaravelPrivateStorageAdapter
ToolBridgeContract → WorkCoreToolBridge
```

## Middleware aliases

```text
workcore.tenant
workcore.capability
workcore.api
```

This is the correct foundation.

---

# 8. Parent-only module state

The parent defines module providers in `WorkModuleRegistry`.

It does not automatically load optional domain modules in the native parent-only profile because package-specific provider classes are absent until their add-ons are installed.

Add-ons call:

```php
$registry->loadMany([...module keys...]);
```

This produces a parent-only state with:

```text
loaded optional modules: none
```

That behaviour is confirmed by repository host-verification profiles.

---

# 9. WorkModuleRegistry behaviour

The registry:

- Rejects invalid provider classes.
- Rejects duplicate module keys.
- Loads a module only once.
- Allows add-ons to load several modules.
- Silently skips missing keys in `loadMany()`.

## Positive result

Add-ons can remain compatible with parent revisions that do not contain every optional module.

## Risk

A misspelled or removed runtime key is silently ignored.

This is already relevant to the QRCode packaging gap.

## Required correction

Add a strict add-on activation method:

```php
loadRequiredMany()
```

It should fail the add-on health check when a declared required module is not defined.

Use a separate tolerant method only for genuinely optional integrations.

---

# 10. Parent migration ownership

The native wrapper loads:

```text
app/Extensions/WorkCore/database/migrations
```

The parent includes compatibility migrations such as:

```text
add users.active_company_id
create workcore_business_flows
```

The native parent manifest declares ownership of the full WorkCore schema.

## Positive result

- Add-ons cannot race to create the same tables.
- Add-on uninstall cannot remove tables.
- Upgrade ordering is centralized.
- Foundation-to-full activation does not rerun separate migration sets.

## Operational concern

The parent package owns a very large schema, including tables used by optional add-ons.

Installing only the parent can therefore migrate tables for modules that are not activated.

This is deliberate in the current extraction phase, but it means:

```text
database footprint
≠ activated product footprint
```

Marketplace and administrators must understand that add-ons control runtime availability, not table creation.

---

# 11. MagicAI user compatibility migration

MagicAI 11 does not contain:

```text
users.active_company_id
```

The parent adds it safely when absent.

Migration behaviour:

- Checks the `users` table exists.
- Checks the column does not already exist.
- Adds nullable indexed unsigned bigint.
- Adds a foreign key to `tz_companies` on non-SQLite databases.
- Uses `nullOnDelete`.
- Drops the foreign key and column on rollback.

This is appropriate for the active-company selector.

## Remaining identity issue

The column links the current MagicAI user directly to the selected operational company.

It does not solve:

- durable actor identity
- customer portal identity
- worker identity
- service account identity
- device identity
- account deletion preservation

Those remain separate WorkCore integration tasks.

---

# 12. Tenant resolver

The native resolver selects candidate company from:

```text
X-Titan-Company header
session titan_company_id
users.active_company_id
```

It verifies:

```text
company ID is numeric
active membership exists
membership user matches authenticated MagicAI user
```

It then persists the selected company to the user and session.

## Strengths

- Client-selected company is not trusted without membership.
- API and web requests use the same authority.
- Company selection is durable.
- Inactive membership is rejected.

## Risks

### Ambiguous failure

The resolver returns null for:

```text
unauthenticated
non-numeric company
missing company
inactive membership
unauthorized company
```

Middleware converts every case into:

```text
409 No active Titan company context
```

This weakens diagnostics and audit.

### Persistent header side effect

An API request containing `X-Titan-Company` changes:

```text
users.active_company_id
```

This changes the user's default company across later devices and sessions.

A mobile integration or background request can unexpectedly alter the dashboard context.

### No membership/role snapshot in context

The resolver returns only:

```text
company_id
user_id
```

The operation context does not contain:

```text
membership_id
role_id
worker_id
device_id
assurance level
permission revision
```

## Required improvements

- Distinguish 401, 403 and 409 outcomes.
- Audit rejected company-selection attempts.
- Make API header selection request-scoped by default.
- Persist company only through an explicit switch action.
- Add membership and actor context.
- Add device context for offline APIs.

---

# 13. Tenant middleware

`ResolveWorkCoreTenant`:

1. Resolves identity.
2. Fails closed when `workcore.require_tenant=true`.
3. Resolves company locale and timezone.
4. Creates correlation and causation IDs.
5. Snapshots existing tenant and operation context.
6. Sets company and user context.
7. Changes application locale and PHP timezone.
8. Restores all previous values in `finally`.

This is a strong request-scoped design.

## Long-lived runtime requirement

Queue jobs, scheduled commands and Octane tasks must establish and clear the same context explicitly.

MagicAI's boot-time queue worker makes this impossible to reason about and must be removed.

---

# 14. Permission resolver defect

The native permission resolver:

- Requires active company membership.
- Allows member-specific overrides.
- Allows role permissions.
- Defaults to deny.

However, owners and admin-like roles return:

```php
WorkCoreAccessLevel::Manage
```

The enum defines only:

```text
all
added
owned
both
none
```

## Severity

**Critical**

## Effect

The resolver throws when the owner/admin path is executed.

Any action permission check for an owner or admin can fail before the handler runs.

## Required correction

Choose a defined access level.

Recommended immediate mapping:

```text
owner → all
admin → all
```

Then separately implement sensitive permissions that do not allow owner bypass.

Add tests for every resolver branch.

---

# 15. Capability and entitlement architecture

The parent registers capability definitions, including:

```text
workcore.core
workcore.tenancy
workcore.authorization
workcore.rewind
```

Optional capabilities are also defined for add-on modules.

The default entitlement resolver checks registered capabilities.

## Integration requirement

Registered capability is not the same as purchased entitlement.

MagicAI plan mapping must contribute:

```text
subscriber plan
team-seat plan
module entitlement
company override
trial/grace state
```

Recommended adapter:

```text
MagicAIWorkCoreEntitlementResolver
```

It should deny by default when:

- subscription is inactive
- add-on extension is disabled
- capability is not loaded
- company is suspended
- plan mapping is absent

---

# 16. Governed action dispatcher

The parent dispatcher enforces:

```text
action registration
tenant match
operation-context match
company capability
actor permission
explicit confirmation
idempotency
transactional handler
domain-event recording
action audit
idempotency completion
```

This is the core value of the parent extension.

Every MagicAI UI, API, AI Agent, Chatbot and automation write should use this dispatcher.

Repositories and Eloquent models must not become public integration interfaces.

---

# 17. Confirmation verifier defect

The default verifier requires confirmation for:

```text
requiresConfirmation=true
high risk
critical risk
```

It then accepts any non-empty confirmation string.

It does not verify:

- record exists
- company
- requesting actor
- approving actor
- action key
- payload hash
- expiry
- one-time use
- approval status
- separation of duties

## Severity

**Critical**

## Required replacement

Create a database-backed confirmation verifier with:

```text
public confirmation ID
company ID
requesting actor
authorized executing actor
approver
action key
payload hash
risk
status
expires_at
consumed_at
correlation ID
```

Verification and consumption must occur atomically inside the action transaction.

---

# 18. Idempotency

The database store uses the unique key:

```text
company_id
action_key
idempotency_key
```

It also stores a payload hash.

It correctly rejects:

- reused key with different input
- concurrent processing
- replay of failed/in-progress action

It returns the prior completed result for a valid replay.

## API weakness

When the client omits an idempotency key, the ActionController generates a random UUID.

A retry after network failure receives a new key and can execute twice.

## Required policy

For consequential write actions:

```text
Idempotency-Key is required
```

The server may generate keys only for low-risk one-shot UI operations where duplicate execution is harmless.

AI Agent and offline calls must always provide stable idempotency keys.

---

# 19. Action audit and actor deletion

The action execution table uses:

```text
actor_user_id → users.id restrictOnDelete
```

This protects action history from silent user deletion.

It also means MagicAI's account deletion workflow can fail while action history exists.

The durable actor-subject migration proposed in Scan 05 remains necessary.

Recommended future reference:

```text
actor_subject_id
magicai_user_id nullable
actor display/email snapshot
```

---

# 20. Domain events

The parent records:

```text
event ID
event name and version
company
actor
aggregate
correlation
causation
payload
occurred time
```

This is a strong operational event envelope.

## Retention issue

Domain events cascade when the company is hard-deleted.

Company deletion must remain archive-first.

A regulated purge must be a separate retention-aware process.

---

# 21. Outbox

The parent binds:

```text
OutboxPublisherContract → DatabaseOutboxPublisher
```

Configured consumers include:

```text
titan_signal
titan_pulse
titan_rewind
analytics
notifications
integrations
```

The default transport is:

```text
NullOutboxTransport
```

## Result

Events can be recorded, but no external consumer receives them until a real transport is bound.

## Required MagicAI adapter

Implement:

```text
MagicAIOutboxTransport
```

It may dispatch to dedicated Laravel queues, but it must preserve:

- event ID
- company
- correlation/causation
- attempt count
- retry/backoff
- dead-letter state
- consumer-specific delivery state

Do not use synchronous event listeners for durable integration.

---

# 22. Host adapters

## Menu

Default:

```text
NullMenuAdapter
```

Result:

- Parent boots without touching MagicAI menus.
- No WorkCore menu appears until a MagicAI adapter is bound.

Required:

```text
MagicAIMenuAdapter
```

It must contribute menu definitions without using menu visibility as operational authorization.

## Notifications

Default:

```text
EventNotificationAdapter
```

Required:

- MagicAI channel listener
- tenant-aware recipient resolution
- template mapping
- delivery status and retry
- no silent failure

## Storage

Default:

```text
LaravelPrivateStorageAdapter
```

This is preferable to public storage.

MagicAI must configure a private disk and signed download controller.

WorkCore company paths should include company public ID.

## Tools

Default:

```text
WorkCoreToolBridge
```

MagicAI AI Agent integration must expose filtered WorkCore tools through this bridge, not direct model access.

---

# 23. Parent API routes

When enabled, the parent exposes:

```text
GET  actions
GET  actions/{action}
POST actions/{action}/execute
POST flows/customer-property-work-order
```

Default middleware is:

```text
api
auth:sanctum
workcore.tenant
workcore.api
```

MagicAI uses Passport:

```text
auth:api
```

## Severity

**Critical integration mismatch**

Required MagicAI middleware:

```text
api
auth:api
workcore.tenant
workcore.api
```

---

# 24. Action catalogue disclosure

The action index and show endpoints return:

```text
action key
risk
confirmation requirement
capability
permission
metadata
```

They do not filter definitions by:

- loaded module
- company entitlement
- actor permission
- channel
- plan

## Impact

An authenticated company user can enumerate unavailable or sensitive internal capabilities.

Execution remains checked, but catalogue disclosure helps an attacker map the system.

## Required correction

Return only actions available to the current actor and company.

Provide the complete catalogue only to a dedicated WorkCore administrator permission.

---

# 25. Generic action execution

The ActionController:

- Validates payload, idempotency and confirmation identifiers.
- Reads the authenticated MagicAI user.
- Reads company from `users.active_company_id`.
- Creates a governed ActionRequest.
- Dispatches through the parent dispatcher.

## Recommended correction

Use the resolved tenant and operation context directly rather than rereading the user column.

This prevents inconsistency if:

- API company selection becomes request-scoped
- user persistence fails
- future context uses company public IDs
- system actors execute without a MagicAI user record

---

# 26. Parent business flow

The parent supplies a composite flow:

```text
customer
→ premises/property
→ work order
```

It calls three governed actions with derived idempotency keys:

```text
{flow}:customer
{flow}:premises
{flow}:work-order
```

It stores flow state and can resume after partial failure.

## Strengths

- Uses governed actions.
- Uses stable sub-action idempotency keys.
- Records completed/failed flow state.
- Replays completed flow result.
- Supports recovery after partial completion.

## Risks

### Optional module dependency

The parent registers the route even when CRM, Premises or Operations modules are absent.

The flow then fails because actions are unregistered.

### Confirmation design

One confirmation ID is passed to three separate actions.

A future secure verifier must determine whether the confirmation authorizes:

```text
the entire composite saga
or
each individual action
```

### Flow idempotency race

The flow queries its own idempotency row before insert without an explicit lock.

The table must enforce a unique company/idempotency key and the service must handle duplicate insert races.

### Sensitive request storage

The flow stores the entire request as JSON.

Customer and property data may require encryption/redaction and retention controls.

## Required correction

- Expose the route only when all required modules are loaded.
- Add capability preflight.
- Add database uniqueness and conflict handling.
- Bind one signed confirmation to the canonical full-flow payload.
- Redact/encrypt sensitive stored input.
- Add flow-step records rather than one opaque request/result blob.

---

# 27. MagicAI queue incompatibility

MagicAI's AppServiceProvider:

1. Finds jobs whose queue is not `default`.
2. Rewrites them to `default`.
3. Calls `queue:work --once` during application boot.

## Consequences for WorkCore

- Outbox queues lose isolation.
- Finance tasks lose isolation.
- Offline sync tasks lose isolation.
- Long-running AI tasks enter web-request execution.
- Retries and timeouts become unpredictable.
- Tenant context can leak between jobs.
- Web latency increases.
- Multiple web requests can race to run queue jobs.

## Required correction

Delete this method and call.

Run queue workers externally with explicit queue lists, timeouts and memory limits.

---

# 28. MagicAI CSRF incompatibility

MagicAI excludes:

```text
dashboard/*
```

from CSRF verification.

Any WorkCore dashboard write route inherits that exclusion.

## Required correction

Remove the broad pattern.

Keep only narrowly defined, signature-verified webhook routes outside CSRF.

---

# 29. Parent install and uninstall

## Installation

MagicAI's modern installer can place the extension under:

```text
app/Extensions/WorkCore
```

The provider can load without Composer regeneration because of the native autoloader.

## Disable

Disable must stop:

- provider boot
- route registration
- optional module loading
- schedules
- queue dispatch
- external delivery

It must not delete data.

## Uninstall

The parent uninstall method is intentionally a no-op.

Operational data, audit and evidence remain.

A separate purge operation is required for destructive deletion.

This is the correct safety policy.

---

# 30. CI and verification evidence

The WorkCore repository contains tests and workflows for:

- parent-only activation
- full activation
- disabled state
- parent-absent add-ons
- module load expectations
- middleware aliases
- provider loading
- migration execution
- Laravel 10 compatibility
- package ownership and checksums
- deterministic builds

The merged integration PR reports successful package and fixture verification.

## Evidence boundary

These tests establish:

```text
package structure
provider discovery
module activation
migrations
Laravel compatibility
```

They do not yet prove:

```text
real MagicAI Passport requests
real subscription entitlements
owner permission branch
secure confirmations
queue execution
outbox delivery
menu rendering
notifications
AI Agent tool calls
cross-extension business workflows
```

---

# 31. Required implementation changes

## Critical

1. Replace `WorkCoreAccessLevel::Manage`.
2. Replace arbitrary-string confirmation verification.
3. Use MagicAI Passport middleware.
4. Remove MagicAI queue rewriting and boot worker.
5. Restore dashboard CSRF.
6. Unify native and canonical enable flags.
7. Prevent migration loading in disabled state.
8. Validate legacy aliases instead of silently accepting them.

## High

9. Filter action catalogue by entitlement and permission.
10. Require caller-supplied idempotency keys for consequential actions.
11. Bind MagicAI plan entitlements.
12. Bind a real outbox transport.
13. Bind a real menu adapter.
14. Bind notification and private storage integrations.
15. Gate composite business flow by loaded modules.
16. Add flow idempotency constraints and step records.
17. Fix WorkCore AI config namespace/path.
18. Add durable actor identity.

## Medium

19. Add install health and class-source diagnostics.
20. Add company-switch audit.
21. Add request-scoped API company selection.
22. Add dependency diagnostics for every add-on.
23. Add parent data-footprint documentation.
24. Add regulated company/archive/purge workflow.

---

# 32. Mandatory integration tests

## Parent boot

- Parent provider resolves from native runtime.
- Critical classes resolve from expected files.
- Legacy class conflicts fail activation.
- Parent disabled means no routes, bindings or migrations.
- Parent-only profile loads zero optional modules.

## Authentication and tenancy

- Passport token accesses WorkCore API.
- Unauthorized company header is rejected.
- Inactive membership is rejected.
- Header selection does not unexpectedly change another device's default company.
- Tenant, operation, locale and timezone restore after request.
- Queue jobs clear context.

## Permission

- Owner path returns a valid access level.
- Member override beats role permission.
- No membership denies access.
- Sensitive owner-bypass policy works.
- Action catalogue hides unavailable actions.

## Confirmation

- Random string is rejected.
- Wrong company/actor/action/payload is rejected.
- Expired confirmation is rejected.
- Consumed confirmation is rejected.
- Valid confirmation executes once.
- Composite flow confirmation binds the whole flow.

## Idempotency

- Same key and same payload replays.
- Same key and different payload conflicts.
- Concurrent requests execute once.
- Failed action retry follows explicit recovery policy.
- Missing idempotency key is rejected for high-risk actions.

## Outbox

- Event recorded in same transaction.
- Consumer delivery retries.
- Dead-letter state is visible.
- MagicAI notification adapter receives one event.
- Queue names are preserved.

## Install lifecycle

- Parent installs from clean MagicAI.
- Upgrade from previous parent version works.
- Disable/re-enable preserves data.
- Uninstall/reinstall preserves data.
- Migration rollback is not used as normal uninstall.
- Signed package verification blocks tampered ZIP.

---

# 33. Decisions established by Scan 20

1. The parent-plus-five-add-ons architecture is retained.
2. The parent remains the sole migration owner.
3. The parent remains the WorkCore operational governance authority.
4. MagicAI remains authentication and SaaS entitlement authority.
5. Parent-only installation loads no optional modules.
6. WorkCore APIs will use Passport in MagicAI.
7. WorkCore queues will not be rewritten to `default`.
8. Dashboard writes will use CSRF protection.
9. High-risk confirmation will be database-backed and payload-bound.
10. Owner/admin permission resolution must be corrected before any operational route is enabled.
11. One authoritative enable switch will control provider, migrations and routes.
12. Legacy overlay conflicts will fail activation.
13. Action catalogue visibility will be permission filtered.
14. Add-on-dependent business flows will be capability gated.
15. Disable and uninstall will preserve operational data.

---

# 34. Next combined scan

```text
21-workcore-business-network-and-magicai-integration-deep-scan.md
```

It should inspect both:

```text
WorkCore Business Network
+
MagicAI CRM, AI Chat, AI Agent, knowledge, support,
review, menu, plan and notification host contracts
```

It should map:

- CRM authority
- customer and lead conversion
- catalogue
- support
- knowledge
- reviews and rebooking
- territories
- intelligence
- wizards
- native AI
- MagicAI AI Agent tool exposure
- MagicAI menu and plan contributions
- required adapters
- current defects
- end-to-end integration tests

---

# Evidence sources

## WorkCore repository

```text
native-extensions/WorkCore/System/WorkCoreServiceProvider.php
native-extensions/WorkCore/System/Runtime/WorkCoreRuntimeAutoloader.php
native-extensions/WorkCore/System/Runtime/WorkCoreHostAliasRegistrar.php
native-extensions/WorkCore/System/Resolvers/WorkCoreTenantResolver.php
native-extensions/WorkCore/System/Resolvers/WorkCorePermissionResolver.php
native-extensions/WorkCore/System/Http/Controllers/ActionController.php
native-extensions/WorkCore/System/Http/Controllers/BusinessFlowController.php
native-extensions/WorkCore/System/Services/CustomerPropertyWorkOrderFlow.php
native-extensions/WorkCore/database/migrations/2026_08_03_000001_add_workcore_active_company_to_users.php
native-extensions/WorkCore/extension.manifest.json
packages/workcore-shared-foundation/src/Domains/WorkCore/WorkCoreServiceProvider.php
packages/workcore-shared-foundation/src/Domains/WorkCore/Config/workcore.php
packages/workcore-shared-foundation/src/Domains/WorkCore/System/Registry/WorkModuleRegistry.php
packages/workcore-shared-foundation/src/Domains/WorkCore/System/Tenancy/ResolveWorkCoreTenant.php
packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/BusinessActionDispatcher.php
packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/Policies/ExplicitConfirmationVerifier.php
packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/Infrastructure/DatabaseActionIdempotencyStore.php
packages/workcore-shared-foundation/src/Domains/WorkCore/Database/Migrations/2026_07_23_120007_create_tz_action_governance_tables.php
packages/workcore-shared-foundation/src/Domains/WorkCore/Routes/api.php
integration/magicai-10-fixture/profiles.json
tools/verify_magicai_native_host.php
tools/build_extensions.py
```

## MagicAI host

```text
app/Domains/Marketplace/MarketplaceServiceProvider.php
app/Providers/AppServiceProvider.php
app/Http/Middleware/VerifyCsrfToken.php
config/auth.php
app/Models/User.php
```

---

# Evidence limitations

This was a static repository and host-package scan.

It did not:

- Install the parent into a live MagicAI database
- Execute Passport API requests
- Run owner/admin permission resolution
- Execute a high-risk confirmation
- Run the composite business flow
- Start queue workers
- Publish the outbox
- Render MagicAI menus
- Test uninstall/reinstall
- Test production storage or notifications

The repository CI evidence supports package and activation compatibility, not full production integration.
