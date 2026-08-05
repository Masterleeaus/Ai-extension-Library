# Titan Hub Native Design-System Migration Guide

## Goal

Migrate retained QRPay Flutter screens and future Titan Hub features to the native Titan design system without breaking route ownership, controller contracts, feature flags, backend authority or upgrade compatibility.

This is an incremental migration. Do not rewrite the whole donor application and do not introduce MobileKit HTML or Bootstrap dependencies.

## Target structure

```text
mobile apps/titan-hub/lib/
├── features/
│   └── design_system/
│       └── titan_design_system_showcase.dart
└── shared/
    └── design_system/
        ├── foundations/
        │   ├── titan_colors.dart
        │   ├── titan_motion.dart
        │   ├── titan_radius.dart
        │   ├── titan_spacing.dart
        │   ├── titan_theme.dart
        │   └── titan_typography.dart
        └── components/
            ├── titan_action_card.dart
            ├── titan_action_sheet.dart
            ├── titan_badge.dart
            ├── titan_button.dart
            ├── titan_card.dart
            ├── titan_empty_state.dart
            ├── titan_error_state.dart
            ├── titan_list_tile.dart
            ├── titan_loading.dart
            ├── titan_section_header.dart
            ├── titan_status_banner.dart
            └── titan_text_field.dart
```

## Migration order

For each feature or screen:

1. identify the closest existing QRPay/Titan Hub implementation;
2. preserve its route, binding, controller and backend request contracts;
3. add or extend widget tests for the current behaviour;
4. replace local colours, spacing, typography and radii with Titan foundations;
5. replace repeated local controls with the closest Titan component;
6. verify compact, expanded, dark-mode and 200-percent text behaviour;
7. verify semantics, callback suppression and error/offline states;
8. run `flutter analyze` and `flutter test` before merging.

Do not combine a visual migration with unrelated domain, API or state-management refactoring.

## Theme migration

### Before

Donor screens may read static colours from `CustomColor`, construct local text styles, or use the legacy `Themes` facade directly.

### After

Use semantic native roles:

```dart
final theme = Theme.of(context);
final semantic = theme.extension<TitanSemanticColors>()!;

final primary = theme.colorScheme.primary;
final surface = theme.colorScheme.surface;
final body = theme.colorScheme.onSurfaceVariant;
final danger = semantic.danger;
```

`lib/utils/theme.dart` remains as a compatibility facade, but new feature code should consume `Theme.of(context)` and Titan tokens directly.

Use:

- `TitanSpacing` instead of ad hoc gaps or Bootstrap-equivalent utility values;
- `TitanRadius` instead of repeated `BorderRadius.circular(...)` literals;
- `TitanTypography` and the active `TextTheme` instead of screen-local font families;
- `TitanMotion` only when motion adds meaning and the content remains usable without it.

Tenant accent colours may change primary presentation. They must not replace semantic success, warning, danger or information colours.

## Component replacements

| Existing pattern | Replace with |
|---|---|
| Primary or secondary local button | `TitanButton` with the appropriate variant |
| Status chip or count label | `TitanBadge` |
| Repeated decorated container/card | `TitanCard` |
| Capability or task launch card | `TitanActionCard` |
| Heading plus optional action | `TitanSectionHeader` |
| Text/icon/avatar row | `TitanListTile` |
| Boxed, underline, clearable, password or multiline input | `TitanTextField` |
| Ad hoc modal action menu | `showTitanActionSheet<T>` |
| Success, warning, offline or information banner | `TitanStatusBanner` |
| No-results placeholder | `TitanEmptyState` |
| Retryable failure placeholder | `TitanErrorState` |
| Progress placeholder | `TitanLoading` |

## Buttons

Use `TitanButton` for actionable text controls. It guarantees a minimum 48-pixel target and suppresses callbacks while disabled or loading.

```dart
TitanButton(
  label: 'Confirm booking',
  icon: Icons.check,
  expand: true,
  isLoading: controller.isSaving,
  onPressed: controller.canSave ? controller.save : null,
)
```

Use the danger variant only for destructive actions. Do not communicate destructive meaning through colour alone; the label must be explicit.

## Cards and capability actions

Use `TitanCard` for content surfaces and `TitanActionCard` when the whole surface launches a capability.

```dart
TitanActionCard(
  title: 'Schedule a service',
  description: 'Choose availability and confirm reminders.',
  icon: Icons.calendar_month_outlined,
  badge: const TitanBadge(label: 'Recommended'),
  onTap: openScheduler,
)
```

Provide a semantic label when a card contains custom widgets from which readable text cannot be inferred.

