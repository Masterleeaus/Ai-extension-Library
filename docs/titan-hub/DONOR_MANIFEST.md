# Titan Hub Donor Manifest

This is the canonical source inventory for issue #254.

## Repository and licence boundary

`Masterleeaus/Ai-extensions` is private. The imported QRPay and MobileKit sources are licensed donor/reference material. Purchase credentials, licence keys, invoices and account secrets are not stored in Git.

## Verified and imported archives

| Donor | Version | Imported target | Original SHA-256 | Status |
|---|---:|---|---|---|
| QRPay Flutter User App | 5.1.0 | `mobile apps/titan-hub/` | `77d00027b7771e219ac1a74fbea4b7e1029e905d36afe3fcb97d1b2f557f6ec8` | Sanitised source imported |
| QRPay Laravel Web | 5.1.0 | `integrations/qrpay-web/` | `412765575258c3c17f9da652d225306b5cbab3cd69f4953b65e02a4980693037` | Sanitised source imported |
| MobileKit Bootstrap 4 UI Kit | 2.9.1 | `mobile apps/mobilekit-reference/` | `a2f9bda06a35a2d82217692bdff736a8b901cef795422fc98f5816f667c6a736` | Sanitised reference imported |
| Cryptomus extension | supplied donor | future provider adapter | `5ad8aa0794438070f1c550cbaf8f2bba0935950bda3126d64488b14475e17bb4` | Not imported in #254 |

## Imported source roles

### `mobile apps/titan-hub/`

Native Flutter donor and starting point for Titan Hub. Existing QRPay screens, controllers, routes, widgets and services must be duplicated and adapted where practical. Titan Hub adds the Vertical Profile and one-or-more Commerce Mode overlays without converting Laravel extensions into Flutter code.

### `integrations/qrpay-web/`

Laravel donor/reference for QR, merchant, agent, wallet-interface, gateway, transfer and transaction workflows. It remains bounded behind Titan adapters and must not become the authoritative Titan Pay ledger.

### `mobile apps/mobilekit-reference/`

HTML, asset and design reference. Production equivalents must be recreated as native Flutter widgets; do not embed the reference kit as production WebViews.

## Sanitisation result

- donor archive hashes verified before import;
- Android signing files removed;
- Firebase Android/iOS configuration removed;
- generated Flutter Firebase options rewritten with placeholders;
- Laravel live/demo `.env` removed and `.env.example` sanitised;
- OAuth key pair removed;
- dependencies, generated builds, caches, sessions, logs, IDE and macOS metadata removed;
- support-ticket user/demo attachments removed;
- source routes, models, migrations, views and application lockfiles retained;
- no imported file exceeds GitHub's file-size limit.

See `docs/titan-hub/DONOR_SECURITY_SCAN.md` and `.json` for evidence and remaining non-secret domain/vendor references.

## Mandatory implementation boundaries

1. MagicAI owns identity, tenancy and extension lifecycle.
2. E-commerce extensions own catalogue and transaction rules.
3. Chatbot remains the conversational and tool foundation.
4. WorkCore owns operational records and execution state.
5. Titan Pay owns immutable financial events and ledger truth.
6. Flutter renders governed capabilities and never mutates financial or operational authority directly.
