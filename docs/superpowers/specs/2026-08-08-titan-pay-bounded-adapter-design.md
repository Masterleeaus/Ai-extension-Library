# Titan Pay QRPay Bounded Adapter Design

## Status

Approved direction for Issue #257: integrate useful QRPay Laravel behaviour into the MagicAI/Titan Zero host through a host-native anti-corruption layer. QRPay remains donor/reference code under `integrations/qrpay-web/`; its application kernel, authentication, tenancy, migrations, user/merchant models and direct financial mutations are not merged into the host.

## Goal

Deliver the first safe Titan Pay backend slice so authenticated MagicAI users can create, list and resolve payment links for their active company through Titan-owned commands and tenant-safe APIs, while preserving a clean boundary for later QR, gateway, webhook and ledger work.

This slice must prove that the integration can be enabled or disabled without replacing MagicAI authentication, breaking host boot, creating core-table collisions, or allowing QRPay donor code to mutate host financial truth directly.

## Evidence and constraints

### Host runtime

MagicAI currently requires PHP `^8.2`, Laravel `^10.0`, Passport `^12.3`, Sanctum `^3.2`, Intervention Image `^3.5`, Pest 2 and PHPUnit 10.

Existing host extensions register config, services, routes and migrations through extension service providers. Existing Titan APIs use `auth:sanctum` plus `titan.company` to bind an authenticated request to company context.

The host user model owns `active_company_id` and company memberships; therefore company context is authoritative for this integration. QRPay `user_id` and `merchant_id` must not be used as tenant boundaries.

The existing Chatbot payment-link tool declares `payments.links.create`, requires approval, and should consume the Titan Pay command/API rather than implementing a second payment-link engine.

### QRPay donor runtime

QRPay requires PHP `^8.0.2`, Laravel `^9.19`, Passport `^10.4`, Sanctum `^3.0` and Intervention Image `^2.7`. Those constraints are not compatible enough for package-level transplantation into the Laravel 10 host.

QRPay's `PaymentLink` model is coupled to donor `User` and `Merchant` models, global `auth()` state, global helper functions and the generic `payment_links` table. Its API controller performs direct Eloquent creation/status updates and depends on QRPay-specific upload, route, currency and response helpers.

The donor migration creates a generic `payment_links` table with a direct foreign key to donor `users`. Importing it unchanged would preserve the wrong ownership model and risks table/name collisions.

## Approaches considered

### A. Merge QRPay Laravel into the MagicAI application

Rejected.

This would require reconciling Laravel 9/10, Passport 10/12, Intervention Image 2/3 and many gateway dependencies while also importing QRPay authentication, routes, models, helpers and migrations. It would create hidden coupling and duplicate authority for users, merchants and financial state.

### B. Run QRPay as a sidecar Laravel application

Rejected for the current product architecture.

A sidecar would isolate dependency conflicts, but it would preserve QRPay's own authentication, tenant concepts, database authority and callback mutation model. Titan Hub and Chatbot would then need to reconcile two business identities and two financial control planes.

### C. Port selected QRPay behaviour into a host-native Titan Pay extension

Selected.

Create a new `App\Extensions\TitanPay` extension using MagicAI's Laravel 10 conventions. The extension exposes stable Titan contracts, stores only Titan-owned integration records in namespaced tables, and translates useful QRPay concepts into host models and commands. QRPay source remains inspectable donor/reference code and is never autoloaded into the host runtime.

This approach keeps the compatibility surface small, lets each donor workflow be adopted incrementally, and creates the correct boundary for Issue #259's immutable finance ledger.

## Architecture

### Extension boundary

Create:

```text
app/extensions/TitanPay/
├── extension.json
├── config/titan_pay.php
├── System/TitanPayServiceProvider.php
├── Contracts/
├── Domain/PaymentLinks/
├── Http/Controllers/Api/
├── Http/Requests/
├── Http/Resources/
├── database/migrations/
├── routes/api.php
└── tests/
```

The extension owns only Titan Pay adapter concerns. It does not own MagicAI identity, WorkCore operational records, commerce product/order truth, Chatbot approval policy or the future immutable financial ledger.

### Runtime enable/disable

`config/titan_pay.php` exposes an `enabled` boolean driven by `TITAN_PAY_ENABLED` and defaults to enabled for installed environments. The service provider always remains safe to register; routes and integration services are available only when the extension is enabled.

Disabling Titan Pay must not alter host authentication, boot, migrations from unrelated extensions, or existing commerce/chatbot behaviour.

### Tenant context

All authenticated API routes use:

```php
['auth:sanctum', 'titan.company']
```

The request's active company is the tenancy boundary. No public method accepts an arbitrary `company_id` from client input for authorization.

Domain commands receive a typed `CompanyContext`/resolved company identifier supplied by the host middleware/controller boundary. Repositories scope every read and write by that company identifier.

A user cannot list, fetch, mutate or resolve a payment link owned by another company even if they know its UUID/token.

### Authorization

The first write command requires the `payments.links.create` capability. The controller must authorize through the host permission boundary before dispatching the command.

