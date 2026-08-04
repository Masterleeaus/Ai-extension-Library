---
title: WorkCore Extensions and MagicAI 11 — Combined Integration Architecture
date: 2026-08-03
status: Repository-backed source-of-truth integration baseline
magicai_source: MagicAI 11.00 supplied server package
workcore_source: Masterleeaus/workcore-extensions
workcore_ref: main
workcore_commit: e56389a87db869915a96b7adca1bb12237895d50
---

# WorkCore Extensions and MagicAI 11 — Combined Integration Architecture

## Purpose

This document replaces the earlier one-sided model in which MagicAI was scanned first and WorkCore was treated only as a future integration target.

The integration now has two explicit source authorities:

- **MagicAI 11.00** is the host shell and source of truth for authentication, SaaS subscriptions, AI-provider infrastructure, marketplace presentation, themes, menus, notifications, mail, storage and host APIs.
- **`Masterleeaus/workcore-extensions`** is the source of truth for WorkCore packaging, operational tenancy, customers, premises, jobs, workforce, compliance, operational finance, governed actions, audit, offline synchronisation and the WorkCore-native AI/tool layer.

The correct result is not a WorkCore rewrite inside MagicAI and not a parallel MagicAI application. It is a provider-owned extension family installed into the existing MagicAI runtime.

## Final package topology

```text
MagicAI 11
└── WorkCore
    ├── WorkCore Business Network
    ├── WorkCore Commercial
    ├── WorkCore Work Operations
    ├── WorkCore Property Operations
    └── WorkCore Workforce Assurance
```

### Parent extension

`WorkCore` is mandatory. It owns:

- canonical `App\Domains\WorkCore` runtime
- compatibility autoloading and aliases
- operational tenant context
- WorkCore permissions and capability resolution
- governed business actions and read models
- action audit and idempotency
- domain events and outbox
- historical migrations
- all WorkCore operational tables
- shared contracts and host adapters
- data-retaining disable and uninstall behaviour

### Add-on extensions

The five add-ons do not duplicate the runtime. They activate declared module groups through the parent-owned `WorkModuleRegistry`.

This provides one canonical source tree, one migration owner, independent product licensing, exact module activation, safe add-on removal without deleting business data, and no drift between duplicated shared code.

## Authority boundary

| Concern | MagicAI authority | WorkCore authority | Integration rule |
|---|---|---|---|
| Login, registration, password reset | Yes | No | WorkCore consumes authenticated MagicAI users |
| API token system | Passport / `auth:api` | No independent token system | Replace WorkCore Sanctum assumptions with host middleware |
| SaaS subscriptions | Yes | No | Map plans to WorkCore capabilities |
| Shared AI credits | Yes | No | WorkCore AI usage must reserve/post against MagicAI credit policy |
| Operational companies | Host account may exist | Yes | `tz_companies` remains operational tenant authority |
| Operational memberships | No | Yes | Resolve MagicAI user IDs into WorkCore memberships |
| Customers, leads, contacts | Host CRM is optional/donor | Yes | WorkCore Business Network becomes authoritative |
| Premises, sites, assets | No | Yes | Property Operations owns operational records |
| Jobs and scheduling | No | Yes | Work Operations owns jobs, appointments and dispatch |
| Workforce and attendance | No | Yes | Workforce Assurance owns workforce context |
| Operational invoices and payments | SaaS billing only | Yes | Commercial owns customer/job money flows |
| AI model/provider routing | Yes | Native fallback may exist | Prefer MagicAI adapter; keep WorkCore native path optional |
| AI tools and governed actions | Host agent invokes | Yes | Tool calls terminate in WorkCore action/read registries |
| Menus and themes | Yes | Contributes definitions | Use MagicAI menu hooks |
| Notification delivery | Yes | Emits intent/events | Adapter translates WorkCore events to host delivery |
| Storage | Yes | Defines ownership/privacy | Use host storage with WorkCore tenant paths |
| Audit and Rewind | No | Yes | Never replace with generic host activity logs |

## MagicAI bootstrap insertion

MagicAI registers optional extensions through:

```php
App\Domains\Marketplace\MarketplaceServiceProvider::$extensionProviders
```

Required provider map:

