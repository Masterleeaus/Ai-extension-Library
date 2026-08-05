# Titan Hub Donor Import and Security Scan

Issue: **#254**  
Import date: **2026-08-05**  
Credential-pattern result: **PASS**

## Imported targets

| Donor | Target | Files | Bytes |
|---|---|---:|---:|
| QRPay Flutter | `mobile apps/titan-hub/` | 561 | 4,058,611 |
| QRPay Laravel | `integrations/qrpay-web/` | 1,959 | 105,362,162 |
| MobileKit | `mobile apps/mobilekit-reference/` | 214 | 6,938,559 |

## Removed or replaced security material

- Android signing keystore and signing properties
- Android and iOS Firebase service configuration
- generated Flutter Firebase options containing donor API keys and project identifiers
- Laravel `.env` containing an application key and Pusher credentials
- Laravel OAuth private/public key pair
- generated caches, sessions, logs, IDE metadata and dependency directories
- demo/user-uploaded support-ticket attachments

The required Firebase configuration shape and Laravel `.env.example` were retained with Titan-safe placeholders.

## Credential scan

No private-key blocks, Google API keys, AWS access keys, OpenAI/Stripe secret keys, GitHub tokens or JWT-shaped bearer credentials were detected after sanitisation.

## Known hard-coded integration and vendor references

Non-secret domains and vendor names remain in donor source for provenance and behaviour analysis. They must be replaced or isolated behind Titan adapters during implementation.

- `ckeditor.com` — 203 reference(s)
- `pub.dev` — 194 reference(s)
- `api.github.com` — 158 reference(s)
- `packagist.org` — 158 reference(s)
- `symfony.com` — 87 reference(s)
- `tidelift.com` — 53 reference(s)
- `purl.org` — 20 reference(s)
- `appdevs.cloud` — 16 reference(s)
- `fontawesome.com` — 16 reference(s)
- `js.pusher.com` — 14 reference(s)
- `richtexteditor.com` — 14 reference(s)
- `creativecommons.org` — 10 reference(s)
- `popper.js.org` — 10 reference(s)
- `fonts.gstatic.com` — 9 reference(s)
- `www.apple.com` — 8 reference(s)
- `www.patreon.com` — 8 reference(s)
- `www.php-fig.org` — 8 reference(s)
- `opensource.org` — 6 reference(s)
- `rawgit.com` — 6 reference(s)
- `www.doctrine-project.org` — 6 reference(s)
- `developers.google.com` — 5 reference(s)
- `ionic.io` — 5 reference(s)
- `pqina.nl` — 5 reference(s)
- `www.colinodell.com` — 5 reference(s)
- `www.google.com` — 5 reference(s)
- `api.paystack.co` — 4 reference(s)
- `chart.googleapis.com` — 4 reference(s)
- `developer.mozilla.org` — 4 reference(s)
- `flareapp.io` — 4 reference(s)
- `icons.getbootstrap.com` — 4 reference(s)

Vendor branding counts:
- `appdevs` — 69
- `codecanyon` — 4
- `mobilekit` — 245
- `qrpay` — 2508
- `themeforest` — 12

## File-size verification

- Total imported files: **2,737**
- Total imported bytes: **116,388,509**
- Files over 95 MiB: **0**

No Git LFS object is required by this import.

## Authority boundaries

- Flutter owns presentation and customer interaction only.
- QRPay Laravel is a bounded donor/reference; it does not own Titan invoices, ledgers, wallet balances or settlement truth.
- MobileKit is a visual reference and must not be embedded as a production WebView.
- Chatbot tools and Flutter screens must use the same governed Titan application commands.
