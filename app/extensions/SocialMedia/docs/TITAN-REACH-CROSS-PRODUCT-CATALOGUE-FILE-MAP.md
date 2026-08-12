# Titan Reach Cross-Product Catalogue File Map

## Issue

GitHub #275 — Titan Reach: catalogues and cross-product contracts.

## Architecture boundary

Titan Reach remains a distribution system. CRM, WorkCore, Commerce, Marketing, Bookings, Property, Automotive and Hire remain authoritative for their own operational records. This change adds a read-only source contract and transformation layer; it does not add a second operational catalogue or direct source-model access.

## New files

### `System/Contracts/CanonicalSourceAdapterContract.php`

**Why necessary:** No existing Titan Reach contract represented optional read-only access to canonical records owned by other Titan products.

**Pattern/provenance:** Follows the extension's existing contract/service boundaries and uses the existing `App\Models\User` tenant context. It deliberately exposes only source discovery/read/search/health/handoff operations.

**Authority rule:** No create/save/update/delete/price/inventory/availability mutation method exists.

### `System/Services/CanonicalSourceRegistry.php`

**Why necessary:** Optional product dependencies require runtime service resolution without importing their models or assuming they are installed.

**Pattern/provenance:** Reuses the same fail-closed runtime resolution approach already used by `DistributionCapabilityService` for optional vertical context resolution: configured identifier, `app()->bound()`/`class_exists()`, interface validation and graceful absence.

**Authority rule:** The registry loads the base `config/catalogues.php` directly, then permits overrides only for existing source keys. An override cannot invent a new authoritative source family.

### `System/Services/CanonicalCatalogueService.php`

**Why necessary:** Titan Reach needed one governed place to validate canonical source envelopes, resolve #337 vertical context, transform bounded fields, report feed health/rejections and route handoffs.

**Pattern/provenance:** Reuses `DistributionCapabilityService` for vertical/suitability decisions and `ext_social_media_distribution_audits` for immutable receipts. It does not query source-product Eloquent models.

**Authority rule:** Canonical source records are accepted as read-only envelopes. Reach only transforms their declared fields and can only hand bounded context back to an authoritative adapter.

### `config/catalogues.php`

**Why necessary:** Source record types/field aliases and destination-field maps do not belong inside provider credentials/config or the canonical #337 vertical suitability catalogue. Keeping this mapping separate prevents overloading those existing responsibilities.

**Pattern/provenance:** Config-first catalogue pattern follows `config/distribution.php` and `config/vertical-distribution.php`. It consumes the same exact nine vertical slugs and does not create another vertical resolver.

**Coverage:**
- `crm`
- `workcore`
- `commerce`
- `marketing`
- `bookings`
- `property`
- `automotive`
- `hire`

Vertical mappings contain generic-business plus exactly nine canonical families. `facilities-maintenance` is a subtype override beneath `field-home-services`.

### `tests/cross_product_catalogue_contract.php`

**Why necessary:** Locks issue #275's architectural invariants: eight optional sources, nine canonical verticals, read-only adapter surface, tenant/source validation, provenance, health/handoff behavior and no direct source-model queries.

**Pattern/provenance:** Follows the repository's focused source-contract test pattern used by Titan Reach provider/vertical/UI work.

### `.github/workflows/titan-reach-cross-product-catalogue-verification.yml`

**Why necessary:** Runs the focused #275 contract and PHP syntax checks against the exact pull-request head.

**Pattern/provenance:** Mirrors existing focused Titan Reach verification workflows.

### `docs/superpowers/specs/2026-08-13-titan-reach-cross-product-catalogues-design.md`

Permanent design record for #275.

### `docs/superpowers/plans/2026-08-13-titan-reach-cross-product-catalogues.md`

Permanent implementation plan for #275.

## Modified files

### `System/Models/DistributionItem.php`

Adds `fromCanonicalSource()` to the existing Reach-owned distribution model. It reuses `createForUser()` and existing `source_type`, `source_id` and `payload` columns. No schema migration is required.

Existing `fromSocialMediaPost()` behavior is unchanged.

## Deliberately not created

- No new Blade view or JavaScript UI.
- No new controller or route provider.
- No new migration or database table.
- No Reach-owned product/property/vehicle/booking/hire/job/customer/inventory catalogue.
- No direct imports of CRM, WorkCore, Commerce, Booking, Property, Automotive or Hire Eloquent models.
- No source mutation API.
- No tenth `facilities-management` vertical.

## Config integration note

The implementation intentionally does **not** rewrite the large `SocialMediaServiceProvider` solely to register one config file. `CanonicalSourceRegistry` and `CanonicalCatalogueService` load the canonical base catalogue directly, matching the existing `DistributionCapabilityService` catalogue pattern, and layer only bounded runtime overrides on top. This reduces regression risk while preserving optional configuration behavior.
