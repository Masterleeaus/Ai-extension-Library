# Migration Engine Canonical Mapping Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the canonical Titan destination registry, deterministic mapping/transformation runtime, tenant-and-connection-scoped external IDs, duplicate policies and approval-gated AI mapping suggestions required by issue #380.

**Architecture:** Source connectors remain read-only and cannot import Titan application models. Canonical entity definitions and destination handlers are registered separately, while a pure transformation engine converts source rows into validated canonical payloads with an audit trace. External IDs, duplicate decisions and advisory mapping suggestions are governed by dedicated services so execution in #381 can consume deterministic decisions without bypassing the destination layer.

**Tech Stack:** PHP 8.3, Laravel 10 service provider/model integration, standalone PHP contract tests, GitHub Actions.

## Global Constraints

- Preserve `app/extensions/Migration`, `App\Extensions\Migration`, `MigrationServiceProvider`, config namespace `migration`, legacy routes and `davinci` compatibility.
- Source connectors must remain read-only and must not reference Titan Eloquent models directly.
- All transformations must be declarative, deterministic, previewable and auditable; no `eval`, dynamic PHP callbacks or arbitrary class execution in DSL input.
- Required destination fields and identity fields must fail closed when absent.
- External-ID lookups must include `company_id` and `connection_id`.
- Fuzzy duplicates above the approved risk threshold must resolve to review, never automatic merge.
- AI-assisted suggestions are advisory, masked and unapproved by default; application requires an explicit approval actor.

---

### Task 1: Contract-first canonical layer

**Files:**
- Create: `app/extensions/Migration/tests/MigrationCanonicalMappingContractTest.php`
- Create: `.github/workflows/migration-engine-canonical-mapping.yml`

**Produces:** A failing contract covering registry extensibility, built-in entity packs, required-field validation, deterministic DSL behavior, stable checksums, duplicate risk gates, masked advisory suggestions, external-ID scoping and connector isolation.

- [ ] Write the standalone contract before production classes exist.
- [ ] Open a draft PR and capture RED CI caused by the first missing canonical runtime class.

### Task 2: Canonical entity and destination registries

**Files:**
- Create: `System/Canonical/CanonicalEntityDefinition.php`
- Create: `System/Canonical/CanonicalEntityRegistry.php`
- Create: `System/Canonical/BuiltinCanonicalEntityCatalog.php`
- Create: `System/Canonical/Contracts/CanonicalEntityPackInterface.php`
- Create: `System/Destination/Contracts/DestinationHandlerInterface.php`
- Create: `System/Destination/DestinationHandlerRegistry.php`
- Create: `System/Destination/DestinationWriteContext.php`
- Create: `System/Destination/DestinationWriteResult.php`
- Create: `System/Canonical/ModuleAvailabilityResolver.php`

**Interfaces:**
- `CanonicalEntityRegistry::register(CanonicalEntityDefinition $definition): void`
- `CanonicalEntityRegistry::get(string $key): CanonicalEntityDefinition`
- `CanonicalEntityRegistry::available(ModuleAvailabilityResolver $modules): array`
- `DestinationHandlerRegistry::register(string $entityKey, DestinationHandlerInterface $handler): void`

- [ ] Implement strict entity keys, field schemas, required fields, identity rules, dependencies and required module declarations.
- [ ] Seed universal entities and vertical packs for field service, accommodation, real estate, salon, fitness, automotive, e-commerce, hire/rental and capacity booking.
- [ ] Keep `register()` public so external vertical modules can add definitions without editing Migration core.

### Task 3: Deterministic transformation DSL and preview

**Files:**
- Create: `System/Mapping/TransformationDefinition.php`
- Create: `System/Mapping/TransformationRegistry.php`
- Create: `System/Mapping/TransformationEngine.php`
- Create: `System/Mapping/MappingRule.php`
- Create: `System/Mapping/MappingPlan.php`
- Create: `System/Mapping/MappingPreviewService.php`
- Create: `System/Mapping/MappingValidationException.php`

**Interfaces:**
- `TransformationEngine::apply(mixed $value, array $steps, array $source = []): array{value:mixed,audit:array}`
- `MappingPreviewService::preview(CanonicalEntityDefinition $entity, MappingPlan $plan, array $source): array`

- [ ] Register only pure built-ins (`trim`, casing, scalar conversion, coalesce, concat, map-values, regex-replace, date-format).
- [ ] Reject unknown transforms and malformed configurations.
- [ ] Produce per-field audit entries with transform key and before/after digests.
- [ ] Validate required and identity fields after transformation.

### Task 4: External IDs and deterministic checksums

**Files:**
- Create: `System/Identity/CanonicalChecksum.php`
- Create: `System/Identity/ExternalIdScope.php`
- Create: `System/Identity/ExternalIdMapService.php`
- Create: `System/Identity/Contracts/ExternalIdRepositoryInterface.php`
- Create: `System/Identity/EloquentExternalIdRepository.php`
- Modify: `System/Models/MigrationExternalId.php`
- Create: `database/migrations/2026_08_12_115300_scope_migration_external_ids_to_connections.php`

- [ ] Canonically sort associative payload keys before SHA-256 hashing.
- [ ] Require positive company and connection IDs in `ExternalIdScope`.
- [ ] Add `connection_id` to persistence and replace the legacy source uniqueness rule with company/project/connection/source uniqueness.
- [ ] Keep all repository lookups tenant and connection scoped.

### Task 5: Duplicate governance and AI advisory suggestions

**Files:**
- Create: `System/Matching/DuplicatePolicy.php`
- Create: `System/Matching/DuplicateDecision.php`
- Create: `System/Matching/DuplicateResolver.php`
- Create: `System/Suggestions/MappingSuggestion.php`
- Create: `System/Suggestions/AdvisoryMappingSuggestionService.php`
- Create: `System/Suggestions/MappingSuggestionApprovalService.php`

- [ ] Implement create/update/merge/skip/review policies.
- [ ] Permit exact identity decisions separately from fuzzy matching.
- [ ] Force fuzzy matches above configured risk threshold to `review` even if policy requests merge.
- [ ] Mask source evidence through the existing `SensitiveValueMasker`.
- [ ] Return AI suggestions as `advisory` + `requires_approval=true` and require a positive approval actor before an approved suggestion can be consumed.

### Task 6: Laravel integration and regression verification

**Files:**
- Modify: `System/MigrationServiceProvider.php`
- Modify: `config/migration.php`

- [ ] Register canonical/destination/transform/external-ID services as singletons without changing legacy driver construction.
- [ ] Run the unchanged #380 contract and full Migration PHP lint.
- [ ] Run #379 connectors, #378 tenancy and #377 foundation workflows on the exact final head.
- [ ] Review the branch diff for unrelated drift.
- [ ] Mark PR ready, squash merge to `main`, confirm issue #380 is closed and update epic #376.
