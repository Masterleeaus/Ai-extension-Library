# Titan Maps Intelligence Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a production-oriented Titan Zero SDK v2 integration extension for provider-neutral place discovery, governed candidate staging, matching, provenance, territory analysis, and WorkCore promotion.

**Architecture:** The extension consumes host-owned authorisation, secret, audit, capability, queue, and WorkCore gateway contracts. It stores only extension-owned external observations and workflow state, uses Google Places API (New) as the first adapter, and fails closed where the supplied Meetup host lacks canonical contracts.

**Tech Stack:** PHP 8.2+, Laravel 12-compatible Illuminate components, Titan Zero Extension SDK v2, SQLite/PostgreSQL-portable migrations, Laravel HTTP client abstraction, Pest-ready tests plus a dependency-free PHP verification runner.

## Global Constraints

- Extension type is exactly `integration`.
- No proprietary donor implementation, selectors, executable content, decompilation output, vendor telemetry, or embedded browser code.
- Request-supplied company IDs are never authority.
- WorkCore tables are never written directly.
- API credentials are resolved through a host secret contract and never persisted as plaintext.
- External HTTP calls use allowlisted endpoints, bounded timeouts, bounded retries, field masks, and normalised errors.
- All company-owned records include `company_id`; optional `branch_id` and `workspace_id` are context metadata.
- Database migrations remain portable across SQLite and PostgreSQL.
- No production fallback is allowed for missing host authorisation or secret contracts.

---

### Task 1: SDK-compliant scaffold and manifest

**Files:**
- Create: `extension.json`
- Create: `composer.json`
- Create: `config/titan_maps_intelligence.php`
- Create: `src/TitanMapsIntelligenceServiceProvider.php`
- Create: `README.md`
- Test: `tests/Unit/ManifestContractTest.php`

**Interfaces:**
- Produces: manifest ID `titan-maps-intelligence`, provider `App\\Extensions\\TitanMapsIntelligence\\TitanMapsIntelligenceServiceProvider`, capability IDs and permission IDs used by all later tasks.

- [ ] Write a manifest test that asserts schema version `2.0`, type `integration`, no duplicate capabilities/permissions, sensitive settings have no secret default, and no prohibited `domain` type.
- [ ] Run the test and observe failure because the manifest is absent.
- [ ] Add the manifest, package autoload metadata, config defaults, and fail-closed service-provider skeleton.
- [ ] Run manifest test and SDK generator tests; expect pass.
- [ ] Commit `feat: scaffold Titan Maps Intelligence extension`.

### Task 2: Core enums, DTOs, errors, and value objects

**Files:**
- Create: `src/Enums/SearchStatus.php`
- Create: `src/Enums/CandidateType.php`
- Create: `src/Enums/CandidateStatus.php`
- Create: `src/Enums/VerificationStatus.php`
- Create: `src/DTO/Coordinates.php`
- Create: `src/DTO/PlaceSearchRequest.php`
- Create: `src/DTO/NearbySearchRequest.php`
- Create: `src/DTO/ExternalPlaceData.php`
- Create: `src/DTO/PlaceSearchPage.php`
- Create: `src/DTO/ProviderCapabilities.php`
- Create: `src/DTO/ProviderUsage.php`
- Create: `src/Exceptions/MapsIntelligenceException.php`
- Create: `src/Exceptions/ProviderException.php`
- Test: `tests/Unit/DtoValidationTest.php`

**Interfaces:**
- Produces: immutable request/response types consumed by providers and services.

