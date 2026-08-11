# Titan Reach Universal UI File Map

Issue: #274

This file documents native UI provenance before production implementation. The only new Blade files allowed by the approved design are listed below.

| New Blade file | Native parent/template | Necessity |
|---|---|---|
| `resources/views/workspace.blade.php` | `resources/views/index.blade.php` | Eight new customer workspace sections require a shared native panel shell; one reusable workspace avoids eight blank-slate pages. |
| `resources/views/components/reach-navigation.blade.php` | `resources/views/components/home/tools.blade.php` | Reuses existing SocialMedia card/button/grid conventions for responsive product navigation. |

## Existing views retained and edited

- `resources/views/index.blade.php` — Overview authority remains unchanged.
- `resources/views/post/index.blade.php` — Organic Social authority remains unchanged.
- `resources/views/platforms.blade.php` — Connections/account authority remains unchanged.
- `resources/views/setting/index.blade.php` — Existing SocialMedia settings authority remains unchanged.

## Authority boundaries

- `DistributionCapabilityService` remains authoritative for vertical resolution and destination suitability.
- `DistributionItem` stores only Titan Reach distribution drafts/mappings; canonical social posts remain `SocialMediaPost`.
- Provider mutation controllers/services remain authoritative for publishing and provider changes.
- #273 `EngagementGovernanceService` remains authoritative for inbox replies/edit/delete/handoff.
- CRM, WorkCore, Commerce, Bookings, Property, Automotive and Hire remain authoritative for operational records.
- The #274 UI must never activate spend, publish directly, create a tenth vertical or fabricate unavailable provider capabilities.
