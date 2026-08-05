# Titan Reach universal distribution file map

Issue: #268

## Existing-file-first rule

The existing `SocialMedia` extension remains authoritative for connected accounts, social posts and publishing. New files below exist only because the universal distribution concept has no equivalent legacy file. Each file was patterned from an existing SocialMedia file rather than written as an unrelated scaffold.

| New file | Existing file used as the style/template | Why a new file is necessary |
|---|---|---|
| `database/migrations/2026_08_05_000001_create_ext_social_media_distribution_items_table.php` | `database/migrations/2025_01_23_081426_create_social_media_posts_table.php` | Additive storage is required for non-social listing types and immutable capability snapshots without changing the legacy social-post table. |
| `System/Models/DistributionItem.php` | `System/Models/SocialMediaCampaign.php` and `System/Models/SocialMediaSharedLog.php` | A provider-neutral item cannot be represented accurately by `SocialMediaPost` without making that model the authority for products, properties, vehicles and jobs. |
| `System/Services/DistributionCapabilityService.php` | `System/Services/SocialMediaChannelEntitlementService.php` | Destination modes and account-specific effective capabilities need one reusable application service shared by future UI and AI bridge work. |
| `config/distribution.php` | `config/social-media.php` | The marketplace and distribution catalogue must remain separate from OAuth credentials while preserving the extension's PHP configuration style. |
| `tests/Unit/UniversalDistributionContractTest.php` | `tests/Unit/ChannelEntitlementContractTest.php` | Contract coverage is required for compatibility, migration boundaries, tenant scope and fail-closed capabilities. |

## Existing files edited

- `System/Models/SocialMediaPost.php` received only a `distributionItem()` relation. Existing fields, casts, publishing services and routes remain unchanged.

## Compatibility boundary

- `SocialMediaPost` remains the canonical authority for social post content and publication.
- `DistributionItem::fromSocialMediaPost()` stores only a mapping and shared metadata; `resolvedContent()` reads the canonical post content.
- Planned providers with no adapter are declared with `adapter_available = false` and expose no effective publishing capability.
- Facebook Marketplace and Gumtree are declared as assisted destinations, not direct API publishers.
- No user-facing Blade files are introduced by issue #268.