- [ ] Write tests for radius bounds, maximum result bounds, coordinate bounds, blank query rejection, and stable error-code exposure.
- [ ] Run tests and observe missing-class failures.
- [ ] Implement immutable DTOs and stable exception codes.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: add maps domain value objects`.

### Task 3: Host and WorkCore contracts

**Files:**
- Create: `src/Contracts/AuthorisedCompanyContext.php`
- Create: `src/Contracts/PermissionAuthorizer.php`
- Create: `src/Contracts/SecretResolver.php`
- Create: `src/Contracts/AuditRecorder.php`
- Create: `src/Contracts/CapabilityRegistrar.php`
- Create: `src/Contracts/WorkCoreCandidateGateway.php`
- Create: `src/Contracts/ProviderHttpTransport.php`
- Create: `src/Exceptions/MissingHostContractException.php`
- Create: `src/Support/RequiredHostContract.php`
- Test: `tests/Unit/FailClosedHostContractTest.php`

**Interfaces:**
- Produces: host-owned integration boundaries. `RequiredHostContract::resolve(string $contract): object` throws `MAPS_HOST_CONTRACT_MISSING` when absent.

- [ ] Write tests proving absent authorisation, permission, secret, and WorkCore contracts fail closed.
- [ ] Run tests and observe failures.
- [ ] Implement contracts and resolver without production null implementations.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: define fail-closed host integration contracts`.

### Task 4: Portable database schema and models

**Files:**
- Create migrations for provider connections, discovery searches, discovery runs, external places, external place contacts, field observations, discovery candidates, candidate matches, candidate promotions, territory analyses, maps usage records, and suppression entries.
- Create corresponding models under `src/Models/`.
- Test: `tests/Feature/DatabaseSchemaTest.php`

**Interfaces:**
- Produces: extension-owned persistence with `company_id` on every company-owned record and internal foreign keys only.

- [ ] Write Laravel feature-test blueprints that migrate SQLite and assert required tables, indexes, unique provider/place identity, and tenant columns.
- [ ] Add portable migrations using strings/UUIDs, decimals for coordinates, JSON for structured data, and indexes for search/matching fields.
- [ ] Add guarded/fillable/cast definitions and relationships.
- [ ] Run PHP lint and static migration inspection; run Laravel test when dependencies are available.
- [ ] Commit `feat: add maps intelligence persistence schema`.

### Task 5: Provider registry and Google Places API adapter

**Files:**
- Create: `src/Contracts/PlacesProvider.php`
- Create: `src/Providers/PlacesProviderRegistry.php`
- Create: `src/Providers/GooglePlacesProvider.php`
- Create: `src/Providers/LaravelProviderHttpTransport.php`
- Create: `src/Support/GooglePlacesNormalizer.php`
- Test: `tests/Unit/GooglePlacesProviderTest.php`

**Interfaces:**
- `PlacesProvider::search(PlaceSearchRequest): PlaceSearchPage`
- `PlacesProvider::nearby(NearbySearchRequest): PlaceSearchPage`
- `PlacesProvider::details(string): ExternalPlaceData`
- Google endpoints: `POST /v1/places:searchText`, `POST /v1/places:searchNearby`, `GET /v1/places/{id}`.

- [ ] Write transport-spy tests for endpoint, POST/GET method, API-key header, field-mask header, timeout, retry cap, and request payload.
- [ ] Write normalisation tests for display name, address, coordinates, phone, website, hours, rating, review count, status, types, and source URI.
- [ ] Implement registry, adapter, transport, and normaliser with allowlisted `https://places.googleapis.com` base URI.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: add Google Places provider adapter`.

### Task 6: Provenance, canonicalisation, and deduplication

**Files:**
- Create: `src/Services/PlaceCanonicalizer.php`
- Create: `src/Services/ProvenanceService.php`
- Create: `src/Services/ExternalPlaceRepository.php`
- Create: `src/Support/NormalizesContactValues.php`
- Test: `tests/Unit/CanonicalizationTest.php`
- Test: `tests/Unit/ProvenanceTest.php`

**Interfaces:**
- `PlaceCanonicalizer::canonicalKey(ExternalPlaceData): string`
- `ProvenanceService::observations(ExternalPlaceData, string $provider): array`
- `ExternalPlaceRepository::upsertObservation(string $companyId, ExternalPlaceData): object`

- [ ] Write tests for deterministic canonical keys, phone/domain/email normalisation, source-field separation, and provider/place exact deduplication.
- [ ] Implement services so raw source values remain immutable observations and AI/human fields are not conflated.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: add external-place provenance and deduplication`.

