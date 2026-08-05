# Titan Hub Flutter Foundation Design

## Status

Approved for implementation through Issue #255 and the user's instruction to continue the established Titan Hub plan.

## Goal

Convert the imported QRPay Flutter donor into the first modular Titan Hub customer-app foundation without discarding reusable QRPay flows. The app must support one binary that composes a customer experience from a primary vertical, optional secondary vertical capabilities, one primary App Mode, and multiple supporting App Modes.

## Scope boundary

This issue establishes the mobile composition, navigation, branding, security-storage, logging, responsive-layout and feature-flag foundations. It does not implement the Laravel app-manifest endpoint, final commerce flows, Titan Pay ledger integration, WorkCore integration, or advanced chatbot agents.

## Architecture

The existing GetX application remains the host architecture for this issue. New Titan Hub code is introduced under focused `app/`, `core/`, `features/` and `shared/` boundaries while retained QRPay screens stay in place behind capability flags.

The root application delegates startup and app identity to `TitanHubApp`. A `BusinessAppManifest` model represents backend-resolved configuration. `ManifestNavigationResolver` merges core destinations, vertical contributions and App Mode contributions deterministically. The shell renders Home, Explore, Hub QR, Activity and Account without creating separate vertical app forks.

## Canonical composition contract

- Nine canonical vertical slugs are accepted exactly as documented in `docs/titan-create/vertical-coverage.md`.
- Legacy aliases are normalised before composition.
- A manifest has one primary vertical and may include secondary verticals.
- A manifest has one primary App Mode and may include supporting App Modes.
- Navigation contributions use stable destination IDs.
- Duplicate IDs are merged by priority, then source rank, then lexical ID so output is deterministic.
- Core destinations always remain available unless explicitly disabled by a future governed manifest version.
- Unknown verticals or modes are ignored safely and recorded through redacted diagnostics.

## Components

### App composition

`TitanHubApp` owns application title, themes, route registration, bindings and responsive builder behaviour.

`TitanHubBootstrap` initialises non-sensitive preferences, secure session storage, notification services and network dependencies before the app starts.

### Manifest domain

`BusinessAppManifest` parses the resolved manifest and exposes immutable branding, vertical, App Mode, navigation, feature-flag and business-profile values.

`VerticalSlug` and `AppMode` provide canonical constants and alias normalisation.

`ManifestRepository` exposes a local fallback manifest now and forms the boundary for the future Laravel endpoint.

### Navigation composition

`TitanDestination` is the stable navigation unit.

`ManifestNavigationResolver` combines:

1. core destinations;
2. primary-vertical destinations;
3. secondary-vertical destinations;
4. primary-mode destinations;
5. supporting-mode destinations;
6. explicit manifest overrides.

The shell presents exactly five primary navigation positions for this issue: Home, Explore, Hub QR, Activity and Account. Feature-specific destinations remain discoverable through Explore and route registration rather than overflowing the bottom bar.

### Security

Access and refresh tokens move from `GetStorage` to `flutter_secure_storage`. Non-sensitive preferences remain in `GetStorage`. `SessionTokenStore` supports access-token, refresh-token and expiry persistence plus atomic rotation.

`LocalStorages` keeps its public compatibility methods but delegates token reads and writes to the secure store asynchronously. Call sites that require synchronous access are migrated to the session service during this issue.

### Logging

`TitanLogger` redacts authorization headers, tokens, passwords, secrets, cookies, card data and common personal identifiers before emitting diagnostic messages. Production logging omits verbose payloads. The existing `logger(Type)` entry point delegates to the redacting logger to minimise donor disruption.

### Responsive foundation

Portrait-only locking is removed. The root layout uses breakpoints:

- compact: width below 600;
- medium: 600–1023;
- expanded: 1024 and above.

The primary shell uses a bottom navigation bar on compact widths and a navigation rail on medium/expanded widths. Existing screens remain usable while later issues progressively adopt responsive content layouts.

### Branding and identity

Customer-visible app identity becomes Titan Hub. Dart package imports remain `package:qrpay/` in this issue to avoid a repository-wide import rewrite before automated migration coverage exists. Android/iOS bundle identifiers and signing are not changed until deployment credentials are supplied; visible Android and iOS app names are changed where safe.

## Data flow

1. Bootstrap initialises preferences and secure storage.
2. `ManifestRepository` returns a validated fallback or backend manifest.
3. Alias normalisation converts legacy vertical/mode values.
4. `ManifestNavigationResolver` creates an ordered destination registry.
5. `TitanHubShellController` exposes selected destination and resolved manifest.
6. The responsive shell renders the selected feature surface.
7. Retained financial routes are available only when their feature flags are enabled.

## Error handling

- Invalid JSON falls back to the default manifest and emits a redacted warning.
- Unknown verticals and modes are ignored without crashing.
- Duplicate navigation IDs resolve deterministically.
- Secure-storage failures return a typed `SessionStorageException` and do not fall back to plaintext token storage.
- Missing optional branding values use Titan Hub defaults.
- A manifest with no usable destinations receives the five core destinations.

## Testing

Unit tests cover:

- canonical and legacy vertical normalisation;
- App Mode normalisation;
- manifest parsing and fallback defaults;
- deterministic navigation collision handling;
- multiple simultaneous App Modes;
- two distinct vertical profiles producing distinct contributions;
- sensitive-value redaction;
- session token rotation using an in-memory secure-store adapter.

Widget tests cover:

- Titan Hub identity;
- five core navigation destinations;
- compact bottom navigation;
- expanded navigation rail;
- central Hub QR action;
- feature-flag visibility for retained QRPay flows.

Verification commands are `flutter pub get`, `dart format --set-exit-if-changed .`, `flutter analyze`, and `flutter test` from `mobile apps/titan-hub/`.

## Non-goals

- No vertical-specific app forks.
- No WebView embedding of MobileKit.
- No direct mobile mutation of financial or WorkCore authority.
- No final remote-branding editor.
- No final deep-link catalogue beyond the parsing and routing boundary.
- No deployment signing material in Git.
