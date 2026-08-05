# Titan Reach native Blade map

## Rule

Titan Reach retains the existing MagicAI `SocialMedia` extension as its UI base. Existing Blade files are edited in place. A new user-facing Blade file may be added only when no suitable native file exists; in that case it must be duplicated from the closest existing MagicAI/SocialMedia view and its source must be recorded here.

## Issue #266 conversion inventory

| Titan Reach surface | Existing native file edited | Native layout/components retained | New Blade file created |
|---|---|---|---|
| Main dashboard | `resources/views/index.blade.php` | `panel.layout.app`, titlebar actions, native cards, grids and included SocialMedia components | No |
| Provider settings | `resources/views/setting/index.blade.php` | `panel.layout.settings`, `x-card`, `x-forms.input`, `x-button`, native validation and route patterns | No |

## Verification statement

Issue #266 introduces no new user-facing Blade files. The existing SocialMedia dashboard and settings pages are the native parents and remain in place. Their routes, permission patterns, components, form actions and internal extension identifiers are unchanged.

Future Titan Reach UI issues must extend this table before adding a new Blade page.
