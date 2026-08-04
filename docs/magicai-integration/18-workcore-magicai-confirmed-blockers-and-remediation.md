---
title: WorkCore Extensions + MagicAI 11 — Confirmed Blockers and Remediation Register
date: 2026-08-03
status: Evidence-backed defect and integration register
---

# WorkCore Extensions + MagicAI 11 — Confirmed Blockers and Remediation Register

## Severity legend

```text
Critical — unsafe or prevents correct core execution
High — major integration failure or misleading package state
Medium — incomplete production behaviour
Low — maintainability or documentation issue
```

## Critical blockers

### WC-MAI-001 — Invalid permission enum result

`WorkCorePermissionResolver` returns `WorkCoreAccessLevel::Manage`, but the enum has no `Manage` case.

**Impact:** permission evaluation can fail at runtime.

**Fix:** map the permission to a real access case or add a deliberately specified enum case with comparison semantics and tests.

### WC-MAI-002 — Confirmation is not bound to an action

`ExplicitConfirmationVerifier` accepts any non-empty `confirmationId`.

**Impact:** a stale or invented string can authorise a high-risk or critical action.

**Fix:** require a stored confirmation bound to company, actor, action key, canonical input hash, risk, expiry and one-time nonce. Consume it atomically with action dispatch.

### WC-MAI-003 — API guard mismatch

MagicAI uses Passport and `auth:api`. WorkCore routes and configurations still contain `auth:sanctum` assumptions.

**Impact:** valid MagicAI API requests can fail or a second token system can be introduced accidentally.

**Fix:** make WorkCore API middleware host-configurable and set the MagicAI profile to `auth:api`.

### WC-MAI-004 — WorkCore AI config registration mismatch

The parent merges AI configuration under `workcore.workcore_ai`, while the AI provider reads `workcore_ai.*`. The provider also references a relative config path that does not resolve to the actual shared-foundation config location.

**Impact:** AI enablement, credentials, budgets, tools and routes can silently use defaults or fail.

**Fix:** choose one top-level key, load the correct file, and add a boot test asserting known values.

### WC-MAI-005 — QRCode is owned but not activated

Repository ownership and package modules include `QRCode`; native runtime-key lists do not include `qrcode`.

**Impact:** the repository reports 35 modules, but native profiles activate 34 runtime keys.

**Fix:** add a QRCode module provider/runtime key or reclassify QRCode as a shared parent capability.

### WC-MAI-006 — Job completion and Finance event mismatch

Work Operations emits `workcore.work_order.status_changed`. The Finance listener expects `work.job.completed`, and no listener registration was found.

**Impact:** completed jobs do not reliably create operational invoices.

**Fix:** define one canonical completion event, register the listener explicitly, and test outbox delivery plus idempotent invoice creation.

### WC-MAI-007 — MagicAI queue mutation destroys WorkCore queue policy

MagicAI boot logic rewrites non-default queues to `default` and runs `queue:work --once`.

**Impact:** WorkCore queue separation, retry policy, latency control and tenant-aware processing are invalidated.

**Fix:** remove boot-time worker execution and run workers through deployment process management.

### WC-MAI-008 — Dashboard-wide CSRF exemption

MagicAI excludes `dashboard/*` from CSRF verification.

**Impact:** WorkCore dashboard writes would inherit unsafe cross-site request behaviour.

**Fix:** remove the broad exemption and retain only narrowly justified webhook exclusions.

## High blockers

### WC-MAI-009 — Finance action-key mismatch

```text
Tool/registry: money.create_invoice_from_job
Internal action: money.invoice.create_from_job
```

Normalise to one public action key and retain aliases only through explicit compatibility mappings.

### WC-MAI-010 — Reviews use missing `tz_contacts`

The WorkCore schema uses `tz_customer_contacts`.

Fix repository references and add migration-schema contract tests.

### WC-MAI-011 — Add-on manifests hide effective runtime

The wrappers declare no routes, permissions, queues, schedules or webhooks even though activated modules contribute them.

Generate transitive manifest data during the release build.

### WC-MAI-012 — Installed state can disagree with runtime state

MagicAI provider registration is based on provider class existence; marketplace state is database/remote metadata.

Add reconciled states:

```text
downloaded
installed
provider_discovered
bootable
healthy
enabled
```

### WC-MAI-013 — Parent-absent add-ons fail silently

Current add-ons return without activation. This protects runtime but can mislead administrators.

Prevent activation until dependencies are satisfied and show a marketplace health error.

### WC-MAI-014 — Production AI credential adapter absent

WorkCore defaults to `NullCredentialResolver`.

Bind WorkCore to MagicAI provider credentials and BYO-key policy without copying secrets into operational tables.

### WC-MAI-015 — Native AI tool registry references optional packages

The parent configuration lists registries across all domain packages.

Filter registries by installed provider, class existence and loaded module state before resolution.

### WC-MAI-016 — Installer package integrity is insufficient

MagicAI extension extraction is executable-code installation.

Require a signed manifest, SHA-256 match, trusted publisher, path validation, staging, preflight, rollback and health check before activation.

## Medium blockers

### WC-MAI-017 — Missing complete shell tests

Current evidence strongly covers packaging and Laravel 10 activation, but not full MagicAI business actions.

Add real application tests using Passport, MagicAI users, WorkCore companies, plan entitlements, queues, notifications and AI Agent tool calls.

### WC-MAI-018 — Menu and plan feature generation incomplete

Create one menu contribution per installed add-on and map every menu/action to plan capabilities.

### WC-MAI-019 — Cross-extension dependencies are under-declared

Examples:

- Support may convert into Work Operations.
- Work Operations references Property Operations.
- Vertical Operations references Workforce Assurance.
- Commercial integrates with Work Operations.

Express capability dependencies and fail gracefully when optional peers are absent.

### WC-MAI-020 — Data retention and privacy rules need product policy

Define explicit retention for biometric attendance, geolocation, compliance evidence, financial records, AI memory, customer communications and archived companies.

## Remediation order

```text
1. Host queue and CSRF defects
2. Permission and confirmation defects
3. Passport middleware
4. AI config and credential adapters
5. QRCode activation
6. Finance completion event
7. schema/table mismatch
8. transitive manifests and dependency health
9. menu/plan integration
10. full business-scenario tests
```

## Release decision

Do not deploy the complete WorkCore family into a production MagicAI instance until Critical items WC-MAI-001 through WC-MAI-008 are closed and verified.