Read operations require authenticated company membership and the corresponding payment-link read permission when the host permission adapter exposes it. The design must not invent a parallel QRPay role system.

Chatbot continues to require its own approval workflow before invoking the same Titan Pay command/tool contract.

## First vertical slice: payment links

### Commands and queries

The public application boundary is explicit and framework-light:

```php
CreatePaymentLinkCommand
ListPaymentLinksQuery
GetPaymentLinkQuery
DeactivatePaymentLinkCommand
```

The first command accepts business data, not QRPay model fields:

```text
company context
actor user id
idempotency key
kind: open_amount | fixed_amount
currency code
currency display metadata supplied/resolved by Titan
country code
label/title
description
optional min/max amount
optional fixed unit amount
optional quantity
```

The application service validates invariants, normalizes money to decimal strings/minor-unit-safe values, creates a cryptographically random public token, persists a Titan-owned record and returns a DTO.

No command writes wallet balances, marks invoices paid, grants entitlements or posts ledger entries.

### Data model

Create a namespaced table:

```text
titan_pay_payment_links
```

Required columns:

- `id` bigint primary key;
- `uuid` UUID, globally unique external identifier;
- `company_id` indexed tenant owner;
- `created_by_user_id` nullable/indexed audit actor;
- `kind` string (`open_amount` or `fixed_amount`);
- `currency_code` char/string;
- `currency_name` nullable string;
- `currency_symbol` nullable string;
- `country_code` nullable string;
- `public_token_hash` fixed string, unique;
- `public_token_hint` short nullable display/debug hint that cannot recreate the token;
- `title` string;
- `description` nullable text;
- `min_amount` nullable decimal;
- `max_amount` nullable decimal;
- `unit_amount` nullable decimal;
- `quantity` nullable unsigned integer;
- `status` string (`active` or `inactive` for this slice);
- `idempotency_key` string;
- timestamps.

Use a unique composite constraint on `(company_id, idempotency_key)` so retries return the previously-created link instead of producing duplicates.

Do not import QRPay's `payment_links` migration or foreign-key the record to QRPay donor tables.

### Token handling

Generate at least 32 random bytes and expose the raw public token only when a link is created or intentionally resolved into a public URL. Store only a SHA-256/HMAC-style irreversible hash in the database, plus a small non-sensitive hint if useful for support.

Lookup hashes the presented token and performs a constant-shape tenant/status lookup. Logs must never include the raw token.

### URLs

The API response may expose a host-generated share URL based on a Titan route, not QRPay's `setRoute()` helper.

The initial public/share route is read-only. Payment execution and gateway callbacks are outside this first slice and must not be simulated by mutating link state.

## API contract

Base prefix:

```text
/api/titan/pay/v1
```

Authenticated company routes:

```text
POST /payment-links
GET  /payment-links
GET  /payment-links/{uuid}
POST /payment-links/{uuid}/deactivate
```

Route names use the `titan-pay.` namespace.

Responses use Titan-owned JSON resources rather than QRPay's `Helpers::success()` envelope. Stable fields include:

```json
{
  "data": {
    "id": "uuid",
    "kind": "fixed_amount",
    "title": "Invoice 1042",
    "currency": {"code": "AUD", "name": "Australian Dollar", "symbol": "$"},
    "amount": {"unit": "120.00", "quantity": 1, "min": null, "max": null},
    "status": "active",
    "share_url": "https://host/...",
    "created_at": "ISO-8601"
  }
}
```

Validation uses Laravel request validation but maps errors to the host's normal API exception/validation handling rather than donor response helpers.

## QR workflow boundary

QRPay QR screens/behaviour remain donor references. Issue #257 may add a `PaymentLinkQrPayload` formatter that returns a Titan share URL or normalized payment-link payload for Flutter to encode natively.

The backend must not treat QR generation as payment settlement. A QR code is presentation of a signed/public identifier, not financial truth.

## Gateway and webhook boundary

Gateway packages and QRPay callback controllers are not imported in the first slice.

When gateway adapters are added later in Issue #257, each provider must sit behind a Titan interface and produce normalized payment events. Webhook endpoints must require provider signature verification, replay protection and idempotent event handling before they can dispatch commands.

Provider callbacks may record a received/verified event but must not directly update entitlements, commerce orders, wallet balances or WorkCore jobs.

Immutable financial posting belongs to Titan Pay ledger contracts introduced in Issue #259.

## Idempotency and concurrency

Create-payment-link calls require an `Idempotency-Key` header.

Rules:

1. key is scoped to active company;
2. identical retry returns the original resource;
3. same key with materially different payload returns conflict/422 rather than silently changing the original resource;
4. uniqueness is enforced in the database, not only application memory;
5. command execution runs in a transaction around idempotency check and insert.

## Error handling

- missing/invalid authentication: host auth response;
- no active company/company membership: `titan.company` response;
- missing permission: 403;
- missing idempotency key: 422;
- invalid amount/currency/kind: 422;
- duplicate idempotency key with different payload: 409 or 422, consistently documented and tested;
- cross-company UUID/token: 404, not 403, to avoid resource enumeration;
- inactive public link: 404/410 according to route contract;
- unexpected persistence error: generic 500 response with structured server-side logging and no payment token or sensitive request leakage.

