# WorkCore Issue #353 Verification Record

## Scope

This branch implements the Dynamic Pricing Engine inside the existing WorkCore Commercial/Finance authority. It does not import or merge the stale `claude/ai-suite-extensions-issues-zx5dae` branch.

## Architecture verified

- Runtime owner: `workcore-commercial`
- Migration owner: `workcore-shared-foundation`
- Entitlement boundary: `workcore.finance`
- Tenant key: `company_id`
- Money representation: integer minor units
- Governed writes: WorkCore `BusinessActionRegistry`
- Governed reads: WorkCore `ReadModelRegistry`
- Installable output: the existing six-extension WorkCore release; no seventh package was introduced

## Implemented behavior

- Deterministic priority-based pricing rules
- Fixed-minor, percentage and multiplier adjustments
- Seasonal rate windows and governed seasonal-rate writes
- Occupancy-tier pricing with neutral behavior when occupancy is unknown
- Demand-score pricing
- Minimum and maximum price bounds
- Explainable factors and deterministic decision hashes
- Tenant-scoped rules, demand indicators, occupancy snapshots, competitor snapshots and immutable price history
- Target-scoped competitor analytics and revenue-impact summaries
- Preview, apply, rule, seasonal-rate, signal and analytics operations
- Finance-entitled API routes and catalogue-driven MagicAI dashboard navigation
- Domain validation shared by HTTP, AI, CLI and governed-action callers

## Verified evidence

Final workflow run `30994047447` passed every required stage on a tree first merged with current `main`:

- Source patch SHA-256 verification
- Ownership/checksum regeneration from the merged tree
- 32 focused Pricing, workspace and integrity tests
- Repository validator with 6 packages, 35 modules and 2,178 owned files
- Complete package PHP lint
- Six deterministic MagicAI installer builds and extension validation
- Generated PHP lint
- Real Laravel 10 host creation and full WorkCore installation
- Fresh database migration including all six pricing tables
- Entitlement-projection database fixture
- Workspace/menu synchronization fixture, including retained disabled legacy rows
- Direct pricing database exercise proving neutral missing occupancy, seasonal persistence, target-isolated competitor analytics, one dashboard route and rejection of invalid non-HTTP payloads

## Repository-wide CI note

The repository's separate `Materialize AI extensions` workflow currently fails before WorkCore validation because its external base-library Parquet transport returns HTTP 404. The focused WorkCore workflow does not depend on that unavailable transport and completed all source, package, installer and real-host checks.
