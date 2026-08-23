# Titan Reach Universal UI File Map

Issue: #274

This file records the native UI provenance and authority boundaries for the universal Titan Reach customer workspace.

## New Blade files

These are the only new Blade files introduced by #274.

| New Blade file | Native parent/template | Necessity |
|---|---|---|
| `resources/views/workspace.blade.php` | `resources/views/index.blade.php` | One reusable native panel workspace serves Create, Distribute, Listings, Paid Media, Creative Studio, Catalogues, Inbox, Analytics and customer Settings instead of creating nine blank-slate pages. |
| `resources/views/components/reach-navigation.blade.php` | `resources/views/components/home/tools.blade.php` | Reuses existing SocialMedia card/button/grid conventions for accessible responsive product navigation. |

## Existing customer views retained and edited

- `resources/views/index.blade.php` — Overview content remains native; only shared Reach navigation is added.
- `resources/views/post/index.blade.php` — Organic Social remains the canonical post list; only shared Reach navigation is added.
- `resources/views/platforms.blade.php` — Connections/account management remains canonical; only shared Reach navigation is added.

## Administrator settings boundary

`resources/views/setting/index.blade.php` is an **admin-only** provider application credential/settings surface. It remains unchanged and is not linked as the customer Settings destination. Customer Settings is the authenticated `workspace/settings` section and exposes only safe business-context and connection-management information; provider secrets, app credentials and webhook configuration remain administrator-only.

## Backend files

- `System/Http/Controllers/ReachWorkspaceController.php` — new controller, patterned after `SocialMediaController`; reads canonical profile/suitability context and creates tenant-owned universal drafts only.
- `System/SocialMediaServiceProvider.php` — existing provider edited to register the two authenticated workspace routes; no second route provider is introduced.

## Authority boundaries

- `DistributionCapabilityService` remains authoritative for canonical vertical resolution, generic fallback and destination suitability.
- `DistributionItem` stores only Titan Reach distribution drafts/mappings; canonical social posts remain `SocialMediaPost` and use the existing post composer/publisher flow.
- Provider mutation controllers/services remain authoritative for publishing, provider revisions, assisted completion and other provider changes.
- #273 `EngagementGovernanceService` remains authoritative for inbox capabilities, replies, edit/delete and handoff.
- CRM, WorkCore, Commerce, Bookings, Property, Automotive and Hire remain authoritative for operational records.
- Paid Media UI can prepare/read paid creative state but cannot activate spend or self-issue approval.
- Tenant overrides cannot create a tenth top-level vertical or expand a `not-applicable` destination.

## Canonical vertical rule

Exactly nine top-level families are consumed from #337. `facilities-maintenance` remains a subtype/capability under `field-home-services` and is never exposed as a tenth vertical.
