# Titan Hub Flutter Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the imported QRPay Flutter donor into a tested modular Titan Hub foundation with manifest-driven vertical/App Mode composition, five-destination responsive navigation, secure token storage, redacted logging and Titan Hub identity.

**Architecture:** Retain GetX and existing QRPay routes while introducing focused `app/`, `core/`, `features/` and `shared/` boundaries. A backend-shaped `BusinessAppManifest` is normalised and passed through a deterministic destination registry; the responsive shell renders core destinations while retained QRPay capabilities remain feature-flagged.

**Tech Stack:** Flutter 3.27, Dart >=3.6.0, GetX 4.6.6, GetStorage 2.1.1, flutter_secure_storage 9.x, flutter_test.

## Global Constraints

- Use the nine canonical vertical slugs from `docs/titan-create/vertical-coverage.md`.
- Support one primary App Mode and multiple supporting App Modes.
- Do not create vertical app forks.
- Duplicate or adapt the closest existing QRPay UI pattern before introducing unrelated UI.
- Keep Laravel, WorkCore and Titan Pay as authoritative backends; Flutter is a governed client.
- Do not commit signing material, Firebase service credentials, tokens or secrets.
- Preserve retained QRPay financial routes behind feature flags.
- `flutter analyze` and `flutter test` must pass before merge.

---

### Task 1: Manifest composition domain

**Files:**
- Create: `mobile apps/titan-hub/lib/core/manifest/vertical_slug.dart`
- Create: `mobile apps/titan-hub/lib/core/manifest/app_mode.dart`
- Create: `mobile apps/titan-hub/lib/core/manifest/business_app_manifest.dart`
- Create: `mobile apps/titan-hub/lib/core/manifest/manifest_repository.dart`
- Test: `mobile apps/titan-hub/test/core/manifest/business_app_manifest_test.dart`

**Interfaces:**
- Produces: `VerticalSlug.normalize(String?) -> String?`
- Produces: `AppMode.normalize(String?) -> String?`
- Produces: `BusinessAppManifest.fromJson(Map<String, dynamic>)`
- Produces: `ManifestRepository.load() -> Future<BusinessAppManifest>`

- [ ] Write tests for canonical slugs, legacy aliases, one primary plus supporting modes, unknown-value filtering and fallback branding.
- [ ] Run `flutter test test/core/manifest/business_app_manifest_test.dart` and confirm failure because types do not exist.
- [ ] Implement immutable manifest value objects with deterministic list de-duplication.
- [ ] Add a local default manifest for `field-home-services + service + booking + quote_first`.
- [ ] Run the focused test and confirm pass.
- [ ] Commit `feat(titan-hub): add manifest composition domain`.

### Task 2: Deterministic destination registry

**Files:**
- Create: `mobile apps/titan-hub/lib/core/navigation/titan_destination.dart`
- Create: `mobile apps/titan-hub/lib/core/navigation/manifest_navigation_resolver.dart`
- Test: `mobile apps/titan-hub/test/core/navigation/manifest_navigation_resolver_test.dart`

**Interfaces:**
- Consumes: `BusinessAppManifest`
- Produces: `ManifestNavigationResolver.resolve(BusinessAppManifest) -> List<TitanDestination>`
- Produces stable IDs: `home`, `explore`, `hub-qr`, `activity`, `account`

- [ ] Write failing tests for five core destinations, two different vertical profiles, multiple supporting modes and collision priority.
- [ ] Run focused navigation tests and confirm failure.
- [ ] Implement destination contributions for the nine verticals and canonical App Modes without creating screen forks.
- [ ] Merge duplicate IDs by priority, source rank and lexical ID.
- [ ] Run focused tests and confirm pass.
- [ ] Commit `feat(titan-hub): resolve manifest navigation deterministically`.

### Task 3: Secure session storage and redacted diagnostics

**Files:**
- Modify: `mobile apps/titan-hub/pubspec.yaml`
- Create: `mobile apps/titan-hub/lib/core/security/session_token_store.dart`
- Create: `mobile apps/titan-hub/lib/core/security/secure_key_value_store.dart`
- Modify: `mobile apps/titan-hub/lib/backend/local_storage/local_storage.dart`
- Create: `mobile apps/titan-hub/lib/core/logging/titan_logger.dart`
- Modify: `mobile apps/titan-hub/lib/backend/utils/logger.dart`
- Test: `mobile apps/titan-hub/test/core/security/session_token_store_test.dart`
- Test: `mobile apps/titan-hub/test/core/logging/titan_logger_test.dart`

**Interfaces:**
- Produces: `SessionTokenStore.writeSession(accessToken, refreshToken, expiresAt)`
- Produces: `SessionTokenStore.rotateSession(...)`
- Produces: `SessionTokenStore.readSession()`
- Produces: `TitanLogRedactor.redact(Object?) -> String`

