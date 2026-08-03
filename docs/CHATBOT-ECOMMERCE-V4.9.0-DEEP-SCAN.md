# Chatbot Ecommerce v4.9.0 — Deep Scan

## Replacement decision

`extensions/ChatbotEcommerce` originally contained version 1.0.0 with 17 files and 117,161 bytes. It has been replaced by the standalone, cumulative v4.9.0 package:

- 403 packaged files
- 1,676,325 uncompressed bytes
- ZIP SHA-256: `c9d6aaea58fba4dd5d2d115740281fff43fdd3fb5dd5f82c3db38e55aff1b42a`
- 393 PHP files passed syntax validation before publication
- 38 standalone regression scripts passed
- 1,024 assertions and contract checks passed
- Blueprint v3 manifest and JSON schema passed
- OpenAPI 3.1 document parsed successfully

The folder name remains `ChatbotEcommerce`, preserving the MagicAI extension identity and loader path.

## Product roles

The extension implements three governed roles without duplicating the channel or inbox extensions:

1. **Customer Shopping Assistant** — product discovery, comparison, cart, checkout preparation, payment/BNPL, order tracking, returns, and marketplace-assisted shopping.
2. **Seller Commerce Steward** — catalogue, pricing, promotions, inventory, orders, fulfilment, marketplace reads/writes, listing intelligence, reconciliation, settlements, and exception management.
3. **Customer Communications Agent** — grounded pre-sale, checkout, delivery, return, refund, warranty, and complaint support using real commerce state.

Role-scoped tool registries prevent customer sessions from acquiring seller-management authority.

## Native commerce foundation

The package includes:

- Native products, variants, categories, carts, cart recovery, and cart merging
- Multi-location inventory, reservations, adjustments, commitments, and availability
- Pricing rules, promotions, coupons, stacking controls, and usage limits
- Checkout sessions, shipping quotes, fulfilment, tax resolution, and exemptions
- Provider-neutral payment intents, authorization, capture, cancellation, refund, reconciliation, signed webhooks, and replay prevention
- BNPL offers and hosted approval flows
- Rental/hire accounts, agreements, rates, charges, payments, allocation, receipts, ledger entries, adjustments, and reversals
- Native order materialisation, immutable snapshots, events, returns, refunds, and rollback journals

## Marketplace architecture

Read integrations are normalized behind provider contracts for Amazon, eBay, Etsy, and generic providers. Supported reads include listing search, listing details, inventory observation, and external-order import.

Seller writes use a separate signed gateway and require:

- Provider capability support
- Fresh marketplace state and source hash
- Conflict analysis
- Seller approval
- Idempotency keys
- Before/after journal entries
- Version-safe rollback
- Rate-limit and circuit-breaker checks

Bulk operations add dry runs, bounded selection, impact previews, one batch approval, per-item failure isolation, and partial rollback.

## Listing intelligence and compliance

Canonical product facts and seller brand-voice profiles drive marketplace-specific copy for Amazon, eBay, Etsy, and generic providers. The compliance scanner detects unsupported claims, prohibited language, missing evidence, marketplace policy risks, required attributes, and brand-voice violations.

Blocking findings cannot enter the publication flow. Compliant rewrites become ordinary marketplace write proposals and retain source-version checks and rollback protection.

## Inventory conflict resolution

The reconciliation engine calculates permitted marketplace stock from physical inventory minus reservations, commitments, damaged stock, safety stock, and channel buffers.

Equal-share allocation is the safe default across mapped channels. Sellers may explicitly configure mirror, percentage, fixed-cap, or percentage-cap policies. Persistent conflicts track oversell exposure, stale observations, missing or unverified mappings, delayed synchronization, acknowledgement, ignore periods, and governed correction proposals.

## Security and tenant boundaries

Version 4.7.0 and later include:

- Encrypted Shopify and WooCommerce credential storage
- External credential-vault references
- Secret redaction and legacy plaintext migration
- Signed, expiring storefront-session authority tokens
- Capability-scoped public APIs
- Strict chatbot ownership and tenant isolation
- Runtime checks preventing cross-chatbot products, variants, locations, coupons, prices, taxes, shipping methods, payments, fulfilments, rental accounts, and marketplace records
- Cart-merge recovery-token enforcement
- Fail-closed behavior when session-authority secrets are missing

## Reliability and lifecycle

Version 4.8.0 and later include:

- Registered Laravel schedules with overlap and single-server controls
- Dedicated queues and explicit tenant-context restoration
- Asynchronous payment-webhook acknowledgement and processing
- Retry, exponential backoff, dead-letter handling, and requeue support
- Tenant/provider/operation-scoped circuit breakers
- Enabled, disabled, uninstalling, and uninstalled lifecycle states
- Data-preserving disable, uninstall, and reinstall behavior
- Health checks for lifecycle, webhooks, circuits, and commerce subsystems

## Unified order and settlement workbench

Version 4.9.0 projects native, Shopify, WooCommerce, Amazon, eBay, Etsy, and generic marketplace orders into one seller workbench while preserving each external provider as authoritative.

It adds:

- Immutable unified-order snapshots
- Settlement entries for sales, fees, taxes withheld, shipping costs, refunds, chargebacks, adjustments, and payouts
- Expected-versus-reported payout reconciliation
- Provider-account isolation for settlement identifiers
- Address, payment, fulfilment, stock, return, and settlement exceptions
- Governed acknowledge, assign, resolve, customer-contact, support-thread-link, and refund-proposal actions
- Idempotency keys bound to exact order, exception, action, payload, and support-thread context

Refund actions prepare proposals only; they cannot self-execute.

## Remaining release requirement

The package has strong standalone validation, but full production release still requires installation into the complete MagicAI Laravel host to run:

- Database migration and rollback tests
- Middleware, policy, and HTTP integration tests
- Queue-worker and scheduler-lock tests
- Webhook replay and concurrency tests
- Upgrade tests from earlier ecommerce versions
- Disable, uninstall, and reinstall tests
- Live Shopify, WooCommerce, marketplace-gateway, and payment-provider tests
- Load, fault-injection, and recovery tests
