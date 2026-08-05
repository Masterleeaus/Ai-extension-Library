# Titan Hub Native Design System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Translate selected licensed MobileKit visual patterns into a tested, accessible, responsive and dark-mode-capable native Flutter component library and showcase for Titan Hub.

**Architecture:** Introduce immutable semantic foundations under `shared/design_system/foundations`, build small native Flutter components on those foundations, expose a typed action-sheet overlay, and demonstrate only public APIs in a dedicated showcase route. Keep the existing `Themes` compatibility API but delegate theme construction to `TitanTheme` so retained QRPay screens continue compiling.

**Tech Stack:** Flutter 3.27.0, Dart >=3.6.0, Material 3, GetX routing, flutter_test, existing Titan Hub permanent CI.

## Global Constraints

- No production WebViews, Bootstrap JavaScript, Bootstrap CSS or HTML rendering.
- MobileKit is visual/interaction reference only and remains under `mobile apps/mobilekit-reference/`.
- Every interactive component has a minimum 48 logical-pixel target.
- Components must support light mode, dark mode, compact widths, expanded widths and 200% text scale.
- Semantic success, warning and danger colours are not replaced by tenant branding.
- Existing QRPay donor routes and widgets must continue compiling.
- Use public component APIs in the showcase; do not access private implementation state.
- Permanent verification is locked dependencies, Titan-owned formatting, `flutter analyze` and `flutter test`.

---

### Task 1: Semantic foundations and Material themes

**Files:**
- Create: `mobile apps/titan-hub/lib/shared/design_system/foundations/titan_colors.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/foundations/titan_spacing.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/foundations/titan_radius.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/foundations/titan_motion.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/foundations/titan_theme.dart`
- Modify: `mobile apps/titan-hub/lib/utils/theme.dart`
- Test: `mobile apps/titan-hub/test/shared/design_system/foundations/titan_theme_test.dart`

**Interfaces:**
- Produces: `TitanColors.primary`, `success`, `danger`, `warning`, `info`, dark surface roles.
- Produces: `TitanSpacing.xs/sm/md/lg/xl/xxl`.
- Produces: `TitanRadius.compact/standard/prominent`.
- Produces: `TitanTheme.light({Color? seedColor}) -> ThemeData`.
- Produces: `TitanTheme.dark({Color? seedColor}) -> ThemeData`.
- Existing `Themes.light` and `Themes.dark` delegate to `TitanTheme`.

- [ ] Write tests asserting MobileKit-derived semantic colours, Material 3 use, Inter typography, 48px component minimums and distinct readable dark surfaces.
- [ ] Run `flutter test test/shared/design_system/foundations/titan_theme_test.dart` and confirm missing-foundation failures.
- [ ] Implement immutable token classes and theme factories using `ColorScheme.fromSeed`, then override semantic extension roles without changing success/warning/danger from tenant seed colours.
- [ ] Modify `utils/theme.dart` so its public fields and theme-mode persistence remain compatible while delegating theme creation to `TitanTheme`.
- [ ] Run the focused theme test and `flutter analyze`.
- [ ] Commit `feat(titan-hub): add semantic design foundations`.

### Task 2: Buttons, badges, cards and section structure

**Files:**
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_badge.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_button.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_card.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_action_card.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_section_header.dart`
- Test: `mobile apps/titan-hub/test/shared/design_system/components/titan_surfaces_test.dart`

**Interfaces:**
- Produces: `TitanBadge(label, tone)`.
- Produces: `TitanButton(label, onPressed, variant, icon, isLoading)`.
- Produces: `TitanCard(child, header, footer, onTap, variant, statusTone)`.
- Produces: `TitanActionCard(title, description, icon, onTap, badge, isLoading, enabled)`.
- Produces: `TitanSectionHeader(title, subtitle, action)`.

- [ ] Write widget tests for 48px targets, callback suppression while loading/disabled, preserved semantic labels, full-card press semantics and 200% text scale without overflow.
- [ ] Run the focused test and confirm the component imports fail.
- [ ] Implement small components using native Material controls, Titan foundations and no fixed text heights.
- [ ] Add responsive action-card layout behaviour through constraints rather than device names.
- [ ] Run the focused test and analyzer.
- [ ] Commit `feat(titan-hub): add accessible action surfaces`.

### Task 3: Native lists and form controls

**Files:**
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_list_tile.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_text_field.dart`
- Test: `mobile apps/titan-hub/test/shared/design_system/components/titan_forms_and_lists_test.dart`

**Interfaces:**
- Produces: `TitanListTile(title, subtitle, leading, metadata, badge, trailing, onTap, isDestructive)`.
- Produces: `TitanTextField(controller, label, hint, helperText, errorText, variant, obscureText, showClear, prefix, suffix, maxLines)`.

- [ ] Write widget tests for list row semantics, 48px rows, badge/metadata rendering, destructive text labels, helper/error text, clear action, password reveal and multiline text scaling.
- [ ] Run the focused test and confirm missing types.
- [ ] Implement one list API covering MobileKit text, icon, avatar and metadata variants.
- [ ] Implement boxed/basic text fields with clear and obscure toggles without logging or retaining field values.
- [ ] Run the focused test and analyzer.
- [ ] Commit `feat(titan-hub): add native list and form components`.

