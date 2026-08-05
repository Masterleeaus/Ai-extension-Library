# Titan Hub Donor Manifest

Generated from the supplied licensed archives while working on issue #254.

## Repository status

`Masterleeaus/Ai-extensions` is private as of 2026-08-05. Licensed donor source may be imported into this repository after sanitisation and secret/signing-material removal.

Private repository visibility reduces public-disclosure risk but does not remove licence, access-control, secret-management, or least-privilege requirements.

## Verified donor archives

| Donor | Intended target | SHA-256 |
|---|---|---|
| QRPay Flutter User App v5.1.0 | `mobile apps/titan-hub/` | `77d00027b7771e219ac1a74fbea4b7e1029e905d36afe3fcb97d1b2f557f6ec8` |
| QRPay Laravel Web v5.1.0 | `integrations/qrpay-web/` | `412765575258c3c17f9da652d225306b5cbab3cd69f4953b65e02a4980693037` |
| MobileKit Bootstrap 4 UI Kit | `mobile apps/mobilekit-reference/` | `a2f9bda06a35a2d82217692bdff736a8b901cef795422fc98f5816f667c6a736` |
| Cryptomus extension | future Titan Pay crypto adapter | `5ad8aa0794438070f1c550cbaf8f2bba0935950bda3126d64488b14475e17bb4` |

## Source inventory

### QRPay Flutter user application

Known structure and capabilities from the inspected archive:

- Flutter 3.27 / Dart 3.6+ application
- approximately 387 Dart files
- approximately 102 screen files
- approximately 89 registered GetX routes
- approximately 60 controllers
- wallet, QR, send/receive money, merchant payment, payment request, payment link, add-money, withdrawal, remittance, agent cash-out, transaction history, KYC, 2FA, biometrics, notifications, gift cards, bill payment, top-up, and virtual-card workflows

Target role:

- native Titan Hub customer application
- Titan Pay customer surface
- future vertical profile and commerce-mode overlays
- reusable QRPay screens should be duplicated and adapted rather than replaced with unrelated new UI

### QRPay Laravel web application

Known structure and capabilities from the inspected archive:

- Laravel 9.52.18 / PHP 8+
- approximately 1,438 PHP files
- approximately 130 migrations
- approximately 93 models
- approximately 179 controllers
- user, merchant, agent, admin, wallet, QR, payment link, money request, add-money, withdrawal, remittance, KYC, gateway, transaction limits, merchant API, and cash-agent workflows

Target role:

- donor integration under the MagicAI Laravel base
- payment-product, gateway, wallet-interface, merchant, agent, QR, and transaction-workflow donor
- must not remain the final financial source of truth because direct mutable wallet updates were found

### MobileKit

Target role:

- UI and interaction reference only
- selected HTML/Bootstrap patterns should be recreated as native Flutter widgets by duplicating the closest existing QRPay Flutter pages/components
- do not embed Bootstrap runtime or production WebViews merely to reuse the template

### Cryptomus

Target role:

- optional crypto rail behind Titan Pay's provider-neutral adapter contract
- must not own Titan invoices, wallet balances, ledgers, or customer entitlements

## Confirmed sensitive/generated paths requiring exclusion or replacement

### QRPay Flutter

- `android/key.properties`
- `android/app/key.jks`
- `android/app/google-services.json`
- `ios/Runner/GoogleService-Info.plist`
- `__MACOSX/` metadata

New Titan-owned Android/iOS signing and Firebase configuration must be generated outside Git.

### QRPay Laravel

- `.env`
- `storage/oauth-private.key`
- `storage/oauth-public.key`

`.env.example` may be retained only after confirming it contains placeholders rather than live values. Published framework view overrides under `resources/views/vendor/` are source files and are not equivalent to Composer's root `vendor/` directory.

## Required post-extraction scans

Before committing extracted donor source, scan for:

- `.env*` files containing values rather than placeholders
- private/public signing keys and certificates
- Android keystores and key properties
- Firebase/Google service configuration
- API tokens, bearer tokens, client secrets, passwords, and webhook secrets
- hard-coded domains and vendor callback URLs
- request/response logging of authentication, KYC, payment, or personal data
- direct wallet-balance mutation
- provider callbacks that mutate financial or entitlement state before authentication and idempotency checks
- generated build directories, caches, logs, sessions, dependencies, and IDE metadata
- files exceeding GitHub's size limit

Recommended tools where available:

- `gitleaks`
- `trufflehog filesystem`
- `git grep` with high-risk patterns
- Flutter static analysis
- Composer audit
- PHPStan/Psalm where compatible

## Import policy

1. Confirm the repository remains private and access is restricted to authorised project participants.
2. Verify each archive hash before extraction.
3. Extract into a temporary directory.
4. Remove credentials, signing material, generated output, caches, logs, macOS metadata, and root dependency directories.
5. Scan for hard-coded secrets, domains, tokens, API keys, and sensitive request/response logging.
6. Copy only sanitised source into the requested repository folders.
7. Preserve meaningful source structure.
8. Prefer editing existing files; create new code files only when a separate responsibility requires them.
9. For new UI files, duplicate the closest existing QRPay Flutter screen/component and adapt the duplicate.
10. Commit the scan report and source import separately where practical.
11. Rotate every credential contained in an archive before deployment, regardless of whether it appears to be demo data.
12. Keep purchase codes, licence keys, invoices, and account credentials out of Git and issue discussions.

## Transport status

The repository is now private, resolving the public-disclosure blocker.

The current connected GitHub action set can create branches, issues, pull requests, and text commits, but it cannot stream the mounted 54 MB donor archive or its extracted multi-thousand-file source tree from this session. Completion of issue #254 therefore still requires an authenticated local Git import or a future connector action that accepts mounted file paths.