### Task 7: Search orchestration and durable jobs

**Files:**
- Create: `src/Services/DiscoverySearchService.php`
- Create: `src/Services/DiscoveryRunService.php`
- Create: `src/Jobs/ExecuteDiscoverySearch.php`
- Create: `src/Jobs/ProcessDiscoveryPage.php`
- Create: `src/Events/SearchCreated.php`
- Create: `src/Events/SearchProgressed.php`
- Create: `src/Events/SearchCompleted.php`
- Create: `src/Events/SearchFailed.php`
- Test: `tests/Unit/SearchLifecycleTest.php`
- Test: `tests/Feature/SearchJobTest.php`

**Interfaces:**
- `DiscoverySearchService::create(string $companyId, string $userId, PlaceSearchRequest, array $context): object`
- `DiscoverySearchService::cancel(string $companyId, string $searchId): void`
- Jobs are idempotent, cancellation-aware, provider-rate-limit aware, and checkpoint run state.

- [ ] Write state-machine tests rejecting illegal transitions and proving cancellation/idempotency.
- [ ] Write job-blueprint tests for progress, partial completion, rate-limit retry time, and safe error storage.
- [ ] Implement services, jobs, and events using provider registry and repository interfaces.
- [ ] Run pure lifecycle tests and PHP lint; run Laravel job tests when dependencies are available.
- [ ] Commit `feat: add durable discovery search lifecycle`.

### Task 8: Candidate classification, matching, ranking, and suppression

**Files:**
- Create: `src/Services/CandidateClassificationService.php`
- Create: `src/Services/CandidateMatchingService.php`
- Create: `src/Services/ProviderRankingService.php`
- Create: `src/Services/SuppressionService.php`
- Create: `src/DTO/MatchResult.php`
- Create: `src/DTO/RankedCandidate.php`
- Test: `tests/Unit/CandidateMatchingTest.php`
- Test: `tests/Unit/ProviderRankingTest.php`

**Interfaces:**
- `CandidateMatchingService::score(array $candidate, array $existing): MatchResult`
- `ProviderRankingService::rank(array $candidate, array $context): RankedCandidate`

- [ ] Write tests for exact identity, phone/domain/address signals, geographic distance, name similarity, conflicts, ambiguous thresholds, transparent score breakdown, and suppression exclusion.
- [ ] Implement deterministic weighted scoring with explicit thresholds and no silent merge.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: add governed candidate intelligence`.

### Task 9: Promotion workflow and audit lineage

**Files:**
- Create: `src/Services/CandidatePromotionService.php`
- Create: `src/Events/CandidatePromotionRequested.php`
- Create: `src/Events/CandidatePromoted.php`
- Create: `src/Events/CandidatePromotionFailed.php`
- Test: `tests/Unit/CandidatePromotionServiceTest.php`

**Interfaces:**
- `CandidatePromotionService::promote(string $companyId, string $candidateId, string $targetType, array $acceptedFields, array $executionContext): array`
- Consumes `PermissionAuthorizer`, `WorkCoreCandidateGateway`, and `AuditRecorder`.

- [ ] Write tests proving permission enforcement, company match, approval requirement, unresolved-match rejection, accepted-field allowlisting, WorkCore gateway invocation, audit lineage, and safe failure recording.
- [ ] Implement the promotion service with no direct WorkCore persistence.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: add audited WorkCore promotion workflow`.

### Task 10: Protected API, requests, policies, and resources

**Files:**
- Create: `routes/api.php`
- Create controllers, request validators, policies, and API resources under `src/Http/` and `src/Policies/`.
- Test: `tests/Feature/ApiSecurityTest.php`

**Interfaces:**
- Endpoints: searches create/read/cancel, candidates list/read/classify/approve/reject/match/promote, usage read, territory analyse/read.
- Controllers resolve `companyId` only from `AuthorisedCompanyContext`.