```php
'workcore'
    => \App\Extensions\WorkCore\System\WorkCoreServiceProvider::class,

'workcore_business_network'
    => \App\Extensions\WorkCoreBusinessNetwork\System\WorkCoreBusinessNetworkServiceProvider::class,

'workcore_commercial'
    => \App\Extensions\WorkCoreCommercial\System\WorkCoreCommercialServiceProvider::class,

'workcore_work_operations'
    => \App\Extensions\WorkCoreWorkOperations\System\WorkCoreWorkOperationsServiceProvider::class,

'workcore_property_operations'
    => \App\Extensions\WorkCorePropertyOperations\System\WorkCorePropertyOperationsServiceProvider::class,

'workcore_workforce_assurance'
    => \App\Extensions\WorkCoreWorkforceAssurance\System\WorkCoreWorkforceAssuranceServiceProvider::class,
```

The parent must be registered before all add-ons.

## Host prerequisites before installation

MagicAI must be hardened before WorkCore is enabled:

1. Remove `AppServiceProvider::jobRuns()` queue rewriting.
2. Remove boot-time `queue:work --once`.
3. Restore CSRF protection to WorkCore dashboard writes.
4. Restrict extension install/uninstall to `super_admin`.
5. Convert installer mutations to non-GET authorised operations.
6. Add package signature and hash verification before extraction.
7. Validate extracted ZIP paths.
8. Add rollback/staging around extraction, migration and publishing.
9. Clear stale generated Laravel caches during deployment.
10. Preserve WorkCore queues instead of forcing every job to `default`.

## WorkCore host adapters required

The parent package currently supplies generic or null adapters. MagicAI-specific implementations are required for:

```text
Tenant identity bridge
Permission bridge
Plan/capability entitlement bridge
Menu adapter
Notification adapter
Storage adapter
AI credential/provider adapter
AI credit ledger adapter
Tool bridge
Queue tenant-context bridge
Host URL/navigation bridge
```

Recommended namespace:

```text
App\Extensions\WorkCore\System\Host\MagicAI\
```

Suggested adapters:

```text
MagicAITenantIdentityAdapter
MagicAIPermissionBridge
MagicAIEntitlementResolver
MagicAIMenuAdapter
MagicAINotificationAdapter
MagicAIStorageAdapter
MagicAICredentialResolver
MagicAICreditLedger
MagicAIToolBridge
```

## Route integration

WorkCore routes must remain provider-owned.

### Dashboard routes

```text
web
auth
workcore.tenant
workcore.capability
```

### API routes

```text
api
auth:api
workcore.tenant
workcore.api
throttle:workcore-actions
```

### Offline sync

```text
api
auth:api
workcore.tenant
workcore.api
throttle:workcore-offline-sync
```

Do not use `auth:sanctum` in the MagicAI installation unless Sanctum is deliberately added as a second supported API guard.

## Database and marketplace model

The parent extension owns the historical migration sequence and all operational tables. Add-ons declare no migrations because they activate parent-owned modules.

Marketplace manifests must nevertheless expose effective transitive ownership, including:

- activated tables
- routes
- permissions
- schedules
- queues
- tools
- external providers
- webhooks
- commands

Without this, an add-on appears harmless while activating a large operational subsystem.

## Safe installation state machine

```text
downloaded
signature_verified
staged
preflight_passed
backed_up
maintenance_enabled
extracted
migrated
assets_published
provider_discovered
health_checked
activated
```

Failure should record:

```text
failed_at
failure_stage
last_error
previous_version
rollback_status
```

## Integration definition of done

The integration is complete only when all of the following pass in the real MagicAI application:

- provider discovery
- parent-only activation
- each add-on independently
- full six-extension activation
- clean and upgrade migrations
- authenticated Passport API access
- tenant switching and fail-closed isolation
- role and permission enforcement
- plan/capability enforcement
- governed high-risk confirmation
- idempotent replay
- action audit and outbox delivery
- queue execution with tenant context
- menu and dashboard contribution
- WorkCore tool invocation from MagicAI AI Agent
- operational event-to-Finance flow
- disable/re-enable without data loss
- uninstall/reinstall without operational data deletion

## Current conclusion

The WorkCore repository has solved the core packaging problem. It has not yet completed the full MagicAI operational integration.

The correct next phase is adapter implementation and real-shell integration testing, not another extension split.
