# Titan Hub Flutter Architecture

## Purpose

`mobile apps/titan-hub/` is the customer-facing native Flutter application for Titan Zero. It is one governed app binary that changes shape from a resolved business manifest. It is not a collection of separate vertical forks.

The imported QRPay Flutter app remains the donor foundation for proven authentication, QR, wallet-interface, payment, KYC, biometrics, receipt, notification, localisation and hosted-checkout flows. New Titan Hub modules reuse those routes and widgets while authority remains in Laravel, WorkCore and Titan Pay.

## Ownership boundaries

| Concern | Authority |
|---|---|
| Identity, tenancy, entitlements and extension lifecycle | MagicAI / Titan Zero Laravel host |
| Catalogue, products, services, bookings, orders and commerce rules | Titan commerce extensions |
| Operational jobs, resources, staff and execution state | WorkCore |
| Immutable financial events, ledger truth and settlement state | Titan Pay |
| Conversational actions and tool invocation | Chatbot / agent foundation |
| Customer presentation, local navigation and device capabilities | Titan Hub Flutter |

Flutter must not directly mutate authoritative financial or operational records. It calls governed backend capabilities and renders their result.

## Module map

```text
lib/
├── app/
│   ├── titan_hub_app.dart
│   └── titan_hub_bootstrap.dart
├── core/
│   ├── features/
│   ├── logging/
│   ├── manifest/
│   ├── navigation/
│   └── security/
├── features/
│   ├── account/
│   ├── activity/
│   ├── explore/
│   ├── home/
│   └── shell/
├── shared/
│   └── layout/
└── retained QRPay donor controllers, routes, views and widgets
```

`app/` owns startup and application identity. `core/` contains framework-independent contracts. `features/` contains customer surfaces. `shared/` contains reusable presentation foundations. Existing QRPay folders remain available while later issues progressively move responsibilities behind Titan-owned interfaces.

## Manifest composition

`BusinessAppManifest` is shaped like the future Laravel-resolved customer-app manifest. It contains:

- business profile ID;
- one primary vertical;
- optional secondary verticals;
- one primary App Mode;
- optional supporting App Modes;
- branding values;
- retained-flow feature flags;
- explicit navigation overrides.

### Canonical verticals

1. `field-home-services`
2. `accommodation-rooming`
3. `real-estate`
4. `salons-personal-care`
5. `fitness-membership`
6. `automotive-services`
7. `ecommerce-retail`
8. `hire-rental`
9. `booking-capacity`

Legacy aliases are normalised at the manifest boundary. Unknown values are ignored safely.

### App Modes

The current catalogue supports service, quote-first, booking, reservation, capacity booking, classes, event ticketing, transport booking, accommodation, sales, order-ahead, hire, rental, membership, subscription, marketplace and application flows.

A business can activate several modes at once. For example, a field-service business may use `service` as its primary mode with `booking` and `quote_first` as supporting modes.

## Navigation resolution

`ManifestNavigationResolver` combines:

1. five core destinations;
2. primary-vertical contributions;
3. secondary-vertical contributions;
4. primary-mode contributions;
5. supporting-mode contributions;
6. explicit manifest navigation.

Stable core destination IDs are:

- `home`
- `explore`
- `hub-qr`
- `activity`
- `account`

Duplicate capability IDs are resolved deterministically by priority, source rank, source name and route. Supporting verticals and modes are sorted before contribution, so input ordering does not change the resolved output.

The bottom navigation and navigation rail always contain the five core positions. Vertical and App Mode capabilities are presented through Explore rather than overflowing primary navigation.

## Responsive shell

- Compact: width below 600; Material `NavigationBar`.
- Medium: 600–1023; labelled `NavigationRail`.
- Expanded: 1024 and above; extended `NavigationRail`.

Portrait-only locking has been removed. Android and iOS declare landscape support where their existing project configuration permits it.

The centre Hub QR action reuses the retained QR scanner route. It does not create a second scanner implementation.

## Secure session storage

Tokens are not stored in `GetStorage`.

`SessionTokenStore` writes one encrypted JSON session bundle through `flutter_secure_storage`, containing:

- access token;
- refresh token;
- UTC expiry.

Writing or rotating a session replaces the bundle atomically. The bootstrap migrates a legacy plaintext access token once, removes the plaintext value and keeps only a process-memory compatibility cache for retained donor request code.

Non-sensitive preferences remain in `GetStorage`.

## Diagnostic redaction

`TitanLogRedactor` removes or masks:

- authorization headers and bearer tokens;
- access, refresh and ID tokens;
- passwords, PINs and secrets;
- cookies;
- email addresses;
- card-number patterns;
- recursively nested sensitive map keys.

The legacy `logger(Type)` entry point delegates to the redacting logger so retained donor call sites receive protection without a repository-wide rewrite.

## Retained-flow feature flags

`TitanFeatureFlags` exposes typed flags for:

- QR;
- wallet interface;
- payments;
- KYC;
- biometrics;
- receipts;
- transaction history;
- notifications;
- localisation;
- hosted checkout.

Proven donor flows default to enabled. A backend manifest can explicitly disable individual capabilities. Unknown flags default to disabled; destinations without a flag are allowed.

## Visible identity and compatibility

Customer-visible identity is Titan Hub in:

- Flutter application title;
- shared app-name string;
- Android application label;
- iOS display and bundle names;
- package description and documentation.

The Dart package name remains `qrpay` in this issue to avoid breaking hundreds of existing imports without a dedicated migration. Android/iOS bundle identifiers, namespaces, signing material and Firebase configuration are also unchanged until deployment-owned credentials are supplied.

## Testing and CI

Focused tests cover:

- vertical and App Mode normalisation;
- manifest parsing and defaults;
- deterministic navigation resolution;
- multiple simultaneous modes;
- secure session persistence, rotation and clearing;
- credential and personal-data redaction;
- retained-flow feature flags;
- compact and expanded navigation;
- central Hub QR behaviour;
- composed Explore capabilities.

Required verification from `mobile apps/titan-hub/`:

```bash
flutter pub get
dart format --output=none --set-exit-if-changed <changed Dart files>
flutter analyze
flutter test
```

The repository workflow runs with Flutter 3.27.0, matching the donor project constraint.

## Next integration steps

Issue #256 should build the native Titan design system using the MobileKit reference while retaining these shell and composition contracts. Later backend issues will replace `ManifestRepository.fallbackManifest` with the authenticated Laravel manifest endpoint and route each capability through authoritative commerce, WorkCore, chatbot and Titan Pay services.
