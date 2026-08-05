# Titan Reach eBay integration file map

Issue: #269

## Existing-file-first rule

The eBay integration reuses `SocialMediaPlatform` for connected seller accounts and `DistributionItem` for listing records. No separate account, product, inventory or user-facing Blade subsystem is introduced.

| New file | Existing file used as the style/template | Why a new file is necessary |
|---|---|---|
| `System/Helpers/Ebay.php` | `System/Helpers/Linkedin.php` | eBay has a distinct OAuth consent flow, token refresh contract, Account API and Inventory API endpoints. |
| `System/Http/Controllers/Oauth/EbayController.php` | `System/Http/Controllers/Oauth/LinkedinController.php` | A provider-specific OAuth callback is required to validate state and store encrypted eBay seller tokens. |
| `System/Services/EbayListingService.php` | `System/Services/SocialMediaChannelEntitlementService.php` and existing publisher services | eBay listings are `DistributionItem` records, not `SocialMediaPost` publications, and require inventory-item plus offer lifecycle orchestration. |
| `System/Http/Controllers/EbayListingController.php` | `System/Http/Controllers/SocialMediaPostController.php` | Governed HTTP actions are required for readiness, draft sync, publish, revise, withdraw, reconciliation and buyer-question handoff. |
| `config/ebay.php` | `config/social-media.php` | eBay OAuth, Sandbox settings and destination capabilities need an isolated provider configuration without destabilising existing social credentials. |
| `tests/Unit/EbayListingIntegrationContractTest.php` | `tests/Unit/UniversalDistributionContractTest.php` | Contract coverage is required for official endpoints, scopes, tenant boundaries, approvals, idempotency and route registration. |

## Existing files edited

- `System/Enums/PlatformEnum.php`: adds eBay as a connected channel while retaining `all()` for social-only post destinations and adding `channels()` for the wider connection catalogue.
- `System/Http/Controllers/SocialMediaPlatformController.php`: switches the existing Channels page to `PlatformEnum::channels()`.
- `System/Http/Controllers/SocialMediaSettingController.php`: exposes eBay keys through the existing native settings page.
- `System/SocialMediaServiceProvider.php`: merges the eBay config, activates the eBay capability declaration, and adds OAuth/listing routes.
- `resources/views/platforms/platform-cards.blade.php`: keeps the existing native card and adds a generic initials fallback when a provider icon is absent.
- `resources/views/components/create-post-dropdown.blade.php`: explicitly keeps marketplace channels out of the social-post composer.

## Data authority and safety boundaries

- Titan Commerce remains authoritative for products, SKUs, prices and inventory. Titan Reach stores the approved listing snapshot and external eBay identifiers only.
- eBay access and refresh tokens are encrypted before they enter `SocialMediaPlatform.credentials`.
- Sandbox is the default environment.
- User OAuth consent is required; Titan Reach does not collect eBay usernames or passwords.
- Inventory item revisions send a complete replacement payload.
- Publishing, revisions and withdrawals require an approved `DistributionItem` and a connected, entitled eBay channel owned by the same tenant.
- Provider operations require explicit idempotency keys, use a per-item distributed mutation lock, and append immutable audit snapshots.
- Bounded retries are limited to eBay rate-limit and transient server responses.
- Buyer questions are handed to a human workflow; this issue does not impersonate the seller or send automated buyer messages.
- No new user-facing Blade page is introduced. The universal Listings UI remains issue #274.

## Live validation boundary

The implementation is Sandbox-ready and source-contract verified. A real Sandbox seller, eBay application keyset, RuName redirect value, business policies and inventory location are required for live provider validation. Full Laravel commands (`composer install`, migrations, route listing and PHPUnit) remain blocked in this connector-only runtime because the repository cannot be cloned into the execution container.
