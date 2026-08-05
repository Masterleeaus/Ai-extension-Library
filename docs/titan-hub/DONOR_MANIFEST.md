# Titan Hub Donor Manifest

This file is the authoritative inventory for the licensed donor systems used to build Titan Hub and Titan Pay.

## Donor archives

| Donor | Target | Archive size | SHA-256 | Intended authority |
|---|---|---:|---|---|
| QRPay Flutter User App v5.1.0 | `mobile apps/titan-hub/` | ~2.8 MB | `77d00027b7771e219ac1a74fbea4b7e1029e905d36afe3fcb97d1b2f557f6ec8` | Titan Hub customer-facing mobile shell and reusable payment journeys |
| QRPay Laravel Web v5.1.0 | `integrations/qrpay-web/` | ~54 MB | `412765575258c3c17f9da652d225306b5cbab3cd69f4953b65e02a4980693037` | Donor for payment, wallet, QR, merchant, agent, KYC and gateway workflows |
| MobileKit Bootstrap 4 UI Kit | `mobile apps/mobilekit-reference/` | ~3.9 MB | `a2f9bda06a35a2d82217692bdff736a8b901cef795422fc98f5816f667c6a736` | Visual and interaction reference only; components must be recreated natively in Flutter |
| Cryptomus extension | future Titan Pay crypto adapter | archive supplied | `5ad8aa0794438070f1c550cbaf8f2bba0935950bda3126d64488b14475e17bb4` | Optional crypto rail only; not wallet, invoice or ledger authority |

## Verified source inventory

### QRPay Flutter user app

- Flutter 3.27 / Dart 3.6 generation.
- 387 Dart files.
- 102 `*_screen.dart` files.
- 89 registered GetX routes.
- 60 controllers.
- 118 views.
- Existing product areas include wallet, QR scan/display, send/receive money, merchant payment, payment requests, payment links, add money, withdrawals, remittance, agent cash-out, KYC, 2FA, biometrics, receipts, notifications, gift cards, bill payment, mobile top-up and virtual cards.
- Current source layout is organised by technical layers (`controller`, `backend`, `model`, `views`, `routes`, `bindings`) rather than isolated feature packages.
- Existing screens and widgets are donors. New Titan UI must first duplicate the closest existing implementation and edit the duplicate rather than introducing visually disconnected files.

### QRPay Laravel web system

- Laravel 9.52.18 and PHP 8+.
- 1,438 PHP files.
- 130 migrations.
- 93 models.
- 179 controllers.
- User, merchant, agent and admin application areas.
- Existing product areas include wallets, transfers, merchant payment, payment links, money requests, add money, withdrawals, remittance, agent cash flows, transaction limits, fees, KYC, 2FA, gateway configuration, merchant APIs, virtual cards, gift cards, bill payments and mobile top-up.
- Gateway/provider code includes PayPal, Stripe, Paystack, Razorpay, Flutterwave, Bkash, Pagadito, SSLCommerz, Perfect Money, CoinGate and Tatum patterns.
- The donor currently mutates wallet balances directly and is not event-sourced. These mechanisms must not become Titan Pay financial authority.

### MobileKit

- Bootstrap 4 mobile web UI kit.
- Intended for component and interaction reference only.
- HTML, CSS and Bootstrap runtime code must not be embedded as the production Flutter interface.
- Useful pages should be mapped to the closest existing Flutter page, duplicated, and then adapted into native Flutter components.

## Ownership boundaries

| Capability | Authoritative Titan owner | Donor contribution |
|---|---|---|
| Customer-facing app | Titan Hub Flutter | QRPay navigation, screens, QR, wallet and payment journeys |
| Identity and tenancy | MagicAI / Titan Zero core | QRPay user, merchant and agent concepts mapped through adapters |
| Products, services, booking, rental and hire | Titan commerce APIs / WorkCore | Existing Laravel commerce extensions |
| Quotes and pricing | Titan Commercial Engine | DiscountManager rules and calculation patterns |
| Invoices and receivables | Titan Pay / WorkCore authority map | QRPay payment presentation and transaction views |
| Wallet and payment state | Titan Pay event streams and ledger | QRPay workflows and provider integration knowledge |
| Crypto | Cryptomus adapter | Hosted crypto checkout and verified provider events |
| UI styling | Existing Flutter app plus native Titan design system | MobileKit as reference only |

## Confirmed sensitive and generated paths

### QRPay Flutter

Remove and replace before commit or build:

- `android/key.properties`
- `android/app/key.jks`
- `android/app/google-services.json`
- `ios/Runner/GoogleService-Info.plist`
- `__MACOSX/` metadata
- `.DS_Store`
- generated `build/` and `.dart_tool/` directories

New Titan-owned Android/iOS signing and Firebase configuration must be generated outside Git.

### QRPay Laravel

Remove and replace before commit or deployment:

- `.env`
- `storage/oauth-private.key`
- `storage/oauth-public.key`
- generated logs, cache and session data
- root Composer `vendor/`

`.env.example` may remain only after confirming it contains placeholders. Published framework view overrides under `resources/views/vendor/` are source files and must not be confused with root Composer dependencies.

## Required security corrections after import

- Replace mobile bearer-token storage with secure platform storage.
- Remove or redact sensitive request and response logging.
- Replace direct wallet balance mutation with event-sourced commands, ledger postings and projections.
- Add optimistic concurrency and row-safe transaction handling during migration.
- Authenticate, store and deduplicate provider webhooks before domain processing.
- Replace simple payment QR identifiers with signed, scoped and expiring payment sessions.
- Rotate every credential found in a donor archive, even if it appears to be demo data.
- Verify tenant isolation across customer, merchant, agent and administrator operations.

## Import policy

1. Verify every archive hash before extraction.
2. Require a clean Git working tree.
3. Extract into temporary staging directories.
4. Remove credentials, signing material, generated output, caches, logs, macOS metadata and root dependency directories.
5. Scan source for hard-coded credentials, private keys, API tokens and sensitive logging.
6. Preserve meaningful donor structure; flatten only a single archive wrapper directory.
7. Copy sanitised source into the requested repository folders.
8. Review the generated audit summary and `git diff` before commit.
9. Commit donor source separately from later Titan modifications.
10. Do not close issue #254 until the sanitised source trees are present and independently inspectable on GitHub.

## Transport status

MiniUp publishes static web projects and datasets; it is not a Git transport. The connected GitHub API can create branches, issues and text commits but cannot stream the mounted 54 MB donor archive from this session.

The source import therefore still requires one of:

- authenticated local Git push;
- Git LFS where a retained binary genuinely requires it; or
- a future connector action capable of uploading repository files from mounted paths.

Issue #254 remains open until that source import is completed and verified.