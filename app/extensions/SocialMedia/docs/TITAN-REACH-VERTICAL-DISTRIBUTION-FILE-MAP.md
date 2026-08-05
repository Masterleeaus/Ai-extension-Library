# Titan Reach canonical vertical distribution file map

Issue: #337

## Canonical vertical families

Titan Reach exposes exactly nine top-level vertical families:

1. `field-home-services`
2. `accommodation`
3. `real-estate`
4. `salons-personal-care`
5. `fitness-membership`
6. `automotive-services`
7. `ecommerce-retail`
8. `hire-rental`
9. `booking-capacity`

Facilities maintenance is a subtype and capability under `field-home-services`. It is not a tenth top-level vertical.

## Existing-file-first implementation

| File | Source/template | Reason |
|---|---|---|
| `config/vertical-distribution.php` | Duplicated from the structure and formatting conventions of `config/distribution.php` | The provider catalogue remains provider-centric. A sibling catalogue is necessary to hold vertical profiles, subtypes, guidance, suitability and authority handoffs without creating another provider registry. |
| `tests/Unit/VerticalDistributionProfilesContractTest.php` | Duplicated from `tests/Unit/UniversalDistributionContractTest.php` | A dedicated contract is required to prevent vertical-count drift, facilities becoming a tenth vertical, unsupported content types and authority-boundary regressions. |

## Existing files edited

- `System/Models/DistributionItem.php`
  - Adds room/stay, membership, class/session, booking and hire/rental content types.
  - Does not change the table, migration history or canonical social-post mapping.

- `System/Services/DistributionCapabilityService.php`
  - Resolves canonical profiles and old-name aliases.
  - Optionally consumes a configured Titan Vertical Context resolver.
  - Falls back to `generic-business` when no vertical context is available.
  - Combines provider availability with vertical/content suitability.
  - Supports governed tenant refinement of existing families only.
  - Prevents tenant configuration from adding a new top-level vertical.
  - Adds profile version and provenance to capability audit snapshots.

## Authority boundaries

- Provider adapters remain destination-centric and consume the resolved profile.
- Titan Reach does not create separate provider adapters for each vertical.
- CRM remains authoritative for customers, leads and companies.
- WorkCore remains authoritative for jobs, technicians, field work and maintenance operations.
- Titan Commerce remains authoritative for products, variants, prices and inventory.
- Titan Bookings remains authoritative for availability, appointments, capacity and reservations.
- Titan Property remains authoritative for properties, inspections, leasing and property operations.
- Titan Automotive remains authoritative for vehicles, workshop records and fleet operations.
- Titan Hire remains authoritative for rentable assets, rates, bonds, availability and returns.

## Suitability levels

- `primary`: normally recommended for the vertical.
- `supported`: valid but not necessarily the first recommendation.
- `special-case`: allowed only when the content and destination rules genuinely fit.
- `not-applicable`: fail closed unless the canonical catalogue is changed in a reviewed release.

Tenant overrides may refine an existing profile but cannot convert provider-unavailable functionality into an available capability or add a tenth vertical family.

## Compatibility

- Existing `SocialMedia` folder, namespace, provider, routes, tables and migrations remain unchanged.
- Existing `DistributionItem` records remain valid because content type is stored as a string.
- Existing callers of `forDestination()` are unchanged.
- New vertical-aware callers use `forVerticalDestination()`.
- Installations with no vertical profile receive the generic-business fallback.
