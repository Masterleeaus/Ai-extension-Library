# Titan Hub Native Design System

## Status

Approved for implementation through Issue #256 and the user's instruction to continue the Titan Hub backlog without pausing between completed issues.

## Goal

Translate the useful visual and interaction patterns from the licensed MobileKit Bootstrap 4 donor into an accessible, responsive, dark-mode-capable native Flutter design system for Titan Hub. No Bootstrap runtime, HTML rendering or production WebView is permitted.

## Options considered

### 1. Token-first native library — selected

Define Titan semantic tokens and build a focused set of native Flutter primitives and composites. Existing and new feature screens consume the same components. MobileKit is used only to identify proven patterns.

This provides consistent theming, accessibility, testability and responsive behaviour without coupling the app to donor HTML structure.

### 2. Page-by-page MobileKit reproduction

Rebuild every MobileKit page in Flutter. This would create unnecessary screens, duplicate business flows and make later vertical composition harder. Rejected.

### 3. Restyle donor QRPay widgets in place

Apply colours and spacing directly across existing donor widgets. This would spread design rules through hundreds of files and make migration difficult to verify. Rejected as the primary approach; donor screens can migrate incrementally through the component library.

## Source patterns

The internal component map is derived from:

- MobileKit theme settings: semantic colours, Inter typography, 6px-radius cards and light/dark surfaces;
- cards: product, standard, header/body/footer, status and grouped-list cards;
- list views: text, icon, image/avatar, badge, metadata, header/footer and media rows;
- inputs: boxed, basic, animated-label, helper text, select, password, phone and multiline;
- action sheets: inset sheets, icon actions, destructive actions and share grids;
- notifications: title/body, media, status icon, actions, tap-to-dismiss and timed variants;
- utility pages: authentication, profile, commerce, invoice, cart, empty/error and loading patterns.

The exact donor files and licence boundary remain documented in `docs/titan-hub/DONOR_MANIFEST.md` and `DONOR_SECURITY_SCAN.*`.

## Architecture

```text
lib/shared/design_system/
├── foundations/
│   ├── titan_colors.dart
│   ├── titan_spacing.dart
│   ├── titan_radius.dart
│   ├── titan_typography.dart
│   ├── titan_motion.dart
│   └── titan_theme.dart
├── components/
│   ├── titan_action_card.dart
│   ├── titan_badge.dart
│   ├── titan_button.dart
│   ├── titan_card.dart
│   ├── titan_list_tile.dart
│   ├── titan_section_header.dart
│   ├── titan_status_banner.dart
│   ├── titan_text_field.dart
│   └── titan_state_view.dart
├── overlays/
│   └── titan_action_sheet.dart
└── showcase/
    └── titan_design_system_showcase.dart
```

Foundations are dependency-light and immutable. Components depend on Material, Titan foundations and optional callbacks only. The showcase consumes public component APIs and therefore acts as integration evidence rather than private implementation access.

## Design foundations

### Colour

MobileKit's palette is translated into semantic Titan roles rather than copied as literal Bootstrap classes:

- primary: `#1E74FD` donor reference, adjusted through generated Material colour schemes;
- secondary: `#6C7C94`;
- success: `#34C759`;
- danger: `#EC4433`;
- warning: `#FE9500`;
- info: `#592BCA`;
- light background/surface/outline/text roles;
- dark background `#0C1624`, surface `#0F1C2F`, outline `#1B283B` and readable text roles.

Business-manifest accent colours may seed the theme later, but semantic success, warning and danger colours are never replaced by tenant branding.

### Typography

Inter becomes the Titan-owned default because it is the MobileKit reference and is highly legible across platforms. Typography is represented through Material text roles and remains responsive to system text scaling. No fixed-height text containers may clip 200% text scale.

### Spacing and shape

- base spacing step: 4;
- common gaps: 4, 8, 12, 16, 24, 32;
- compact radius: 8;
- standard card/input radius: 12;
- prominent panel radius: 20;
- minimum interactive target: 48 logical pixels;
- compact content width uses full available space; wide content is constrained to readable maximum widths.

### Motion

Default feedback animations are short and platform-respectful. Components query `MediaQuery.disableAnimations` and avoid required motion for comprehension.

