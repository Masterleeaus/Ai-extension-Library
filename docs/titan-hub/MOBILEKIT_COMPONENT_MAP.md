# Titan Hub MobileKit Component Map

## Purpose

This document records the approved translation of the licensed MobileKit Bootstrap 4 UI kit into Titan Hub's native Flutter design system. MobileKit is a visual and interaction donor only. It does not define Titan Hub application architecture, backend authority, navigation ownership or production runtime dependencies.

Production components live under:

- `mobile apps/titan-hub/lib/shared/design_system/foundations/`
- `mobile apps/titan-hub/lib/shared/design_system/components/`
- `mobile apps/titan-hub/lib/features/design_system/`

The internal showcase route is `/design-system`. It is registered for development and review but deliberately excluded from the five-position customer navigation.

## Licence and provenance

| Item | Evidence |
|---|---|
| Donor | MobileKit Bootstrap 4 UI Kit 2.9.1 |
| Sanitised reference path | `mobile apps/mobilekit-reference/` |
| Original archive SHA-256 | `a2f9bda06a35a2d82217692bdff736a8b901cef795422fc98f5816f667c6a736` |
| Canonical provenance record | `docs/titan-hub/DONOR_MANIFEST.md` |
| Approved use | Internal visual and interaction reference for native Flutter recreation |
| Prohibited use | Shipping MobileKit HTML, Bootstrap runtime, copied JavaScript behaviour or production WebViews |

Purchase records, licence keys and vendor-account credentials remain outside Git.

## Foundation mapping

| MobileKit reference | Titan native implementation | Notes |
|---|---|---|
| Primary, secondary, success, danger, warning and info colours | `TitanColors`, `TitanSemanticColors` | Status meaning remains semantic and is not overwritten by tenant accent colours. |
| Light background, lines, heading and body colours | `TitanTheme.light()` | Material 3 `ColorScheme` provides accessible native roles. |
| Dark background, content surface, heading, text and line colours | `TitanTheme.dark()` | Dark scaffold and raised surfaces remain visually distinct. |
| Inter typography scale | `TitanTypography` | Native Flutter text themes support system text scaling. |
| Six-pixel donor radius | `TitanRadius` | Standard, compact and prominent radii are explicit tokens. |
| Repeated spacing utilities | `TitanSpacing` | Four-pixel base scale replaces Bootstrap spacing classes. |
| CSS transitions | `TitanMotion` | Motion durations are optional native tokens; content remains usable without animation. |

## Component mapping

| MobileKit pattern family | Native Titan component | Production decision |
|---|---|---|
| Primary, secondary, outline, text and destructive buttons | `TitanButton` | Material buttons with at least 48-pixel targets, loading and disabled states. |
| Badges and contextual labels | `TitanBadge` | Semantic tone enum; status is conveyed by text as well as colour. |
| Basic, elevated, coloured and status cards | `TitanCard` | Native card surface with optional header, footer, whole-card action and semantic status border. |
| Product and task action cards | `TitanActionCard` | Responsive icon, copy and badge composition; no copied commerce logic. |
| Section titles with optional action | `TitanSectionHeader` | Stacks the action below copy on constrained widths. |
| Text, icon, image and metadata list rows | `TitanListTile` | One accessible API for title, subtitle, leading content, metadata, badge and action. Compact rows move metadata below copy at large text sizes. |
| Boxed and basic inputs | `TitanTextField` | Native `TextField` with boxed or underline presentation. |
| Clearable inputs | `TitanTextField(showClear: true)` | Clears the controller directly and retains no secondary plaintext copy. |
| Password inputs | `TitanTextField(obscureText: true)` | Visibility state remains local to the widget and is never logged. |
| Textareas | `TitanTextField(maxLines: ...)` | Native multiline input with helper and error guidance. |
| Default, inset, iconed and destructive action sheets | `showTitanActionSheet<T>` | Typed native modal bottom sheet with safe area, scroll bounds and disabled actions. |
| Notification banners and operational alerts | `TitanStatusBanner` | Success, warning, danger, info and offline copy with explicit semantics. |
| Empty screens | `TitanEmptyState` | Icon, title, guidance and optional action. |
| Retryable failures | `TitanErrorState` | Explicit error semantics and optional retry action. |
| Loading screens | `TitanLoading` | Progress semantics; tests advance fixed frames rather than waiting for an intentional infinite animation. |
| Navigation bars, sidebars and bottom menus | Existing `TitanHubShell` | MobileKit navigation is not copied. Titan Hub retains Home, Explore, Hub QR, Activity and Account. |
| Authentication pages | Existing retained QRPay routes plus Titan components | Screens are migrated incrementally; authentication authority remains in the backend. |
| Onboarding pages | Titan onboarding feature composition | Reuse tokens and components, not MobileKit page markup. |
| Profile and settings pages | Account feature plus Titan components | Existing route ownership remains unchanged. |
| Commerce product grids and checkout pages | Future commerce features using Titan components | MobileKit examples inform presentation only; catalogue and order rules remain backend-owned. |
| Utility layouts, tabs and accordions | Native Material widgets plus Titan tokens | Add a dedicated Titan wrapper only after repeated product usage proves it necessary. |

## Page-pattern decisions

MobileKit's authentication, onboarding, commerce, profile and utility pages are not copied as complete Flutter screens. They are treated as composition references. Product features must:

1. start from the closest existing Titan Hub or QRPay Flutter screen;
2. replace local styling with Titan tokens and components;
3. preserve existing route, controller and API contracts unless a tested migration changes them;
4. keep vertical and App Mode capability resolution manifest-driven;
5. keep operational and financial authority in Laravel, WorkCore and Titan Pay.

## Accessibility contract

Every native component must satisfy the following baseline:

- interactive targets are at least 48 logical pixels;
- text remains usable at 200 percent scaling;
- labels, states and destructive meaning are exposed to assistive technology;
- colour is never the only carrier of status;
- disabled and loading controls do not invoke callbacks;
- compact layouts reflow rather than clip or horizontally overflow;
- light and dark themes use semantic contrast roles;
- forms preserve both helper guidance and validation errors when both are needed;
- modal content respects safe areas and remains scrollable.

Automated widget coverage lives in `mobile apps/titan-hub/test/shared/design_system/` and `mobile apps/titan-hub/test/features/design_system/`.

## Explicitly rejected production patterns

Do not introduce any of the following into Titan Hub Flutter:

- Bootstrap CSS or JavaScript;
- jQuery, Ionicons runtime or MobileKit DOM plugins;
- MobileKit HTML templates rendered inside the app;
- production WebViews used to imitate native screens;
- CSS class-name compatibility layers;
- copied page-level navigation architecture;
- separate Flutter forks for each vertical;
- direct Flutter mutation of authoritative commerce, operational or ledger records.

## Review checklist for new components

Before adding another design-system wrapper, verify that:

1. Material or an existing Titan component cannot already express the requirement;
2. the pattern appears in more than one product flow or is an approved foundation primitive;
3. compact, expanded, dark-mode, disabled, error and 200-percent text behaviour are defined;
4. semantics and callback suppression are tested;
5. the showcase demonstrates the public API;
6. this map and the migration guide are updated when the public component set changes.
