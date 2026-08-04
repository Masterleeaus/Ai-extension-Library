---
title: WorkCore–MagicAI Document Reconciliation and Source Authority
date: 2026-08-03
status: Documentation control record
---

# WorkCore–MagicAI Document Reconciliation and Source Authority

## Why this document exists

Scans 01–14 were produced primarily from the supplied MagicAI package and an earlier consolidated WorkCore snapshot.

The WorkCore Extensions repository now contains the actual six-extension packaging that must be installed into MagicAI.

The earlier scans are not deleted. They remain evidence of the host package and discovery process. Their WorkCore packaging recommendations are superseded where this document says so.

## Current source authority

| Source | Authority |
|---|---|
| Supplied MagicAI 11.00 package | Exact host code, routes, middleware, providers and marketplace runtime |
| `Masterleeaus/workcore-extensions` `main` | Exact WorkCore extension packaging and current operational source |
| Earlier consolidated WorkCore ZIP | Historical donor and comparison source |
| Documents repository | Audit trail and integration decisions |

## Reconciliation of earlier scans

### Scan 01 — Complete system inventory

**Retained**

- MagicAI host inventory
- version/framework counts
- absent paid extension source
- host authority boundaries
- runtime residue findings

**Revised**

- WorkCore is not one proposed monolithic extension.
- The implementation is a parent plus five add-ons.
- The repository contains 35 owned modules and 2,158 owned files.

### Scan 02 — Bootstrap and runtime architecture

**Retained**

- provider order
- route and middleware boot sequence
- queue boot defect
- CSRF defect
- modern versus legacy installer analysis

**Revised**

- Marketplace must register six providers, parent first.
- The parent owns runtime autoload and migrations.
- Add-ons activate module keys through `WorkModuleRegistry`.

### Scan 03 — Extension marketplace runtime

**Retained**

- filesystem/provider existence is runtime authority
- database state can drift from runtime state
- installer integrity and rollback concerns
- modern installer is the only acceptable path

**Revised**

- Required package family is six extensions.
- Add-on manifests must include effective transitive contributions.
- Parent/add-on dependency health must be represented.

### Scan 04 — Menu and dashboard hooks

Menus should be contributed per installed WorkCore add-on:

```text
WorkCore
Business Network
Commercial
Work Operations
Property Operations
Workforce Assurance
```

Do not expose menus for modules not loaded by the registry.

### Scan 05 — Authentication and user identity

MagicAI owns authentication. WorkCore uses the MagicAI user identity but owns operational company membership. WorkCore route guards must use Passport-compatible middleware.

### Scan 06 — Teams, companies and tenancy

MagicAI Company is not the WorkCore operational tenant. The parent owns `tz_companies`, memberships and tenant context. Every add-on inherits that context.

### Scan 07 — Roles and permissions

MagicAI roles remain platform roles; WorkCore roles remain operational roles.

New confirmed defect: the repository currently returns undefined `WorkCoreAccessLevel::Manage`.

### Scan 08 — Plans, subscriptions, credits and billing

MagicAI owns SaaS plans and shared AI credits. Map plans to WorkCore capabilities per add-on and module. WorkCore Commercial owns operational finance, not SaaS subscription billing.

### Scans 09–10 — CRM

WorkCore Business Network is the operational CRM implementation that replaces or subsumes host CRM behaviour.

### Scan 11 — Sales, estimates, invoices and payments

WorkCore Commercial contains the operational finance implementation. MagicAI billing remains platform-only. The completion listener and action-key mismatch must be fixed.

### Scan 12 — Customer lifecycle

Business Network owns customer lifecycle, Work Operations owns service execution, Commercial owns operational money, and Property Operations owns premises context.

### Scan 13 — AI providers and model routing

MagicAI remains the preferred provider/model authority. Business Network includes a native AI orchestrator and tool catalogue which should consume MagicAI credential/model and credit adapters. Current WorkCore AI config loading must be corrected.

### Scan 14 — AI Chat runtime and context hooks

MagicAI AI Chat remains the user-facing conversation surface. WorkCore contributes authoritative context and tools through adapters. Tool execution must pass through WorkCore governance, not repositories.

## Repository-backed combined series

The following documents are now authoritative for integration decisions:

```text
15-workcore-magicai-combined-integration-architecture.md
16-workcore-extension-by-extension-magicai-contracts.md
17-workcore-magicai-install-activation-and-release-plan.md
18-workcore-magicai-confirmed-blockers-and-remediation.md
19-workcore-magicai-document-reconciliation-and-source-authority.md
```

## Documentation rule going forward

Every future integration scan must inspect all three layers:

```text
MagicAI host implementation
+
relevant WorkCore extension implementation
+
the adapter and authority boundary between them
```

A host-only or WorkCore-only scan is incomplete unless it is explicitly labelled as a component audit.
