# Titan Reach Universal UI Design

## Goal
Implement issue #274 as a customer-facing Titan Reach workspace that exposes Overview, Create, Distribute, Listings, Organic Social, Paid Media, Creative Studio, Catalogues, Inbox, Connections, Analytics and Settings while preserving the installed `SocialMedia` identity, existing route names and canonical authority boundaries.

## Approved source contract
Issue #274 and closed dependency #337 are the authoritative product/design contract. The UI must expose exactly nine canonical vertical families and keep `facilities-maintenance` beneath `field-home-services`.

## Architecture

### Reuse-first page strategy
Do not create twelve independent pages.

- **Overview**: keep `resources/views/index.blade.php` and augment it with the Reach navigation/context strip.
- **Organic Social**: keep `resources/views/post/index.blade.php` and add only shared Reach navigation.
- **Connections**: keep `resources/views/platforms.blade.php` and add only shared Reach navigation.
- **Create / Distribute / Listings / Paid Media / Creative Studio / Catalogues / Inbox / Analytics / customer Settings**: serve through one new `resources/views/workspace.blade.php` page duplicated from `resources/views/index.blade.php` and adapted to section-specific content.
- **Administrator provider settings**: keep `resources/views/setting/index.blade.php` unchanged and admin-only. It contains provider application credentials/webhook configuration and is never used as the customer Settings destination.
- Add one shared `resources/views/components/reach-navigation.blade.php` partial duplicated from the card/button conventions in `resources/views/components/home/tools.blade.php`.

Every new Blade file must be documented with its native parent and reason for necessity.

### Controller boundary
Add `ReachWorkspaceController`, structurally based on `SocialMediaController`, to provide read-only section context plus governed universal draft creation. It consumes existing application services/models only:

- `DistributionCapabilityService::resolveVerticalProfile()` for tenant/vertical context and generic fallback.
- `DistributionCapabilityService::suitabilityFor()` / destination catalogue for suitability.
- `DistributionItem` for provider-neutral draft items.
- `SocialMediaPlatform` for tenant-owned connected accounts.
- Existing #273 engagement routes for inbox actions; the UI never bypasses `EngagementGovernanceService`.

No new provider registry, account store, vertical catalogue, inbox database, audit ledger, CRM or operational source-of-truth is introduced.

## Vertical behaviour
The UI exposes exactly these top-level families:

1. `field-home-services`
2. `accommodation`
3. `real-estate`
4. `salons-personal-care`
5. `fitness-membership`
6. `automotive-services`
7. `ecommerce-retail`
8. `hire-rental`
9. `booking-capacity`

`facilities-maintenance` is surfaced only as a Field and Home Services subtype.

For every workspace request the controller resolves the canonical profile using the existing vertical context engine when available, otherwise `generic-business`. The resolved profile determines:

- allowed content types;
- required fields;
- media guidance;
- calls to action;
- destination suitability;
- handoff targets;
- compliance warnings;
- profile version/provenance.

Tenant override data may change governed profile fields but cannot change the canonical family slug or create a tenth top-level family.

## Workspace sections

### Create
Show only content types supported by the resolved profile. `social_post` launches the existing native post composer. Other supported types can create a Titan Reach draft `DistributionItem` through the workspace controller.

Manual workspace drafts are always stamped with `source_type=manual` and `source_id=null`. The customer request cannot supply or impersonate canonical CRM, Commerce, Property, Automotive, Hire or other source provenance. Canonical source identifiers can only enter Titan Reach through their owning integration.

### Distribute
Show tenant-owned distribution items and a destination matrix. Each destination is labelled `direct`, `partner`, `assisted` or `export_only`; `not-applicable` combinations are disabled rather than hidden or simulated.

### Listings
Filter universal items to marketplace/classified/property/vehicle/job/room-stay/hire-rental listing types. Preserve canonical source IDs and source authority; manual drafts never fabricate source IDs.

### Paid Media
Expose `paid_creative` items and provider capability/readiness status only. No UI action may self-authorise spend or bypass provider/account approval rules.

### Creative Studio
Surface existing AI/creative routes and vertical-aware media guidance/calls-to-action; do not create a second content generator.

### Catalogues
Display the current vertical content catalogue and optional source handoff expectations. No cross-product records are copied into Titan Reach by this issue.

### Inbox
Display connected engagement-capable accounts and links/actions through the authenticated #273 engagement endpoints. Provider limitations remain visible; TikTok commercial comment management stays unavailable.

### Analytics
Show tenant-scoped Titan Reach distribution-item counts/statuses. Provider-native analytics remain with the existing channel analytics flows.

### Settings
Expose only customer-safe business context, resolved vertical/subtype/version and connection-management entry points. Provider application credentials, secrets, webhooks and administrator configuration remain on the existing admin-only settings page.

## Navigation
A reusable Reach navigation partial appears on the existing Overview, Organic Social and Connections views and the new workspace page. It uses native `x-button`/card conventions, is horizontally scrollable on small screens, and preserves keyboard focus/ARIA semantics. The Settings tab resolves to `workspace/settings`, never the admin credential route.

## Routes
Preserve every existing route. Add only names under the existing `dashboard.user.social-media.` namespace:

- `workspace` — GET `workspace/{section}` with an allowlisted section slug.
- `workspace.draft.store` — POST `workspace/create/draft` for non-social universal drafts.

Navigation uses existing route names for Overview, Organic Social and Connections. New workspace sections—including customer Settings—use the new workspace route. Existing campaign/post routes remain the Creative Studio entry points.

## Draft validation and safety
Universal draft creation:

- is tenant-bound to `Auth::user()`;
- rejects `social_post` and directs users to the canonical post composer;
- only accepts a `content_type` present in the resolved profile;
- enforces canonical required fields and maps a profile `description` requirement to the canonical content body;
- bounds arbitrary payload fields/values;
- refuses caller-supplied source provenance and stamps manual source ownership itself;
- stores only bounded `payload` metadata including vertical slug, subtype, profile version and provenance;
- never publishes, activates spend or calls provider mutation endpoints.

## Testing
Add a focused source/contract test and GitHub Actions workflow that prove:

- exactly nine canonical verticals are represented;
- facilities maintenance is not a tenth vertical;
- all twelve navigation labels are present;
- existing native routes remain referenced;
- new workspace routes are authenticated and namespaced;
- the workspace resolves `resolveVerticalProfile()` and suitability;
- generic fallback is present;
- unsupported/not-applicable combinations are disabled;
- direct/partner/assisted/export-only modes are visibly represented;
- draft creation is tenant-scoped and refuses `social_post`;
- manual drafts cannot claim canonical source provenance;
- customer Settings never exposes the admin-only credential page;
- new Blade provenance is documented;
- all changed PHP files pass `php -l`.

## Blade provenance

| New file | Native parent | Reason |
|---|---|---|
| `resources/views/workspace.blade.php` | `resources/views/index.blade.php` | One reusable native Reach workspace is required for nine new customer sections that have no existing safe customer page. |
| `resources/views/components/reach-navigation.blade.php` | `resources/views/components/home/tools.blade.php` | Reuses native card/button/grid conventions for responsive workspace navigation. |

No other new Blade files are permitted for #274 without updating this provenance table first.
