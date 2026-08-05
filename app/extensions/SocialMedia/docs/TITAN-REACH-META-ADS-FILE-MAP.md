# Titan Reach Meta Ads file map

Issue: #270

## Existing-file-first rule

Meta Ads extends the existing Facebook channel, `DistributionItem`, provider capability catalogue, audit store and SocialMedia service provider. No duplicate Facebook account system and no standalone paid-media Blade page are introduced.

| New file | Existing file used as the style/template | Why a new file is necessary |
|---|---|---|
| `database/migrations/2026_08_05_000002_create_ext_social_media_paid_media_tables.php` | `database/migrations/2026_08_05_000001_create_ext_social_media_distribution_items_table.php` | Campaign drafts, append-only budget decisions and immutable provider receipts require dedicated additive storage. |
| `System/Models/PaidMediaCampaign.php` | `System/Models/DistributionItem.php` | Paid campaigns have budgets, schedules and four provider object IDs that do not belong on `SocialMediaPost` or the generic distribution record. |
| `System/Services/MetaAdsService.php` | `System/Services/EbayListingService.php` and `System/Services/DistributionCapabilityService.php` | The campaign → ad set → creative → ad lifecycle needs one governed application service shared by the future UI and reporting layers. |
| `System/Http/Controllers/MetaAdsController.php` | `System/Http/Controllers/EbayListingController.php` | Separate API actions are required for draft, recommendation, approval, PAUSED sync, preview, activation, pause and insights. |
| `config/meta-ads.php` | `config/ebay.php` | Paid-media permissions, budget limits, objectives, preview formats, insight fields and destination capabilities require an isolated provider module. |
| `tests/Unit/MetaAdsGovernanceContractTest.php` | `tests/Unit/EbayListingIntegrationContractTest.php` | Contract coverage is required for scopes, PAUSED creation, immutable approvals, explicit activation, tenant scope and AI boundaries. |

## Existing files edited

- `System/Helpers/Facebook.php`: retains organic Page methods and adds Marketing API methods using the advertising user token.
- `System/Http/Controllers/Oauth/FacebookController.php`: adds OAuth state validation, `ads_read`/`ads_management`, encrypted advertising-token storage and authorised ad-account discovery while preserving Page credentials.
- `System/SocialMediaServiceProvider.php`: merges Meta Ads configuration, activates its destination capability and registers governed API routes.

## Spend and authority boundaries

- Meta campaign, ad set and ad objects are always created with provider status `PAUSED`.
- AI recommendations are read-only and explicitly grant no spend or activation authority.
- Budget approval is a separate append-only human decision tied to a SHA-256 fingerprint of budget, schedule, targeting and creative.
- Any material edit appends `material_change_requires_new_approval` and invalidates the prior approval.
- Activation is a separate action requiring the current fingerprint, explicit confirmation and the configured activation Gate.
- Daily budgets require an explicit campaign spend cap; lifetime budgets require an end time.
- Budgets and spend caps are integer minor units and cannot exceed the host-configured maximum.
- Failed partial activation triggers a best-effort return to `PAUSED`.
- The application never increases budgets, activates ads or purchases media automatically.
- Provider mutation endpoints are not automatically retried.
- Every provider-changing operation uses a tenant/campaign lock and an immutable idempotent receipt.

## Data authority

- `DistributionItem` owns the approved paid creative source reference.
- `PaidMediaCampaign` owns the Meta campaign draft, governed budget and provider object IDs.
- The existing Facebook `SocialMediaPlatform` remains the connected-account authority.
- Titan CRM and Commerce remain authoritative for leads, products, prices and inventory.

## UI boundary

No new Blade page is introduced by issue #270. The future Paid Media navigation and native Blade interface remain in issue #274 and must be duplicated from existing SocialMedia/MagicAI pages.

## Live validation boundary

The implementation is contract-ready for Meta development/test ad accounts. A real Meta app, approved permissions, authorised Facebook Page, ad account, billing state and compliant creative are required for live provider validation. Full Laravel migrations, route listing and PHPUnit execution remain unclaimed in this connector-only runtime because the repository cannot be cloned into the execution container and exposes no CI checks.
