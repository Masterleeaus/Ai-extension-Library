# WorkCore Dynamic Pricing Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver issue #353 as a tenant-safe dynamic pricing capability inside the existing WorkCore Commercial/Finance authority and merge it through a focused, verified pull request.

**Architecture:** Pricing remains part of the existing `workcore-commercial` package and uses the existing `workcore.finance` entitlement. Runtime code lives beneath `System/Modules/Finance/Pricing`; schema migrations remain owned by `workcore-shared-foundation`. Pricing recommendations are deterministic, previewable and auditable; persistence is tenant scoped and all consequential writes use registered WorkCore actions.

**Tech Stack:** PHP 8.2, Laravel 10, WorkCore BusinessActionRegistry/ReadModelRegistry, query-builder repositories, Python source-contract tests, GitHub Actions complete-package validation.

## Global Constraints

- Never merge the stale `claude/ai-suite-extensions-issues-zx5dae` branch.
- Preserve `App\\Domains\\WorkCore` canonical namespaces.
- Do not create a seventh WorkCore package or duplicate Finance authority.
- Every persisted record requires `company_id` and tenant-scoped access.
- Monetary amounts use integer minor units; multipliers use bounded decimal strings/floats only at calculation boundaries.
- Preview operations are read-only; apply operations persist an immutable price-history record.
- Migrations are forward-only and parent/shared-foundation owned.
- Package checksum and native owned-table metadata must be regenerated deterministically.
- Issue #353 closes only after the focused PR is merged and the merged `main` commit is verified.

---

### Task 1: Add the executable pricing contract test

**Files:**
- Create: `app/extensions/WorkCore_Platform/tests/test_workcore_dynamic_pricing.py`

**Interfaces:**
- Consumes: existing WorkCore package layout and registries.
- Produces: source and standalone-calculator contract for all later tasks.

- [ ] Add failing assertions for canonical package ownership, parent-owned migration, tenant-scoped repository queries, governed actions/read models, finance entitlement, API/dashboard routes, deterministic rule priority, seasonal/demand/occupancy factors, bounds and immutable history.
- [ ] Run the focused test and confirm RED because Pricing files do not exist on current `main`.

### Task 2: Implement the deterministic pricing calculator

**Files:**
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Domain/DynamicPriceCalculator.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/DTO/PricingDecision.php`

**Interfaces:**
- Consumes: base price in minor units and normalized factor arrays.
- Produces: `DynamicPriceCalculator::calculate(array $input): PricingDecision`.

- [ ] Implement deterministic priority ordering, fixed/percentage/multiplier rules, seasonal combination, occupancy tiers, demand score multiplier, min/max bounds and explainable factor output.
- [ ] Execute the calculator from the Python contract test through PHP and confirm GREEN for pure calculations.

### Task 3: Add tenant-scoped persistence and schema

**Files:**
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Contracts/PricingRepositoryContract.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Infrastructure/DatabasePricingRepository.php`
- Create: `packages/workcore-shared-foundation/src/Domains/WorkCore/Database/Migrations/2026_08_05_180000_create_workcore_dynamic_pricing_tables.php`

**Interfaces:**
- Consumes: active company ID and normalized pricing records.
- Produces: rule/rate/indicator/occupancy reads and immutable decision/history writes.

- [ ] Create `tz_pricing_rules`, `tz_seasonal_rates`, `tz_demand_indicators`, `tz_occupancy_snapshots`, `tz_competitor_price_snapshots` and `tz_price_history` with company indexes and safe uniqueness constraints.
- [ ] Make every repository query require and constrain `company_id`.
- [ ] Add aggregation methods for pricing analytics and demand scoring.

### Task 4: Register governed pricing actions and read models

**Files:**
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Actions/PreviewDynamicPrice.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Actions/ApplyDynamicPrice.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Actions/UpsertPricingRule.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Actions/RecordPricingSignal.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Actions/GetPricingAnalytics.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/WorkPricingServiceProvider.php`
- Modify: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/WorkCoreFinanceServiceProvider.php`

**Interfaces:**
- Consumes: WorkCore operation context and repository/calculator services.
- Produces: registered action keys `workcore.pricing.*` and read-model keys for preview/analytics.

- [ ] Register repository/calculator bindings.
- [ ] Register write actions with risk, idempotency, `workcore.finance` entitlement and finance permissions.
- [ ] Register preview and analytics as read models.
- [ ] Register the Pricing provider from the existing Finance provider.

### Task 5: Add API and dashboard surfaces

**Files:**
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/Http/PricingController.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/routes/api.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/routes/user.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/resources/views/dashboard.blade.php`
- Create: `packages/workcore-commercial/src/Domains/WorkCore/System/Modules/Finance/Pricing/config/pricing.php`

**Interfaces:**
- Consumes: registered WorkCore actions/read models.
- Produces: preview, apply, rule, signal and analytics endpoints plus an entitlement-gated dashboard.

- [ ] Validate all request inputs and use active company context only.
- [ ] Keep preview read-only and require idempotency for apply/signal writes.
- [ ] Render current price movement, factor mix and revenue-impact summaries without legacy frontend dependencies.

### Task 6: Regenerate integrity metadata and verify packages

**Files:**
- Modify generated: package `files.sha256.json` manifests.
- Modify generated: `native-extensions/WorkCore/extension.manifest.json` owned tables.
- Modify: `ownership-manifest.json` and validator expected file count if required by current repository rules.

**Interfaces:**
- Consumes: completed source tree.
- Produces: deterministic package metadata accepted by repository validators.

- [ ] Run focused pricing tests.
- [ ] Run WorkCore repository integrity tests and validator.
- [ ] Run PHP syntax validation over all package PHP files.
- [ ] Run native extension build and complete Laravel host fixtures.
- [ ] Remove the temporary source-export workflow before final review.

### Task 7: Review, merge and close issue

**Files:**
- Update: pull-request description and issue #353 evidence comment.

**Interfaces:**
- Consumes: green CI and reviewed focused diff.
- Produces: merged `main` commit and completed issue.

- [ ] Confirm the PR contains no unrelated stale-branch changes.
- [ ] Mark ready, merge with expected head SHA, verify merged-main checks.
- [ ] Close #353 with the merged commit, test evidence, limitations and rollback notes.
