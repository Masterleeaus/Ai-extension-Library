# Titan Reach Cross-Product Catalogue Contracts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let Titan Reach consume optional authoritative records from Titan products through governed read-only adapters, transform them into source-provenanced `DistributionItem` data, report feed health/rejections, and hand enquiries back without duplicating or mutating canonical source data.

**Architecture:** Add a read-only `CanonicalSourceAdapterContract`, a runtime `CanonicalSourceRegistry`, `config/catalogues.php`, and a `CanonicalCatalogueService`. Reuse `DistributionCapabilityService`, `DistributionItem`, the existing audit ledger and #337 vertical profiles. No source-product model imports and no new persistence table.

**Tech Stack:** PHP 8+/Laravel service container/config, existing SocialMedia extension models/services, GitHub Actions PHP lint/source-contract tests.

## Global Constraints

- Exactly nine canonical vertical families; facilities maintenance stays beneath `field-home-services`.
- Source systems remain authoritative for products, services, properties, vehicles, bookings, hire assets, jobs, customers, inventory, prices and availability.
- No direct cross-extension Eloquent model access.
- No duplicate vertical-specific database in Titan Reach.
- Missing optional products degrade gracefully without fabricated records.
- Every transformed record includes canonical source identifiers, version/provenance and attribution confidence.
- Adapter contract exposes no source mutation method.
- Reuse existing audit ledger and `DistributionItem` persistence.

---

### Task 1: RED source-contract verification

**Files:**
- Create: `app/extensions/SocialMedia/tests/cross_product_catalogue_contract.php`
- Create: `.github/workflows/titan-reach-cross-product-catalogue-verification.yml`

**Interfaces:**
- Consumes: issue #275 requirements and existing #337 config.
- Produces: an executable contract that fails until catalogue config, adapter contract, registry, service and model factory exist.

- [ ] **Step 1: Write the failing source contract**

Assert:

```php
$contract = source('System/Contracts/CanonicalSourceAdapterContract.php');
$registry = source('System/Services/CanonicalSourceRegistry.php');
$service = source('System/Services/CanonicalCatalogueService.php');
$catalogues = source('config/catalogues.php');
$model = source('System/Models/DistributionItem.php');

foreach (['crm','workcore','commerce','marketing','bookings','property','automotive','hire'] as $sourceKey) {
    assertContains("'{$sourceKey}' => [", $catalogues);
}
foreach (['field-home-services','accommodation','real-estate','salons-personal-care','fitness-membership','automotive-services','ecommerce-retail','hire-rental','booking-capacity'] as $vertical) {
    assertContains("'{$vertical}' => [", $catalogues);
}
assertNotContains('function update(', $contract);
assertNotContains('function delete(', $contract);
assertContains('canonical_source', $service);
assertContains('source_version', $service);
assertContains('attribution_confidence', $service);
assertContains('fromCanonicalSource', $model);
```

Also assert missing adapters return bounded unavailable health/reason codes and the service contains explicit tenant/source validation plus audit reuse.

- [ ] **Step 2: Add focused GitHub Actions workflow**

Run the contract and `php -l` over every changed PHP implementation file.

- [ ] **Step 3: Confirm RED**

Expected failure reasons: missing contract/registry/service/config/model factory.

---

### Task 2: Read-only adapter contract and source registry

**Files:**
- Create: `app/extensions/SocialMedia/System/Contracts/CanonicalSourceAdapterContract.php`
- Create: `app/extensions/SocialMedia/System/Services/CanonicalSourceRegistry.php`
- Create: `app/extensions/SocialMedia/config/catalogues.php`
- Modify: `app/extensions/SocialMedia/System/SocialMediaServiceProvider.php`

**Interfaces:**
- Produces `CanonicalSourceAdapterContract` with `sourceKey`, `available`, `supports`, `fetch`, `search`, `health`, `handoff`.
- Produces `CanonicalSourceRegistry::adapter(string $sourceKey): ?CanonicalSourceAdapterContract`, `capabilities(User $user): array`, `health(User $user): array`.

- [ ] **Step 1: Define the read-only interface**

Use only read/health/handoff methods. Do not expose canonical source mutation methods.

- [ ] **Step 2: Add optional source catalogue**

Declare eight source keys with resolver/service identifiers, canonical record types, authoritative fields and bounded handoff types.

Declare generic + exactly nine vertical mappings, representative subtype overrides, source-record aliases and transformation field maps.

- [ ] **Step 3: Implement registry fail-closed resolution**

Resolve configured adapter identifiers through Laravel only if bound/class/interface exists and the resulting instance implements `CanonicalSourceAdapterContract`. Missing/invalid adapters return `null`; no exception is allowed to fabricate data.

- [ ] **Step 4: Register catalogue config**

