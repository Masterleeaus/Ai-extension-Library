# Titan Hub Flutter

Titan Hub is Titan Zero's customer-facing native Flutter application. It is built from the sanitised QRPay Flutter v5.1.0 donor and composes one customer app from a business manifest rather than creating separate vertical app forks.

## Current foundation

- Flutter 3.27.0 and Dart 3.6+
- GetX routing and state retained from the donor
- five-position responsive shell: Home, Explore, Hub QR, Activity, Account
- nine canonical vertical profiles
- one primary plus multiple supporting App Modes
- deterministic manifest-driven capability navigation
- encrypted session storage through `flutter_secure_storage`
- credential and personal-data redaction for retained logging
- typed feature flags for retained QRPay flows
- compact bottom navigation and medium/expanded navigation rail
- Titan Hub Android, iOS and Flutter display identity

## Run locally

```bash
flutter pub get
flutter analyze
flutter test
flutter run
```

Signing files, Firebase service configuration and live environment credentials are intentionally absent. Supply deployment configuration outside Git before building signed Android or iOS releases.

## Architecture

See [`docs/titan-hub/FLUTTER_ARCHITECTURE.md`](../../docs/titan-hub/FLUTTER_ARCHITECTURE.md) for module boundaries, manifest composition, backend authority, navigation resolution, session security and testing requirements.

## Important boundaries

- Flutter presents governed backend capabilities; it is not the financial or operational source of truth.
- Titan Pay owns immutable financial events and ledger truth.
- WorkCore owns operational execution state.
- Commerce extensions own product, service, booking and order rules.
- Chatbot and agents own conversational tool invocation.
- MobileKit is a design reference only; production UI must remain native Flutter.

The technical Dart package remains `qrpay` until a dedicated import and native bundle migration is implemented with automated coverage.