### Task 4: Status, loading, empty, error and offline states

**Files:**
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_status_banner.dart`
- Create: `mobile apps/titan-hub/lib/shared/design_system/components/titan_state_view.dart`
- Test: `mobile apps/titan-hub/test/shared/design_system/components/titan_feedback_test.dart`

**Interfaces:**
- Produces: `TitanStatusBanner(title, message, tone, actionLabel, onAction, onDismiss)`.
- Produces: `TitanStateView.loading/empty/error/offline(...)`.

- [ ] Write tests for semantic messages, optional actions, dismiss behaviour, non-colour status labels, progress semantics and text scale 2.0.
- [ ] Run the focused test and confirm missing types.
- [ ] Implement native banner variants derived from MobileKit notification patterns.
- [ ] Implement state factories with icon, message and one or two actions; prevent duplicate loading callbacks.
- [ ] Run the focused test and analyzer.
- [ ] Commit `feat(titan-hub): add reusable feedback states`.

### Task 5: Typed native action sheet

**Files:**
- Create: `mobile apps/titan-hub/lib/shared/design_system/overlays/titan_action_sheet.dart`
- Test: `mobile apps/titan-hub/test/shared/design_system/overlays/titan_action_sheet_test.dart`

**Interfaces:**
- Produces: `TitanActionSheetItem(id, label, icon, isDestructive, enabled)`.
- Produces: `showTitanActionSheet(context, title, message, items) -> Future<String?>`.

- [ ] Write tests for icon actions, destructive labels, disabled actions, selected ID return, safe cancellation and expanded-width constraint.
- [ ] Run the focused test and confirm missing overlay API.
- [ ] Implement `showModalBottomSheet` with safe area, drag handle, 48px rows and a maximum content width on expanded screens.
- [ ] Run the focused test and analyzer.
- [ ] Commit `feat(titan-hub): add typed native action sheet`.

### Task 6: Component showcase and route

**Files:**
- Create: `mobile apps/titan-hub/lib/shared/design_system/showcase/titan_design_system_showcase.dart`
- Modify: `mobile apps/titan-hub/lib/routes/routes.dart`
- Modify: `mobile apps/titan-hub/lib/routes/route_pages.dart`
- Test: `mobile apps/titan-hub/test/shared/design_system/showcase/titan_design_system_showcase_test.dart`

**Interfaces:**
- Produces: `Routes.titanDesignSystemShowcase`.
- Produces: `TitanDesignSystemShowcase` using only public component APIs.

- [ ] Write a widget test asserting buttons, cards, lists, form fields, status banners, loading/empty/error/offline states and action-sheet launcher are present in light and dark themes.
- [ ] Run the focused test and confirm missing showcase/route.
- [ ] Implement a responsive showcase with constrained content and section headers.
- [ ] Add the dedicated GetX route without placing it in primary customer navigation.
- [ ] Run focused test and analyzer.
- [ ] Commit `feat(titan-hub): add native component showcase`.

### Task 7: Component map, migration guide and licence provenance

**Files:**
- Create: `docs/titan-hub/MOBILEKIT_COMPONENT_MAP.md`
- Create: `docs/titan-hub/DESIGN_SYSTEM_MIGRATION.md`
- Modify: `docs/titan-hub/FLUTTER_ARCHITECTURE.md`
- Modify: `mobile apps/titan-hub/README.md`

**Interfaces:**
- Documents donor source pattern -> Titan component -> intended migration target.
- Documents internal licence/provenance boundary and prohibits runtime donor dependencies.

- [ ] Record selected MobileKit files and the native Titan component replacing each pattern.
- [ ] Provide before/after migration examples for donor cards, list rows, text fields, status messages and action sheets.
- [ ] Document accessibility, dark-mode and responsive requirements for future feature work.
- [ ] Update architecture and README links.
- [ ] Commit `docs(titan-hub): map MobileKit to native components`.

### Task 8: Final verification and merge

**Files:**
- Modify only files required by fresh verification findings.

**Interfaces:**
- Consumes the complete public design-system API.
- Produces a merge-ready Issue #256 PR.

- [ ] Run permanent Titan Hub CI against the branch.
- [ ] Confirm `flutter pub get` leaves `pubspec.lock` unchanged.
- [ ] Confirm Titan-owned formatting passes.
- [ ] Confirm `flutter analyze` passes.
- [ ] Confirm every existing and new test passes.
- [ ] Confirm no HTML, Bootstrap runtime or MobileKit asset path is imported by Flutter production code.
- [ ] Confirm all temporary patch/sync workflows are absent.
- [ ] Merge current `main` into the branch and rerun the full gate if the branch is behind.
- [ ] Update PR evidence, mark ready, squash-merge to `main`, verify Issue #256 closes, and read a design-system file from `main`.
