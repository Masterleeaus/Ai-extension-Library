# Titan Reach assisted marketplace file map

Issue: #271

Depends on: #337

## Destinations

- Facebook Marketplace
- Gumtree Australia

Both destinations are implemented as `assisted` workflows. Titan Reach prepares and tracks the package; the authenticated business user submits the listing on the marketplace.

## Existing-file-first implementation

| New file | Existing source/template | Why it is necessary |
|---|---|---|
| `config/assisted-marketplaces.php` | `config/distribution.php` | Holds operational metadata not represented by provider capabilities: official handoff URLs, allowed completion hosts, expiry/image limits and destination-specific manual posting guidance. It does not duplicate publishing modes or provider capabilities. |
| `System/Services/AssistedMarketplaceService.php` | `System/Services/EbayListingService.php` and `System/Services/DistributionCapabilityService.php` | A provider-neutral application service is required for package preparation, manual handoff, completion confirmation, renewal and enquiry handoff. |
| `System/Http/Controllers/AssistedMarketplaceController.php` | `System/Http/Controllers/EbayListingController.php` | Governed HTTP actions are required for the future native Listings UI and other approved clients. |
| `tests/Unit/AssistedMarketplaceContractTest.php` | `tests/Unit/VerticalDistributionProfilesContractTest.php` and `tests/Unit/EbayListingIntegrationContractTest.php` | Prevents regression into simulated publishing, browser automation, non-vertical packages, unsafe URL completion or ungoverned actions. |

## Existing file edited

`System/SocialMediaServiceProvider.php` is edited in place to:

- merge the assisted operational config;
- register the existing controller with the current authenticated SocialMedia route group;
- expose prepare, open, complete, renew, enquiry-handoff and status actions.

No new route provider is introduced.

## Nine-vertical behaviour

Every prepared package calls `DistributionCapabilityService::forVerticalDestination()` and receives the canonical profile from #337 or the generic-business fallback.

The package records:

- canonical vertical and label;
- business subtype and whether it is recognised;
- destination suitability;
- vertical/provider required fields;
- media guidance;
- calls to action;
- authoritative handoff targets;
- compliance warnings;
- profile version and provenance;
- shared vertical context ID and hash where available.

Facilities maintenance resolves to the `field-home-services` family and `facilities-maintenance` subtype.

## Manual-only safety boundary

- No API publication is claimed.
- No browser automation, scraping, form simulation or credential collection is used.
- `open` returns the official destination URL; it does not operate the marketplace account.
- Completion requires explicit human confirmation and a valid HTTPS listing URL on an allowed marketplace host.
- The posting form URL itself is not accepted as proof of completion.
- Titan Reach records `manually_published`; it does not change the canonical `DistributionItem` status to provider-published.
- Renewals create a reviewed manual package and never repost automatically.
- Enquiries are deduplicated and handed to a human/authoritative workflow; no automated reply is sent.

## Governance and data authority

- Tenant ownership and `DistributionItem` approval are required before preparation or external handoff.
- Destination suitability and provider capability fail closed.
- A per-tenant/item/destination lock serialises mutations.
- Every idempotent action stores a request hash; reuse of a key with different input fails with `idempotency_key_conflict`.
- Distribution audits record package, completion, renewal and enquiry events.
- Titan Commerce, WorkCore, Bookings, Property, Automotive, Hire and CRM remain authoritative for source records.
- Titan Reach stores the approved listing snapshot and external URL only.

## UI boundary

**No new Blade page** is introduced by #271. The native customer-facing Listings and assisted-posting interface remains issue #274 and must be duplicated from the closest existing SocialMedia/MagicAI Blade page.

## Live validation boundary

The service is source-contract ready. Final browser validation requires authenticated marketplace accounts and current marketplace posting forms. Titan Reach must continue to rely on human submission unless an approved official provider or partner API is later obtained.