## Money rules

No PHP float is authoritative for financial amounts.

Requests accept decimal strings. Validation enforces positive values and the relationship `max_amount > min_amount` when both exist. Fixed links require `unit_amount > 0`; open links reject `unit_amount`.

This slice stores normalized decimal values and returns decimal strings. Issue #259 may replace persistence with Money value objects/minor units as part of the ledger without changing the external API contract.

## Compatibility matrix

The implementation documentation must record at least:

| Concern | MagicAI | QRPay donor | Decision |
|---|---|---|---|
| PHP | ^8.2 | ^8.0.2 | Host ^8.2 only |
| Laravel | ^10.0 | ^9.19 | Host Laravel 10 only |
| Passport | ^12.3 | ^10.4 | Do not import donor auth |
| Sanctum | ^3.2 | ^3.0 | Use host Sanctum |
| Intervention Image | ^3.5 | ^2.7 | Do not import image flow in first slice |
| User/tenant | MagicAI user + active company | QRPay user/merchant | Host company is tenant authority |
| Payment link table | none reserved by Titan Pay | `payment_links` | Use `titan_pay_payment_links` |
| Payment mutation | future Titan ledger | direct donor model/controller updates | Commands/events only |

No QRPay Composer requirement is added to the root `composer.json` merely to preserve donor behaviour.

## Service-provider and migration safety

`TitanPayServiceProvider` follows existing MagicAI extension conventions:

- merge `titan_pay` config;
- register command/query services and repositories;
- load only Titan Pay migrations;
- load namespaced Titan Pay API routes;
- expose no route when disabled if the extension enable flag is false;
- uninstall hook must not drop data automatically unless the host extension lifecycle explicitly requires destructive uninstall semantics.

The migration creates only `titan_pay_*` tables/indexes and never alters QRPay donor tables or MagicAI core tables in the first slice.

## Testing strategy

### Architecture/compatibility tests

Prove:

- root Composer constraints remain unchanged;
- donor QRPay PHP files are not autoloaded by Titan Pay;
- extension can register when enabled and disabled;
- route names/prefixes are namespaced;
- migration table names are collision-safe.

### Tenant-security tests

Prove:

- company A cannot read/deactivate company B links;
- UUID probing returns 404;
- idempotency keys are isolated by company;
- request `company_id` input cannot override active company;
- unauthenticated requests fail before command execution.

### Command tests

Prove:

- fixed and open-amount links validate correctly;
- public token is random and only its hash is persisted;
- identical idempotent retries return one record;
- conflicting retries fail;
- deactivation is idempotent;
- no ledger/wallet/entitlement mutation occurs.

### API tests

Prove:

- create/list/get/deactivate JSON contracts;
- permission enforcement;
- decimal amounts remain strings in public resources;
- share URL uses Titan route generation;
- disabled extension does not expose Titan Pay endpoints.

### Regression gate

Run the focused Titan Pay suite plus the repository's relevant Laravel/Pest host tests. Existing unrelated failures must be reported separately rather than hidden or treated as Titan Pay success.

## Documentation deliverables

Create:

- `docs/titan-hub/TITAN_PAY_QRPAY_COMPATIBILITY.md` with the dependency/model/route/migration compatibility matrix;
- `docs/titan-hub/TITAN_PAY_API_CONTRACT.md` with the first payment-link API;
- donor mapping notes identifying QRPay files used as behavioural references and what was intentionally not ported.

## Out of scope for the first implementation slice

- importing QRPay authentication or Passport clients;
- copying QRPay gateway Composer dependencies into the host;
- wallet balance mutation;
- immutable ledger posting (Issue #259);
- merchant/agent account transplantation;
- agent cash-in/cash-out settlement;
- KYC authority replacement;
- commerce entitlement updates;
- QRPay UI/Blade views;
- production gateway callbacks before signature/replay/idempotency contracts exist;
- changing Flutter primary navigation.

## Acceptance mapping for Issue #257

### MagicAI boots with integration enabled and disabled

Proved by service-provider/config tests and host boot/route tests.

### No core-table collisions occur

Proved by namespaced `titan_pay_*` migrations and architecture assertions that donor migrations are not loaded.

### Tenant-safe API contracts are documented

Proved by `TITAN_PAY_API_CONTRACT.md`, active-company middleware, company-scoped repositories and cross-company tests.

### Initial QR/payment-link flow works through Titan commands rather than QRPay direct mutation

Proved by `CreatePaymentLinkCommand` plus Titan-owned API/resource/QR payload generation. QRPay controllers/models remain outside runtime execution and the resulting link does not directly mutate wallet, ledger, order or entitlement state.

## Spec self-review

- No placeholders or deferred requirements remain inside the first slice.
- The first slice is independently testable and does not require Issue #259 to succeed.
- Financial authority is consistently reserved for the future Titan Pay ledger.
- Tenant authority is consistently the active MagicAI/WorkCore company, never donor user/merchant IDs.
- The API, persistence and permission names are explicit enough to produce a TDD implementation plan without inventing architecture during coding.
