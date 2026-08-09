# Titan Migration Engine Tenancy Model Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the tenant-scoped persistence, authorization, run-state and queue-safe company context required by Migration Engine issue #378.

**Architecture:** Every Migration Engine record is bounded by `company_id`. HTTP requests may resolve that boundary from the authenticated user's `active_company_id`; queued work must explicitly enter `MigrationTenantContext` before querying models. A shared tenant model trait applies the company scope and auto-fills `company_id`. Run transitions are centralized in an enum/state-machine pair, while project actions are protected by company-bound policies and explicit permission slugs.

**Tech Stack:** PHP 8.2+, Laravel 10, Eloquent, Laravel migrations, GitHub Actions contract tests.

## Global Constraints

- Preserve `app/extensions/Migration`, `App\\Extensions\\Migration`, `MigrationServiceProvider`, config namespace `migration`, legacy route names and the `davinci` source value.
- Preserve existing legacy migrations and `old_sys_user_id` / `old_sys_plan_id` compatibility columns.
- `company_id` is the primary tenant boundary for all new persistence.
- Do not serialize connection credential references.
- Do not introduce non-null timestamps that rely on implicit MySQL defaults.
- New migrations must be safe on installations where the tables already exist.

---

### Task 1: Lock the contract with a failing standalone test

**Files:**
- Create: `app/extensions/Migration/tests/MigrationTenancyContractTest.php`
- Create: `.github/workflows/migration-engine-tenancy.yml`

**Interfaces:**
- Consumes: issue #378 acceptance criteria.
- Produces: a no-framework contract test for state transitions, company boundary behavior, schema coverage, secret hiding, tenant model wiring and permissions.

- [ ] **Step 1: Write the failing test** that requires the new state enum and company-boundary class, checks all required table/model paths, and verifies encrypted/hidden credential-reference configuration.
- [ ] **Step 2: Open a draft PR and run CI.** Expected: FAIL because the new tenancy/state files do not exist yet.
- [ ] **Step 3: Keep the exact failing test unchanged while implementing Tasks 2-6.**

### Task 2: Add queue-safe tenant context and model scope

**Files:**
- Create: `app/extensions/Migration/System/Tenancy/MigrationTenantContext.php`
- Create: `app/extensions/Migration/System/Tenancy/MigrationTenantResolver.php`
- Create: `app/extensions/Migration/System/Tenancy/Concerns/BelongsToMigrationCompany.php`
- Create: `app/extensions/Migration/System/Models/TenantMigrationModel.php`
- Modify: `app/extensions/Migration/System/MigrationServiceProvider.php`

**Interfaces:**
- Produces: `MigrationTenantContext::run(int $companyId, callable $callback)`, `MigrationTenantResolver::companyId(): ?int`, and a global Eloquent company scope.

- [ ] **Step 1:** Implement an in-memory explicit company context with nested-context restoration via `try/finally`.
- [ ] **Step 2:** Resolve company in priority order: explicit queue context, authenticated `active_company_id`, configured default.
- [ ] **Step 3:** Add an Eloquent trait that scopes by resolved company, auto-fills `company_id` on create, and exposes `forCompany()` / `withoutCompanyScope()`.
- [ ] **Step 4:** Register the context and resolver as singletons in `MigrationServiceProvider`.

### Task 3: Create tenant-scoped persistence schema

**Files:**
- Create: `app/extensions/Migration/database/migrations/2026_08_09_180800_create_titan_migration_engine_core_tables.php`

**Interfaces:**
- Produces tables: `ext_migration_projects`, `ext_migration_connections`, `ext_migration_entity_plans`, `ext_migration_mappings`, `ext_migration_runs`, `ext_migration_steps`, `ext_migration_checkpoints`, `ext_migration_external_ids`, `ext_migration_record_results`, `ext_migration_conflicts`, `ext_migration_failures`, `ext_migration_artifacts`, `ext_migration_templates`, `ext_migration_audit_events`.

- [ ] **Step 1:** Add `company_id` plus company-first indexes to every table; add optional `user_id` and `team_id` where ownership is meaningful.
- [ ] **Step 2:** Use `Schema::hasTable` guards so upgrades are idempotent at table creation level.
- [ ] **Step 3:** Use nullable operational timestamps (`started_at`, `completed_at`, `approved_at`, etc.) to avoid MySQL implicit-default failures.
- [ ] **Step 4:** Drop tables in dependency-safe reverse order in `down()` without touching legacy Migration tables/columns.

### Task 4: Add models and relationships

**Files:**
- Create model classes under `app/extensions/Migration/System/Models/` for all 14 persistence tables.

**Interfaces:**
- All models extend `TenantMigrationModel` and therefore inherit company isolation.
- `MigrationConnection::$hidden` contains `credential_reference` and casts it as `encrypted`.
- `MigrationRun::$casts['state']` maps to `MigrationRunState`.

- [ ] **Step 1:** Add project/connection/entity-plan/mapping/run/step/checkpoint models with parent-child relations.
- [ ] **Step 2:** Add external-ID/result/conflict/failure/artifact/template/audit models with casts and indexes matching their migration columns.
- [ ] **Step 3:** Hide encrypted credential references from serialization.

### Task 5: Add run state machine and authorization boundary

**Files:**
- Create: `app/extensions/Migration/System/Enums/MigrationRunState.php`
- Create: `app/extensions/Migration/System/Enums/MigrationPermission.php`
- Create: `app/extensions/Migration/System/Authorization/CompanyBoundary.php`
- Create: `app/extensions/Migration/System/Policies/MigrationProjectPolicy.php`
- Create: `app/extensions/Migration/System/Services/MigrationRunStateMachine.php`

**Interfaces:**
- `MigrationRunState::canTransitionTo(MigrationRunState $target): bool`
- `MigrationRunStateMachine::transition(MigrationRun $run, MigrationRunState $target): MigrationRun`
- Permissions: `migration.view`, `migration.configure`, `migration.approve`, `migration.execute`, `migration.resolve`, `migration.rollback`, `migration.purge`.

- [ ] **Step 1:** Define explicit run transitions with terminal-state protection.
- [ ] **Step 2:** Throw `LogicException` on invalid transitions; set lifecycle timestamps when entering running/completed/failed/cancelled/rolled-back states.
- [ ] **Step 3:** Enforce same-company access before checking the action permission.

### Task 6: Green verification and integration

**Files:**
- Modify: `.github/workflows/migration-engine-tenancy.yml` only if verification needs correction; do not weaken assertions.

- [ ] **Step 1:** Run the standalone contract test. Expected: PASS.
- [ ] **Step 2:** Run `php -l` over every PHP file in `app/extensions/Migration`. Expected: no syntax errors.
- [ ] **Step 3:** Confirm the PR diff does not remove or rename legacy files/routes/config/provider identifiers.
- [ ] **Step 4:** Merge only after the dedicated Migration Engine tenancy workflow is green; close #378 as completed and update epic #376.