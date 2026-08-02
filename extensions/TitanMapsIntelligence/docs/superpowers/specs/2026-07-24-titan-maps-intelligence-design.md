# Titan Maps Intelligence — Approved Design

## Status

Approved for implementation from the user's supplied build prompt on 2026-07-24.

## Objective

Build an original, provider-neutral Titan Zero integration extension for business/place discovery, provider and supplier sourcing, candidate staging, provenance, matching, territory analysis, and governed promotion into WorkCore.

The proprietary Google Maps Scraper executable is a functional reference only. No executable code, decompilation output, selectors, vendor telemetry, or proprietary implementation will be included.

## Authority and boundaries

- Meetup/Titan Zero host owns authentication, active-company resolution, membership, permissions, queues, notifications, files, conversations, and extension discovery.
- WorkCore owns customers, leads, providers, suppliers, contractors, properties, jobs, and all operational records.
- Titan Maps Intelligence is an optional SDK v2 `integration` extension.
- The extension stages external observations and candidates. It never writes WorkCore tables directly.
- Titan Vault or a host secret resolver owns API credentials. The extension stores credential references only.
- Missing host contracts are explicit integration dependencies. The extension fails closed rather than inventing tenant authority.

## Host compatibility reality

The supplied Meetup application is Laravel 12 but currently lacks canonical company, membership, active-company, permission, extension-registry, and Titan Vault contracts. Therefore this package will:

1. implement the extension against explicit host-facing contracts;
2. ship test fakes, not production fallbacks;
3. document the required host adapters;
4. avoid editing `bootstrap/providers.php` as a production install method;
5. remain independently testable and ready for the future canonical host registry.

The supplied WorkCore source exposes CRM actions such as `CreateLead` and `CreateCustomer`, but no stable external-candidate promotion contract is authoritative. The extension will use a `WorkCoreCandidateGateway` contract and ship an adapter blueprint identifying the target WorkCore actions. It will not duplicate WorkCore models.

## Extension structure

The package follows Titan Zero Extension SDK v2:

- `extension.json`
- `src/TitanMapsIntelligenceServiceProvider.php`
- `config/titan_maps_intelligence.php`
- `routes/api.php`
- `database/migrations/`
- `src/Contracts/`
- `src/DTO/`
- `src/Enums/`
- `src/Events/`
- `src/Exceptions/`
- `src/Http/Controllers/`
- `src/Http/Requests/`
- `src/Jobs/`
- `src/Models/`
- `src/Policies/`
- `src/Providers/`
- `src/Repositories/`
- `src/Services/`
- `src/Support/`
- `src/Tools/`
- `tests/`
- `docs/`

## Core components

### Host contracts

- `AuthorisedCompanyContext`: returns the authenticated, membership-verified company scope.
- `PermissionAuthorizer`: enforces namespaced permissions.
- `SecretResolver`: resolves a Titan Vault credential reference without exposing it to persistence or logs.
- `AuditRecorder`: records security and operational actions.
- `CapabilityRegistrar`: exposes Quattro tools to the canonical Titan capability registry.
- `WorkCoreCandidateGateway`: promotes approved candidates through WorkCore services.

All production contracts are mandatory for affected capabilities and fail closed when absent.

### Provider adapters

- `PlacesProvider` defines text search, nearby search, place details, capabilities, attribution, and usage.
- `GooglePlacesProvider` implements Places API (New) through a small HTTP transport contract.
- `FakePlacesProvider` exists only in tests.
- The Google adapter uses field masks, bounded timeouts, no wildcard production mask, normalised errors, and credential references.

### Search lifecycle

`Draft → Queued → Running → Paused|PartiallyCompleted|Completed|Failed|Cancelled`

A durable `discovery_searches` record is paired with one or more `discovery_runs`. Each run records provider, cursor/checkpoint, progress, retries, safe errors, and usage. Queue stages are idempotent and cancellation-aware.

### External observations and provenance

`external_places` stores normalised source observations. `external_place_contacts` stores public contact observations. `field_observations` stores field-level source, observation time, confidence, restrictions, and expiry.

