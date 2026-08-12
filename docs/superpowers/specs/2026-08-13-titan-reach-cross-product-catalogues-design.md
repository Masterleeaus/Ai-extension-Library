# Titan Reach Cross-Product Catalogue Contracts Design

## Objective

Implement issue #275 inside the existing SocialMedia/Titan Reach extension so Reach can read canonical records from optional Titan products, transform them into governed distribution items, report feed health/rejections, and route enquiries back to authoritative systems without becoming the source of truth for operational data.

## Architecture

Titan Reach remains a distribution layer. `DistributionItem` remains the local distribution/workflow record. `DistributionCapabilityService` remains the canonical #337 vertical and destination-suitability resolver. `ext_social_media_distribution_audits` remains the immutable receipt ledger.

Cross-product access is introduced as a small read-only adapter contract plus registry. Source products are discovered at runtime through configured service identifiers/classes. Reach never imports source-product Eloquent models directly and never creates a duplicate product/property/vehicle/booking/job/customer catalogue database.

## Chosen approach

Use a governed adapter registry rather than direct model imports or replicated Reach-owned catalogues.

Rejected approaches:

- Direct cross-extension model access couples Reach to optional product schemas and breaks graceful absence.
- Replicated catalogues create competing sources of truth and stale inventory/price/availability risk.

The adapter-registry approach preserves optional dependencies, source ownership, tenant isolation and deterministic transformation.

## Canonical source envelope

Every source record returned to Reach is normalized to an immutable array envelope containing:

- `source_system`
- `source_type`
- `source_id`
- `tenant_id`
- optional `company_id`
- `source_version`
- `source_updated_at`
- `canonical_fields`
- `provenance`
- `authority`

`canonical_fields` contains only bounded fields required for transformation. It must not contain credentials or arbitrary model serialization.

The envelope is read-only. Reach may create/update its own `DistributionItem`, but it may never write the canonical source record through this contract.

## Source adapter contract

A source adapter exposes only:

- `sourceKey()`
- `available(User $user)`
- `supports(string $recordType)`
- `fetch(User $user, string $recordType, string $recordId)`
- `search(User $user, string $recordType, array $filters = [], int $limit = 50)`
- `health(User $user)`
- `handoff(User $user, string $handoffType, array $context)`

There are deliberately no `save`, `update`, `delete`, `setPrice`, `setInventory`, `setAvailability` or equivalent mutation methods.

Adapters must tenant-scope every read and handoff internally. Reach validates the normalized returned tenant identifier against the current user before accepting the record.

## Optional source catalogue

The `catalogues.php` configuration declares these optional authoritative sources:

- `crm` — customers, leads, quotes and enquiries.
- `workcore` — jobs, technicians, service areas, maintenance plans and facilities-maintenance work.
- `commerce` — products, variants, inventory, prices, orders and collections.
- `marketing` — campaigns, audiences/segments and attribution context where exposed.
- `bookings` — appointments, reservations, classes, events, waitlists and capacity.
- `property` — properties, rooms, stays, rates, listings, inspections, appraisals and leasing context.
- `automotive` — customers/vehicles, workshop bookings, inspections, parts, fleet and vehicle listings.
- `hire` — assets, rates, availability, bonds, bookings, delivery/collection and damage/return state.

Each source defines a resolver/service identifier, supported canonical record types and authoritative fields. If its configured service is missing or does not implement the read-only contract, it is unavailable rather than fabricated.

## Nine-vertical mapping

The catalogue config maps exactly the #337 canonical families:

1. `field-home-services`
2. `accommodation`
3. `real-estate`
4. `salons-personal-care`
5. `fitness-membership`
6. `automotive-services`
7. `ecommerce-retail`
8. `hire-rental`
9. `booking-capacity`

Facilities maintenance remains a `field-home-services` subtype.

Each vertical declares:

- authoritative source/record combinations;
- canonical field aliases used by Reach content types;
- optional subtype-specific field overrides;
- handoff target preferences;
- attribution source preferences.

The mapping never changes record ownership. Vertical profiles determine transformation and suitability only.

## Field transformation

`CanonicalCatalogueService` resolves a vertical profile through `DistributionCapabilityService`, resolves the source adapter, validates the source envelope, and maps canonical fields into a bounded Reach payload.

The resulting distribution payload contains:

- `canonical_source` with source system/type/id/version/update time;
- `vertical`/`business_subtype`/profile version/provenance;
- mapped Reach fields such as title, content, price, availability, location, booking URL and media references where present;
- `attribution` with source identifiers and confidence;
- no copied source credentials or hidden operational fields.

Required destination/content fields are checked against #337 and destination capability rules. Missing required canonical facts produce explicit rejections; Reach does not invent them.

## DistributionItem boundary

`DistributionItem` remains the only Reach-owned persistent distribution record. For a canonical source-backed item:

- `source_type` stores the authoritative source key + record type;
- `source_id` stores the canonical source ID;
- `payload.canonical_source` stores provenance/version metadata;
- item title/content/payload are transformed distribution data, not the source record itself.

Material source changes can cause a new transformation/reapproval state but never a source-system write.

## Feed health and rejection handling

`CanonicalCatalogueService::health()` reports each optional source as `healthy`, `degraded`, `unavailable` or `misconfigured` with bounded reason codes.

Transformation rejection receipts use the existing distribution audit ledger and include:

- source key/type/id hash or canonical identifier where safe;
- vertical/profile version;
- destination/content type;
- missing/invalid field names;
- reason code;
- timestamp.

No credentials or full raw records are written to audits.

## Attribution confidence

Attribution is explicit and bounded:

- `1.0` for direct canonical source identifiers returned by the authoritative adapter;
- lower values only when an adapter itself provides a documented cross-system attribution confidence;
- no inferred/fabricated source ownership inside Reach.

The distribution receipt carries attribution confidence and provenance.

## Handoffs

Lead/enquiry handoffs use the resolved #337 `handoff_targets` and the source registry. Reach sends bounded context to the matching authoritative adapter/service and records a receipt. Missing optional handoff products return `handoff_unavailable`; Reach does not create a shadow lead, booking, job, property or hire record.

## Generic-business fallback

If vertical context is unavailable, Reach uses the existing generic-business profile. Source reads remain allowed only for explicitly requested/configured canonical record types. Generic fallback cannot expand provider capability or source authority.

## Security and tenancy

- Every adapter call receives the current `User`.
- Returned envelope tenant ID must match the current user context before transformation.
- Registry resolution fails closed for missing/invalid adapters.
- Raw source models are never serialized into Reach.
- Audit snapshots redact token/secret/password/authorization keys.
- The contract exposes no canonical source mutation method.

## Testing

A focused issue #275 contract will verify:

- exactly nine canonical vertical mappings plus generic fallback;
- facilities maintenance remains under `field-home-services`;
- all eight optional product/source families are declared;
- adapter contract has no write/mutation methods;
- missing adapters degrade gracefully;
- canonical envelopes require provenance and source IDs;
- transformation produces source provenance and vertical profile metadata;
- required-fact rejection is explicit rather than fabricated;
- feed health and handoff status are bounded;
- tenant mismatches fail closed;
- source adapter methods cannot overwrite canonical source data;
- all changed PHP files lint in GitHub Actions.

## UI boundary

Issue #275 does not create a new Blade page. The #274 Catalogues/Connections/Distribute UI may consume this service through existing controller/workspace seams in later integration, but this issue is the authoritative backend contract layer.