---
title: WorkCore Extensions into MagicAI 11 — Install, Activation and Release Plan
date: 2026-08-03
status: Implementation runbook
---

# WorkCore Extensions into MagicAI 11 — Install, Activation and Release Plan

## Phase 0 — Freeze source authorities

Pin:

```text
MagicAI host: supplied MagicAI 11.00 package
WorkCore: Masterleeaus/workcore-extensions main
WorkCore commit: e56389a87db869915a96b7adca1bb12237895d50
```

Do not copy code from earlier WorkCore ZIPs once the equivalent repository package exists.

## Phase 1 — Harden MagicAI host

Complete before installing WorkCore:

- remove boot-time queue worker invocation
- stop rewriting queue names
- restore CSRF protection
- require super-admin installation permissions
- remove unauthenticated cache mutation endpoints
- stage extension extraction outside executable application paths
- verify package signature and SHA-256
- validate ZIP paths
- add database/file rollback
- clear generated caches during deployment
- add install health status to extension records

## Phase 2 — Prepare the extension family

Build deterministic packages from repository source:

```text
WorkCore.zip
WorkCoreBusinessNetwork.zip
WorkCoreCommercial.zip
WorkCoreWorkOperations.zip
WorkCorePropertyOperations.zip
WorkCoreWorkforceAssurance.zip
```

Each release must include:

- `extension.json`
- `extension.manifest.json`
- provider class
- configuration
- checksum manifest
- package version
- parent/dependency declaration
- migration policy
- data-retention policy
- compatibility range
- transitive contribution summary

## Phase 3 — Register providers

Patch MagicAI MarketplaceServiceProvider with six entries.

Order:

```text
workcore
workcore_business_network
workcore_commercial
workcore_work_operations
workcore_property_operations
workcore_workforce_assurance
```

Add tests that verify:

- parent is registered first
- all providers resolve
- absent folders remain harmless
- extension database state agrees with provider discovery
- a missing provider class marks an extension unhealthy

## Phase 4 — Parent-only installation

Install only `WorkCore`.

Verify:

- provider boots
- canonical namespace autoloads
- no optional modules are loaded
- migrations complete
- middleware aliases exist
- tenant context is fail-closed
- WorkCore diagnostics pass
- operational data survives disable/re-enable

## Phase 5 — MagicAI adapter implementation

Implement and bind:

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

Do not enable WorkCore-native AI routes until credentials, credits, permissions and model routing are connected to host policy.

## Phase 6 — Add-on activation profiles

### Parent

```text
WorkCore
Expected modules: none
```

### Business + Operations

```text
WorkCore
WorkCoreBusinessNetwork
WorkCoreWorkOperations
```

### Commercial

```text
WorkCore
WorkCoreCommercial
```

### Property + Workforce + Operations

```text
WorkCore
WorkCoreWorkOperations
WorkCorePropertyOperations
WorkCoreWorkforceAssurance
```

### Full

```text
all six extensions
```

### Disabled

```text
all folders present
WORKCORE_ENABLED=false
Expected modules: none
```

### Parent absent

```text
one add-on present
parent absent
Expected: add-on inactive and marketplace dependency warning
```

## Phase 7 — Correct current source blockers

Mandatory fixes:

1. undefined `WorkCoreAccessLevel::Manage`
2. arbitrary non-empty confirmation acceptance
3. `auth:sanctum` assumptions
4. WorkCore AI config namespace/path mismatch
5. QRCode ownership without activation
6. disconnected job-completion Finance listener
7. Finance action-key mismatch
8. `tz_contacts` table reference
9. missing effective contribution metadata
10. null AI credential resolver in production

## Phase 8 — MagicAI shell integration

Add:

- WorkCore menu groups
- plan feature mappings
- dashboard routes
- operational company switcher
- operational context beneath the AI composer
- deep links from AI results to WorkCore screens
- notification translations
- private file serving
- installation health page
- extension dependency status

## Phase 9 — AI Agent integration

Required call chain:

```text
MagicAI AI Agent
→ MagicAI WorkCore tool adapter
→ WorkCore NativeToolCatalogue
→ entitlement and permission filters
→ WorkCore read model or governed action
→ confirmation, idempotency and audit
→ result returned to the agent
```

The agent must never call WorkCore repositories directly.

## Phase 10 — End-to-end business scenarios

Minimum acceptance suite:

1. Create customer and premise.
2. Create estimate.
3. Accept estimate and create job.
4. Schedule and dispatch worker.
5. Complete attendance verification.
6. Fill operational form and attach evidence.
7. Complete job.
8. Generate invoice.
9. Send payment instructions.
10. Record and allocate payment.
11. Emit audit, event and outbox records.
12. Ask MagicAI AI Agent to read job status.
13. Ask the agent to perform a governed change.
14. Approve with a bound one-time confirmation.
15. Replay the action and verify idempotency.
16. Switch company and verify zero cross-tenant leakage.

## Phase 11 — Release gates

Release only when:

- all CI workflows pass
- all six ZIP hashes are recorded
- migrations pass from clean and upgrade states
- queue workers preserve WorkCore queues
- Passport authentication passes
- cross-package scenarios pass
- security tests pass
- disable/re-enable passes
- uninstall preserves operational data
- backup and rollback are proven
- documentation matches the release commit

## Recommended branch workflow

```text
Repository: Masterleeaus/workcore-extensions
Branch: feature/magicai-production-integration

host contract fixes
→ parent adapter bindings
→ add-on corrections
→ MagicAI fixture tests
→ full shell tests
→ deterministic release build
→ draft PR
```
