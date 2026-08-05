# Titan Migration Engine Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Import the Migration donor and add a connector registry that exposes Davinci without breaking the legacy workflow.

**Architecture:** Keep the current driver service as a compatibility layer. Add a parallel connector metadata and source contract layer, registered through the existing Laravel service provider, so later issues can implement discovery and streaming without coupling sources to destination models.

**Tech Stack:** PHP 8.2, Laravel 10 service container, dependency-free PHP verification script.

## Global Constraints

- Preserve `app/extensions/Migration` and `App\Extensions\Migration`.
- Preserve `MigrationServiceProvider`, config namespace `migration`, legacy route names and `davinci`.
- Do not modify the legacy `/migrate` production write path in this issue.
- Write tests before production connector classes.

---

### Task 1: Import donor and write failing registry test

**Files:**
- Import: `app/extensions/Migration/**`
- Create: `app/extensions/Migration/tests/ConnectorRegistryTest.php`

- [x] Write a test that requires the not-yet-existing connector classes.
- [x] Run `php app/extensions/Migration/tests/ConnectorRegistryTest.php`.
- [x] Confirm failure is caused by missing `ConnectorDefinition.php`.

### Task 2: Implement connector metadata and registry

**Files:**
- Create: `System/Connectors/ConnectorDefinition.php`
- Create: `System/Connectors/Contracts/MigrationSourceConnectorInterface.php`
- Create: `System/Connectors/ConnectorRegistry.php`
- Create: `System/Connectors/Exceptions/DuplicateConnectorException.php`
- Create: `System/Connectors/Exceptions/UnknownConnectorException.php`

- [x] Implement validated immutable definitions.
- [x] Implement deterministic registration, lookup and definition listing.
- [x] Reject duplicate and unknown keys with dedicated exceptions.
- [x] Run the focused test and confirm it passes.

### Task 3: Register Davinci without breaking the old service

**Files:**
- Create: `System/Connectors/Legacy/LegacyDavinciConnector.php`
- Modify: `System/MigrationServiceProvider.php`

- [x] Add a legacy adapter exposing truthful capability metadata.
- [x] Refuse unimplemented discovery and streaming operations explicitly.
- [x] Register `ConnectorRegistry` as a singleton.
- [x] Keep `MigrationService` registration unchanged.

### Task 4: Brand, document and verify

**Files:**
- Modify: `extension.json`
- Modify: `README.md`
- Create: `docs/superpowers/specs/2026-08-05-titan-migration-engine-foundation-design.md`
- Create: `docs/superpowers/plans/2026-08-05-titan-migration-engine-foundation.md`

- [x] Update customer-facing manifest identity only.
- [x] Document compatibility and the intentionally retained legacy risk.
- [x] Run the focused test.
- [x] Run PHP syntax checks across the extension.