Merge `config/catalogues.php` under `social-media.catalogues` in the existing SocialMedia service provider.

- [ ] **Step 5: Run contract**

Expected: contract still fails only for missing catalogue service/model factory.

---

### Task 3: Canonical catalogue transformation, health, rejection and handoff

**Files:**
- Create: `app/extensions/SocialMedia/System/Services/CanonicalCatalogueService.php`
- Modify: `app/extensions/SocialMedia/tests/cross_product_catalogue_contract.php`

**Interfaces:**
- `resolve(User $user, string $sourceKey, string $recordType, string $recordId, ?string $vertical = null, ?string $subtype = null): array`
- `search(User $user, string $sourceKey, string $recordType, array $filters = [], int $limit = 50): array`
- `transform(User $user, array $envelope, string $contentType, ?string $destination = null, ?string $vertical = null, ?string $subtype = null): array`
- `health(User $user): array`
- `handoff(User $user, string $target, string $handoffType, array $context): array`

- [ ] **Step 1: Validate canonical envelope**

Require `source_system`, `source_type`, `source_id`, `tenant_id`, `canonical_fields`, `provenance`, `authority`. Reject source-key mismatch and tenant mismatch.

- [ ] **Step 2: Resolve #337 profile**

Call `DistributionCapabilityService::resolveVerticalProfile()` and use generic-business fallback. Never create another vertical resolver.

- [ ] **Step 3: Transform bounded fields**

Use `catalogues.php` vertical/content maps to extract only declared canonical fields. Include:

```php
'canonical_source' => [
    'source_system' => ...,
    'source_type' => ...,
    'source_id' => ...,
    'source_version' => ...,
    'source_updated_at' => ...,
],
'profile_version' => ...,
'profile_provenance' => ...,
'attribution_confidence' => ...,
```

Missing required facts return explicit `missing_canonical_fields`; never synthesize prices, inventory, availability, addresses or capacity.

- [ ] **Step 4: Add feed health**

Return only `healthy`, `degraded`, `unavailable`, `misconfigured` with bounded reason codes and adapter health metadata.

- [ ] **Step 5: Add governed handoff**

Check target against #337 allowed handoff targets and catalogue source registry. Missing optional target returns `handoff_unavailable`. Record audit receipt; do not create shadow operational records.

- [ ] **Step 6: Add immutable audit receipts**

Reuse `ext_social_media_distribution_audits` with destination `catalogue`; store source identifiers/hashes, profile version, rejection/health/handoff codes and no credentials/raw models.

- [ ] **Step 7: Run contract/lint**

Expected: only model-factory assertions remain red.

---

### Task 4: Source-backed DistributionItem factory

**Files:**
- Modify: `app/extensions/SocialMedia/System/Models/DistributionItem.php`
- Modify: `app/extensions/SocialMedia/tests/cross_product_catalogue_contract.php`

**Interfaces:**
- `DistributionItem::fromCanonicalSource(User $user, array $transformation, array $attributes = []): self`

- [ ] **Step 1: Add factory validation**

Require transformed `canonical_source.source_system`, `source_type`, `source_id`, `content_type` and provenance metadata.

- [ ] **Step 2: Map source identity without source mutation**

Store `source_type` as `<source_system>:<source_type>`, `source_id` as canonical source ID, and canonical provenance inside `payload`.

- [ ] **Step 3: Preserve existing social-post factory**

Do not change `fromSocialMediaPost()` semantics or existing content-type validation.

- [ ] **Step 4: Run contract/lint**

Expected: issue #275 contract green.

---

### Task 5: Provenance, hardening and PR gate

**Files:**
- Create: `app/extensions/SocialMedia/docs/TITAN-REACH-CROSS-PRODUCT-CATALOGUE-FILE-MAP.md`
- Modify: `docs/superpowers/specs/2026-08-13-titan-reach-cross-product-catalogues-design.md` only if implementation materially differs.

**Interfaces:**
- Documents every new non-UI file, why it was necessary, and which existing seam it extends.

- [ ] **Step 1: Document file provenance and authority boundaries**

State explicitly that no Blade files, source-product models or new database tables were created.

- [ ] **Step 2: Verify branch scope**

Compare feature branch to current `main`; changes must stay inside SocialMedia contracts/services/config/model/tests/docs plus focused CI docs/workflow.

- [ ] **Step 3: Verify CI**

Require focused issue #275 contract and changed-PHP lint to pass on exact head.

- [ ] **Step 4: Review security/correctness**

Check adapter absence, cross-tenant envelopes, raw record leakage, fabricated defaults, handoff target expansion and accidental source mutation paths.

- [ ] **Step 5: Open PR with `Closes #275`**

Summarize source authority, optional-dependency behavior, nine-vertical coverage, feed health/rejections/handoffs and verification limits.