## Lists

Use `TitanListTile` for text, icon, avatar, metadata and badge rows. It reflows metadata and badges below the main copy on constrained widths.

```dart
TitanListTile(
  title: job.title,
  subtitle: job.address,
  leading: const Icon(Icons.home_outlined),
  metadata: job.startTimeLabel,
  badge: TitanBadge(label: job.statusLabel),
  onTap: () => openJob(job.id),
)
```

Set `isDestructive: true` for destructive rows. Disabled rows must not receive an active callback.

## Forms

Use `TitanTextField` rather than wrapping raw inputs with screen-local decoration.

```dart
TitanTextField(
  controller: emailController,
  label: 'Email',
  hint: 'name@example.com',
  helperText: 'Used for receipts',
  errorText: emailError,
  keyboardType: TextInputType.emailAddress,
  showClear: true,
)
```

Security requirements:

- never log field values, passwords, tokens, card details or identity documents;
- do not retain a secondary plaintext copy when clearing text;
- keep password visibility state local to the widget;
- preserve helper guidance when validation errors are shown;
- use backend validation as authoritative even when local validation improves feedback.

## Action sheets

Use the typed native API:

```dart
final action = await showTitanActionSheet<JobAction>(
  context,
  title: 'Job actions',
  actions: const [
    TitanActionSheetAction(
      value: JobAction.open,
      label: 'Open job',
      icon: Icons.open_in_new,
      tone: TitanActionSheetTone.primary,
    ),
    TitanActionSheetAction(
      value: JobAction.delete,
      label: 'Delete job',
      icon: Icons.delete_outline,
      tone: TitanActionSheetTone.destructive,
    ),
  ],
);
```

The returned value is nullable because the user may dismiss the sheet. Do not encode actions as untyped strings when a feature enum exists.

## Loading, empty, error and offline states

Every asynchronously loaded screen must define all relevant states rather than leaving a blank surface:

- loading: `TitanLoading`;
- empty: `TitanEmptyState`;
- retryable failure: `TitanErrorState`;
- offline/sync delay: `TitanStatusBanner.offline()`;
- operation result or warning: `TitanStatusBanner` with a semantic tone.

Keep cached or queued content visible when safe. A banner is preferable to replacing usable offline content with a full-screen error.

## Responsive rules

- Under 600 logical pixels, prefer vertical composition and wrap secondary metadata.
- From 600 logical pixels, use the existing shell's medium layout patterns.
- At 900 logical pixels and above, multi-column internal/showcase composition is allowed where content widths remain readable.
- Never branch on a device model or platform name for layout.
- Test at 200-percent text scaling; do not solve overflow by clipping meaningful text.

## Navigation boundary

The internal component showcase is registered at `/design-system`. It is not a primary destination and must not alter the customer navigation contract:

1. Home
2. Explore
3. Hub QR
4. Activity
5. Account

Feature pages remain owned by their existing routes. Design-system migration does not create parallel navigation stacks.

## MobileKit boundary

Allowed:

- inspect spacing, hierarchy, status treatment and interaction intent;
- recreate approved patterns as native Flutter components;
- keep internal provenance and SHA evidence.

Not allowed:

- add Bootstrap CSS or JavaScript to Flutter;
- render MobileKit pages in production WebViews;
- copy vendor page architecture as Titan feature architecture;
- ship MobileKit sample content or assets without an explicit product need and licence review;
- let UI donor patterns define commerce, operational or finance authority.

## Verification

From `mobile apps/titan-hub/` run:

```bash
flutter pub get
dart format --output=none --set-exit-if-changed \
  lib/app lib/core lib/features lib/shared \
  lib/main.dart \
  lib/backend/local_storage/local_storage.dart \
  lib/backend/utils/logger.dart \
  lib/views/navbar/bottom_navbar_screen.dart \
  test/core test/features test/shared test/widget_test.dart
flutter analyze
flutter test
```

The repository workflow `.github/workflows/titan-hub-flutter.yml` enforces locked dependencies, formatting, analysis and the complete Flutter test suite on relevant pull requests.

## Review evidence

A migrated component or screen is ready only when:

- its route and backend contract remain intact or have an explicit tested migration;
- light and dark themes render through semantic roles;
- compact and expanded layouts do not overflow;
- 200-percent text remains readable;
- semantics identify purpose, state and destructive meaning;
- disabled/loading actions cannot invoke callbacks;
- loading, empty, error and offline behaviour is deliberate;
- no Bootstrap, MobileKit runtime or production WebView dependency was introduced;
- the internal showcase is updated when a public design-system API changes.