Observed values, AI classifications, human confirmations, and WorkCore authoritative data remain distinct.

### Candidate governance

`discovery_candidates` classifies observations without changing them. `candidate_matches` records possible WorkCore matches and conflicts. `candidate_promotions` records approval, accepted fields, WorkCore command/event IDs, and lineage.

Ambiguous matches are never silently merged. Promotion requires permission, approval or explicit policy, adequate confidence, and no unresolved duplicate.

### Matching and ranking

Deterministic matching signals:

1. provider/place ID;
2. business number when available;
3. phone;
4. domain;
5. email;
6. address;
7. geographic proximity;
8. name similarity;
9. category compatibility.

Ranking provides a transparent score breakdown and never relies solely on opaque AI output.

### Territory analysis

The first version computes deterministic summary metrics from stored observations: category counts, distance bands, coverage by region labels, source coverage, and confidence distribution. Advanced map rendering and demand prediction remain host/UI follow-on work.

### API and Titan tools

Protected API endpoints and Quattro capabilities cover:

- create/read/cancel searches;
- list/read candidates;
- match/classify/approve/reject candidates;
- request promotion;
- read usage;
- generate territory summaries.

Controllers receive company scope only from `AuthorisedCompanyContext`, not request data.

## Error handling

Stable internal codes include provider-not-configured, authentication failure, rate limit, unsupported filter, unresolved location, limit exceeded, tenant denied, permission denied, duplicate candidate, ambiguous match, promotion validation failure, and provider-terms restriction.

Provider response bodies, API keys, and raw exceptions are never returned to users or logs.

## Security

- fail-closed host context and permission checks;
- credential references only;
- bounded HTTP timeout/retry behavior;
- allowlisted provider base URL;
- no arbitrary URL fetch capability;
- parameterised Eloquent/query-builder persistence;
- company scope on all company-owned tables;
- immutable audit/event metadata with correlation and causation IDs;
- safe export design documented, with export implementation deferred unless host private-file contracts are present;
- no scraping, embedded browser, vendor update check, or vendor telemetry.

## Database strategy

Migrations use Laravel schema types portable across SQLite and PostgreSQL. Latitude/longitude use decimal columns with conventional indexes because portable spatial support is not guaranteed. JSON columns carry provider metadata and structured observations. Foreign keys are internal to the extension; WorkCore target IDs are polymorphic references without cross-package database constraints.

## Testing strategy

Because the supplied host does not include installed Composer dependencies, the package will include:

- pure-PHP unit tests for normalisation, matching, ranking, error mapping, DTO validation, and Google request construction;
- manifest/schema validation through the SDK generator test and custom checks;
- PHP syntax verification for every file;
- Laravel/Pest test specifications ready to run once installed in the host;
- security test definitions for tenancy, permissions, secret leakage, SSRF boundaries, and cross-company isolation.

No test will be claimed as executed unless actual output is recorded.

## Initial implementation scope

Implemented in v1.0.0:

- complete extension package and manifest;
- portable database schema;
- host-facing contracts and fail-closed service wiring;
- Google Places API (New) text, nearby, and detail adapter;
- search orchestration services and queue jobs;
- normalisation, provenance, deduplication, matching, ranking, candidate lifecycle, promotion gateway;
- events, controllers, request validation, policies, AI tool definitions;
- documentation and verification scripts;
- tests runnable without external credentials plus Laravel test blueprints.

Explicit integration gaps:

- production extension registry in Meetup host;
- canonical active-company and permission contracts;
- Titan Vault implementation;
- canonical WorkCore candidate-promotion API;
- Meetup progress-card UI;
- offline device sync engine;
- private signed export/file service.

These gaps are documented rather than hidden inside unsafe extension-owned replacements.

## Completion criteria

The package is complete when its manifest validates, all PHP files lint, pure unit tests pass, no proprietary donor code exists, Google request construction matches the current official Places API endpoints, tenancy and permission boundaries fail closed, and the final ZIP contains code, tests, migrations, and exact integration documentation.