## Component contracts

### `TitanCard`

A semantic surface with optional header, body, footer, press action and status accent. It supports standard, elevated and outlined variants. Its tap target covers the full card and exposes a semantic button when interactive.

### `TitanActionCard`

A compact capability card for Home and Explore. It supports icon, title, description, badge, loading state and enabled/disabled behaviour. It replaces ad hoc feature tiles and adapts from single-column compact layouts to responsive grids.

### `TitanListTile`

A touch-safe row supporting leading icon/avatar, title, subtitle, metadata, badge, trailing widget and destructive styling. It maps MobileKit text, image and icon list variants into one API.

### `TitanTextField`

A Material text field with boxed and basic visual variants, label, helper/error text, clear action, password reveal, prefix/suffix and multiline support. It never stores input or logs values.

### `TitanButton`

Primary, secondary, tonal, outline, text and destructive variants with loading and icon support. Minimum height is 48 and progress state preserves the accessible label.

### `TitanStatusBanner`

Information, success, warning and error message surface with optional action and dismiss affordance. It replaces donor notification boxes for in-app status communication.

### `TitanStateView`

Reusable loading, empty, error and offline states with icon, title, message, primary action and secondary action. Skeleton loading is optional where content shape is known; indeterminate progress remains available.

### `TitanActionSheet`

Native `showModalBottomSheet` wrapper with safe-area handling, title, optional message, icon actions, destructive actions and cancel. It returns a typed action ID and never renders donor HTML.

### `TitanBadge` and `TitanSectionHeader`

Small reusable primitives for count/status labels and page section structure.

## Theme integration

The existing `Themes` API remains for compatibility but delegates light and dark `ThemeData` creation to `TitanTheme`. Existing donor screens continue compiling while new Titan-owned screens use Material 3 and design-system components. Tenant accent seeding remains an explicit future extension point.

## Showcase

A native `TitanDesignSystemShowcase` route demonstrates:

- light and dark theme support through inherited theme;
- tokens and typography;
- buttons and loading states;
- cards and action cards;
- list variants;
- text fields;
- badges and status banners;
- loading, empty, error and offline states;
- modal action sheet.

The showcase is available only through a dedicated route and is not inserted into customer primary navigation.

## Accessibility

- every interactive component has a 48x48 minimum target;
- semantic labels are required when icons have no visible text;
- loading controls keep their original semantic label and announce busy state;
- destructive actions are named and coloured but never communicated by colour alone;
- text honours system scaling and contrast uses Material colour roles;
- focus order follows visual order;
- keyboard activation works through native Material controls;
- state views and banners expose meaningful semantic messages;
- component tests exercise text scale 2.0, dark mode and compact/expanded widths.

## Responsive behaviour

Components use constraints rather than device-name checks. `TitanResponsiveGrid` derives columns from minimum item width and available width. Cards never hard-code phone widths. Action sheets cap width on expanded screens. Showcase content uses the existing Titan breakpoints.

## Error handling

- invalid colours fall back to Titan primary;
- missing optional labels are omitted rather than rendered empty;
- disabled actions cannot be invoked;
- action-sheet cancellation returns `null`;
- image/avatar load failures fall back to an icon or initials;
- loading state prevents duplicate callbacks;
- components assert only developer-contract violations and remain safe in release mode.

## Testing

Unit tests cover token values, theme construction and action-sheet model validation.

Widget tests cover:

- 48px minimum controls;
- light/dark colour roles;
- 200% text scale without overflow;
- button loading semantics and callback suppression;
- card/list click semantics;
- text-field helper, error, clear and obscure behaviour;
- state-view actions;
- responsive action-card grid;
- showcase component presence;
- native action-sheet selection and cancellation.

Permanent Titan Hub CI remains the verification authority: locked dependencies, formatting, `flutter analyze` and `flutter test`.

## Non-goals

- no production WebViews for MobileKit;
- no copied Bootstrap JavaScript or CSS dependencies;
- no full redesign of every QRPay donor screen in this issue;
- no remote visual page builder;
- no tenant-controlled arbitrary CSS;
- no replacement of backend business authority;
- no signed app-store release build.