- [ ] Write failing in-memory storage tests for session persistence, rotation and clear.
- [ ] Write failing redaction tests for bearer tokens, passwords, cookies, card numbers and email values.
- [ ] Add `flutter_secure_storage` and implement an injectable secure-store adapter.
- [ ] Keep non-sensitive preferences in GetStorage and stop writing tokens there.
- [ ] Delegate the legacy logger entry point to the redacting logger.
- [ ] Run focused security/logging tests and confirm pass.
- [ ] Commit `fix(titan-hub): secure sessions and redact diagnostics`.

### Task 4: Titan Hub app root and responsive shell

**Files:**
- Create: `mobile apps/titan-hub/lib/app/titan_hub_bootstrap.dart`
- Create: `mobile apps/titan-hub/lib/app/titan_hub_app.dart`
- Create: `mobile apps/titan-hub/lib/shared/layout/titan_breakpoints.dart`
- Create: `mobile apps/titan-hub/lib/features/shell/titan_hub_shell_controller.dart`
- Create: `mobile apps/titan-hub/lib/features/shell/titan_hub_shell.dart`
- Create: `mobile apps/titan-hub/lib/features/home/titan_home_screen.dart`
- Create: `mobile apps/titan-hub/lib/features/explore/titan_explore_screen.dart`
- Create: `mobile apps/titan-hub/lib/features/activity/titan_activity_screen.dart`
- Create: `mobile apps/titan-hub/lib/features/account/titan_account_screen.dart`
- Modify: `mobile apps/titan-hub/lib/main.dart`
- Modify: `mobile apps/titan-hub/lib/routes/routes.dart`
- Modify: `mobile apps/titan-hub/lib/routes/route_pages.dart`
- Test: `mobile apps/titan-hub/test/features/shell/titan_hub_shell_test.dart`

**Interfaces:**
- Consumes: `ManifestRepository`, `ManifestNavigationResolver`
- Produces: `TitanHubApp`
- Produces: `/titanHubShell`

- [ ] Write widget tests for Titan Hub title, compact bottom navigation, expanded navigation rail and central Hub QR action.
- [ ] Run focused widget tests and confirm failure.
- [ ] Extract startup responsibilities from `main.dart` into `TitanHubBootstrap` and remove portrait-only locking.
- [ ] Build the shell by adapting the donor bottom-navigation pattern, retaining GetX state.
- [ ] Route Home, Explore, Activity and Account to focused feature screens and Hub QR to the retained QR scanner route.
- [ ] Run focused widget tests and confirm pass.
- [ ] Commit `feat(titan-hub): add responsive manifest-driven shell`.

### Task 5: Visible identity and retained-flow feature flags

**Files:**
- Modify: `mobile apps/titan-hub/lib/language/english.dart`
- Modify: `mobile apps/titan-hub/android/app/src/main/AndroidManifest.xml`
- Modify: `mobile apps/titan-hub/ios/Runner/Info.plist`
- Create: `mobile apps/titan-hub/lib/core/features/titan_feature_flags.dart`
- Test: `mobile apps/titan-hub/test/core/features/titan_feature_flags_test.dart`

**Interfaces:**
- Produces: `TitanFeatureFlags.fromManifest(BusinessAppManifest)`
- Produces flags for retained QR, wallet, payment, KYC, biometrics, receipts, transaction history, notifications, localisation and hosted checkout.

- [ ] Write failing tests for default retained-flow flags and explicit manifest overrides.
- [ ] Implement typed flags and expose them through the shell controller.
- [ ] Replace customer-visible QRPay app-name strings with Titan Hub while preserving technical package imports.
- [ ] Change safe Android/iOS display names to Titan Hub without adding signing configuration.
- [ ] Run focused tests and confirm pass.
- [ ] Commit `feat(titan-hub): apply identity and retained-flow flags`.

### Task 6: Documentation and full verification

**Files:**
- Create: `docs/titan-hub/FLUTTER_ARCHITECTURE.md`
- Modify: `mobile apps/titan-hub/README.md`
- Modify: `mobile apps/titan-hub/test/widget_test.dart`

**Interfaces:**
- Documents manifest ownership, route ownership, security boundaries, responsive breakpoints and retained-flow flags.

- [ ] Document the module map and future backend manifest contract.
- [ ] Replace the donor placeholder widget test with a Titan Hub smoke test.
- [ ] Run `flutter pub get`.
- [ ] Run `dart format --set-exit-if-changed lib test`.
- [ ] Run `flutter analyze`.
- [ ] Run `flutter test`.
- [ ] Review changed files for QRPay-visible branding, plaintext token storage and sensitive logging.
- [ ] Commit `docs(titan-hub): document Flutter foundation`.
- [ ] Open a PR with verification evidence, merge it to `main`, close Issue #255 and delete the branch.
