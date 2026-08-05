# Titan Reach Google Business Profile and Pinterest Design

Issue: #272  
Depends on: #337

## Objective

Add official Google Business Profile and Pinterest distribution adapters to the existing SocialMedia/Titan Reach extension. Both adapters must consume the canonical nine-vertical profile catalogue, reuse `SocialMediaPlatform`, `DistributionItem`, `DistributionCapabilityService`, channel entitlements and distribution audits, and fail closed when provider access or scopes do not support an operation.

## Canonical vertical boundary

The adapters support exactly these top-level families: `field-home-services`, `accommodation`, `real-estate`, `salons-personal-care`, `fitness-membership`, `automotive-services`, `ecommerce-retail`, `hire-rental`, and `booking-capacity`. Facilities maintenance remains a subtype of `field-home-services`. The generic-business fallback remains available through `DistributionCapabilityService`.

## Architecture

Each provider receives four focused units derived from existing SocialMedia files:

1. A provider config derived from `config/ebay.php` defines OAuth settings, scopes, API roots and destination capability metadata.
2. A provider helper derived from `System/Helpers/Ebay.php` owns OAuth exchange, encrypted access/refresh token handling, refresh, bounded retry and provider HTTP calls.
3. An OAuth controller derived from `Oauth/EbayController.php` validates state, discovers the provider account and capabilities, and persists encrypted credentials in `SocialMediaPlatform`.
4. A distribution service and thin HTTP controller derived from `EbayListingService.php` and `EbayListingController.php` enforce tenant ownership, account health, approvals, vertical suitability, idempotency, audits and provider receipts.

No new provider registry, route provider, account table, distribution table or Blade page is introduced.

## Provider scope

### Google Business Profile

- OAuth scope: `https://www.googleapis.com/auth/business.manage`.
- Discover accessible Business Profile accounts and locations.
- Publish immediate standard, offer and event local posts.
- Upload location photos from approved HTTPS media.
- List reviews, submit an explicitly approved review reply, and create a human handoff snapshot.
- Read supported Business Profile performance metrics.
- Reconcile a previously published local post.
- Scheduling is unavailable because the provider operation is immediate.

### Pinterest

- OAuth scopes: `boards:read`, `boards:write`, `pins:read`, `pins:write`, and `user_accounts:read`.
- Discover the authenticated user account and boards.
- Create image Pins and product-linked Pins containing only user-approved original content.
- Read Pin details and analytics.
- Create human engagement handoff snapshots without automatic replies.
- Video upload, catalog management, advertising and secret-board operations remain unavailable unless separately implemented and approved.

## Credentials and account health

Access and refresh tokens are stored only as Laravel-encrypted strings inside the existing `credentials` JSON. Plain tokens are never persisted. The helper refreshes an access token before expiry and updates the existing account row. OAuth state is single-use, tenant-bound and expires after ten minutes.

Each readiness operation performs live provider discovery and stores a bounded health snapshot containing discovered scopes/capabilities, provider account identifiers, last check time, response status and rate-limit/retry information. A connected row alone never grants a capability.

## Distribution governance

Publishing requires:

- the `DistributionItem` and `SocialMediaPlatform` to belong to the authenticated user;
- a connected, healthy account of the expected platform;
- `DistributionCapabilityService::forVerticalDestination()` to return available;
- provider scope discovery to permit the operation;
- `approval_status === approved` for provider-changing operations;
- a per-user/item/provider lock;
- an idempotency key and canonical request hash.

Provider receipts are stored beneath `DistributionItem.payload.google_business_profile` or `DistributionItem.payload.pinterest`. Receipts include external IDs, operation state, provider response metadata, rate-limit data, vertical/profile version and provenance, and a bounded history of idempotent operations. Distribution audits omit secrets.

## Handoffs and authority

Reviews, comments, leads and enquiries are represented only as bounded handoff snapshots with the resolved profile's authoritative `handoff_targets`. Titan Reach does not create or duplicate canonical CRM, WorkCore, Commerce, Booking, Property, Automotive or Hire records. Automatic review replies and automatic comment replies are prohibited.

## Error handling

Provider 401/403 responses mark the requested operation unavailable and return a safe error. Provider 429 responses include a normalized rate-limit snapshot and do not retry unsafe POST operations. GET and idempotent PUT requests may retry once for 429/5xx using a bounded `Retry-After` delay. Unknown scopes, destinations, content types and vertical combinations fail closed.

## Testing

A source contract is written before production changes and verifies:

- both providers are connected channels but are not inserted into the legacy generic publisher list;
- encrypted token fields and single-use OAuth state;
- official provider endpoints and minimum scopes;
- exactly nine canonical vertical families and facilities as a subtype;
- use of `forVerticalDestination()`;
- tenant checks, approval gates, locks, idempotency/request hashes, audits and receipts;
- Google local posts/photos/reviews/performance/reconciliation;
- Pinterest boards/Pins/product links/analytics/reconciliation;
- human handoffs and no automatic replies;
- no new Blade page.

## UI boundary

Issue #272 adds authenticated provider and distribution endpoints only. The native customer-facing Google Business Profile and Pinterest interface remains issue #274 and must reuse the closest existing SocialMedia Blade templates.