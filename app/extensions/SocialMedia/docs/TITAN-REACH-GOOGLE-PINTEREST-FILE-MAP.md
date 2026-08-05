# Titan Reach Google Business Profile and Pinterest file map

Issue: #272

Depends on: #337

## Objective

Add official Google Business Profile and Pinterest adapters to the existing Titan Reach/SocialMedia extension without introducing another account model, provider registry, distribution store, route provider or UI system.

## Existing-file-first implementation

| File | Closest existing source | Why it is necessary |
|---|---|---|
| `config/google-business-profile.php` | `config/ebay.php` and `config/distribution.php` | Defines Google OAuth/API roots, minimum scope, retry settings and destination capabilities without duplicating the central destination catalogue. |
| `config/pinterest.php` | `config/ebay.php` and `config/distribution.php` | Defines Pinterest OAuth/API roots, continuous refresh, minimum scopes and destination capabilities. |
| `System/Helpers/GoogleBusinessProfile.php` | `System/Helpers/Ebay.php` | Owns encrypted token refresh, official Google requests, bounded read retries and normalized provider receipts. |
| `System/Helpers/Pinterest.php` | `System/Helpers/Ebay.php` | Owns encrypted token refresh, Pinterest API v5 requests, bounded read retries and rate-limit receipts. |
| `System/Http/Controllers/Oauth/GoogleBusinessProfileController.php` | `System/Http/Controllers/Oauth/EbayController.php` | Adds single-use OAuth state, encrypted token persistence and bounded account/location capability discovery. |
| `System/Http/Controllers/Oauth/PinterestController.php` | `System/Http/Controllers/Oauth/EbayController.php` | Adds single-use OAuth state, encrypted token persistence and bounded user/board capability discovery. |
| `System/Services/GoogleBusinessProfileService.php` | `System/Services/EbayListingService.php` | Applies tenant ownership, live provider capability, vertical suitability, approval, idempotency, receipts, audits, reconciliation and review handoffs. |
| `System/Services/PinterestService.php` | `System/Services/EbayListingService.php` | Applies the same governance to board discovery, image Pins, product links, analytics, reconciliation and engagement handoffs. |
| `System/Http/Controllers/GoogleBusinessProfileController.php` | `System/Http/Controllers/EbayListingController.php` | Exposes thin tenant-scoped, validated and locked Google operations. |
| `System/Http/Controllers/PinterestController.php` | `System/Http/Controllers/EbayListingController.php` | Exposes thin tenant-scoped, validated and locked Pinterest operations. |
| `tests/Unit/GoogleBusinessProfilePinterestContractTest.php` | `tests/Unit/EbayListingIntegrationContractTest.php` and `tests/Unit/VerticalDistributionProfilesContractTest.php` | Prevents regression in security, account capability discovery, nine-vertical behaviour, provider receipts and UI boundaries. |

## Existing files edited

### `System/Enums/PlatformEnum.php`

- Adds `google-business-profile` and `pinterest` as connected channels.
- Leaves the legacy `all()` generic publishing list unchanged so neither adapter is routed through the old generic publisher.
- Reuses the current platform card and disconnect behaviour.

### `config/distribution.php`

- Activates both destination adapters as `direct` and approval-required.
- Keeps content types and provider capability declarations in the existing central catalogue.

### `System/SocialMediaServiceProvider.php`

- Merges both provider configs.
- Registers OAuth callbacks inside the existing OAuth route group.
- Registers authenticated provider operations inside the current SocialMedia user route group.
- Introduces no new provider or route registry.

## Credential and OAuth boundary

- OAuth state is random, user-bound, platform-bound, cached for ten minutes and consumed with `Cache::pull`.
- Access and refresh tokens are stored only as Laravel-encrypted strings in the existing `SocialMediaPlatform.credentials` JSON.
- Plain provider tokens are never persisted or written to distribution audits.
- Refresh occurs before access-token expiry.
- Unsafe POST publication requests are not automatically retried.
- GET and idempotent PUT requests retry at most once for rate-limit or temporary provider failures.

## Provider capability discovery

A connected account row alone does not grant a provider operation.

Google readiness discovers accessible accounts and locations, then records operation flags for:

- local-post publication;
- photos;
- reviews;
- explicit review replies;
- performance metrics;
- reconciliation.

Pinterest readiness discovers the user account and accessible boards, then records operation flags for:

- boards;
- Pin publication;
- product links;
- Pin analytics;
- reconciliation.

Every provider-changing request checks the discovered operation flag again.

## Google Business Profile boundary

Supported:

- immediate standard, offer and event local posts;
- approved HTTPS photos;
- review listing;
- explicit approved review replies;
- human review handoffs;
- supported performance metrics;
- local-post reconciliation.

Not claimed:

- scheduled posts;
- automatic review replies;
- arbitrary location editing;
- Google Ads;
- provider access without Business Profile API approval.

Business Information location resources are preserved as `locations/{id}` for discovery. Local-post, photo and review operations preserve the corresponding v4 resource `accounts/{account}/locations/{id}`.

## Pinterest boundary

Supported:

- user and board discovery;
- approved original image Pins;
- destination and product links;
- Pin reconciliation;
- supported Pin analytics;
- bounded human engagement handoffs.

Not claimed:

- automatic comment replies;
- video upload;
- catalogs or shopping ingestion;
- advertising;
- secret-board operations;
- publication without the required Pinterest scopes or app access.

A publish request must select a board discovered for that connected account and explicitly confirm that the supplied image is approved original content.

## Nine-vertical behaviour

Both adapters call `DistributionCapabilityService::forVerticalDestination()` and consume exactly the canonical nine families from #337:

1. Field and Home Services
2. BnB, Hotel and Rooming Services
3. Real Estate
4. Salons and Personal Care
5. Fitness and Membership Businesses
6. Automotive Services
7. E-commerce and Retail
8. Hire and Rental
9. Booking, Reservation and Capacity-Based Businesses

Facilities maintenance remains the `facilities-maintenance` subtype of `field-home-services`. Generic business fallback remains available.

Receipts preserve:

- vertical and business subtype;
- destination suitability;
- profile version and provenance;
- shared vertical-context ID/hash where available;
- provider external ID;
- request hash;
- normalized rate-limit data.

## Governance and authority

- Tenant ownership is checked at both controller account lookup and application-service boundaries.
- External changes require an approved `DistributionItem`.
- Item mutations are serialized by a per-user/item/provider cache lock.
- Idempotency keys map to canonical request hashes and conflict on changed input.
- Operation history is bounded to ten records per operation.
- Provider events are written to `ext_social_media_distribution_audits` when that table exists.
- CRM, WorkCore, Commerce, Bookings, Property, Automotive and Hire remain authoritative for leads, customers, jobs, products, availability, inventory, properties, vehicles and rentable assets.
- Handoff snapshots do not duplicate authoritative records and never send an automatic reply.

## UI boundary

**No new Blade page** is introduced by issue #272. The native customer-facing Google Business Profile and Pinterest controls remain issue #274 and must duplicate the closest existing SocialMedia/MagicAI Blade templates.

## Live validation boundary

Source contracts and PHP syntax can be verified without provider credentials. Live OAuth, account discovery, publishing, review reply and analytics verification require approved Google Business Profile and Pinterest developer applications plus authenticated business accounts.