- [ ] Write Laravel feature-test blueprints for authentication, missing company context, permission denial, cross-company isolation, request-company-ID ignoring, safe errors, and disabled extension behavior.
- [ ] Implement route names, middleware aliases as host integration placeholders, controllers, requests, resources, and policies.
- [ ] Run PHP lint and route static checks; run Laravel tests when host contracts exist.
- [ ] Commit `feat: expose protected maps intelligence API`.

### Task 11: Titan Quattro tools and capability registration

**Files:**
- Create: `src/Tools/SearchBusinessesTool.php`
- Create: `src/Tools/GetSearchTool.php`
- Create: `src/Tools/ListCandidatesTool.php`
- Create: `src/Tools/MatchCandidateTool.php`
- Create: `src/Tools/PromoteCandidateTool.php`
- Create: `src/Tools/AnalyseTerritoryTool.php`
- Create: `src/Services/MapsCapabilityService.php`
- Test: `tests/Unit/CapabilitySchemaTest.php`

**Interfaces:**
- Produces manifest-declared Quattro capabilities with deterministic JSON schemas and permission requirements.

- [ ] Write tests that every declared capability has one unique tool schema, namespaced permission, typed input, typed output, and no raw provider payload field.
- [ ] Implement tools as thin delegates into authorised services.
- [ ] Register tools through `CapabilityRegistrar` only when the extension is enabled.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: register Titan Maps Quattro tools`.

### Task 12: Territory analysis and usage metering

**Files:**
- Create: `src/Services/TerritoryAnalysisService.php`
- Create: `src/Services/ProviderUsageService.php`
- Create: `src/Events/TerritoryAnalysisCompleted.php`
- Create: `src/Events/ProviderUsageRecorded.php`
- Test: `tests/Unit/TerritoryAnalysisTest.php`
- Test: `tests/Unit/ProviderUsageTest.php`

**Interfaces:**
- `TerritoryAnalysisService::analyse(string $companyId, array $places, array $scope): array`
- `ProviderUsageService::record(string $companyId, ProviderUsage, array $context): void`

- [ ] Write tests for category counts, distance bands, source coverage, confidence distribution, zero-result handling, and usage/cost aggregation.
- [ ] Implement deterministic summaries without claiming demand certainty.
- [ ] Run tests and expect pass.
- [ ] Commit `feat: add territory summaries and provider metering`.

### Task 13: Documentation, host adapters, verification, and package

**Files:**
- Create: `docs/ARCHITECTURE.md`
- Create: `docs/PROVIDER_ADAPTER_GUIDE.md`
- Create: `docs/WORKCORE_INTEGRATION.md`
- Create: `docs/HOST_INTEGRATION_GAPS.md`
- Create: `docs/SECURITY_AND_PRIVACY.md`
- Create: `docs/API_AND_AI_TOOLS.md`
- Create: `docs/TESTING.md`
- Create: `docs/OPERATIONS.md`
- Create: `docs/GOOGLE_PLACES_TERMS_CHECKLIST.md`
- Create: `docs/EXAMPLE_CONVERSATIONS.md`
- Create: `scripts/verify.php`
- Create: `BUILD_REPORT.md`

**Interfaces:**
- Produces: exact installation and adapter requirements, observed verification results, and final ZIP.

- [ ] Document the missing Meetup registry/tenancy/Vault contracts and the WorkCore gateway adapter required before production installation.
- [ ] Implement a dependency-free verification script covering manifest, prohibited artifacts, PHP lint, duplicate capability/permission IDs, secret strings, route permission declarations, and pure unit tests.
- [ ] Run `php scripts/verify.php`; expect all executable checks to pass.
- [ ] Run `find . -name '*.php' -print0 | xargs -0 -n1 php -l`; expect no syntax errors.
- [ ] Record exact outputs and unexecuted Laravel tests in `BUILD_REPORT.md`.
- [ ] Create `Titan-Maps-Intelligence-v1.0.0-Production-Ready.zip` excluding `.git`, credentials, caches, donor files, and decompilation output.
- [ ] Commit `docs: complete maps intelligence handoff and verification`.
