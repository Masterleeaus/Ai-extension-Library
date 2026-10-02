# Smart Sellers Operations

This guide covers installation configuration for the commerce extension at `app/extensions/ChatbotEcommerce/`. The package keeps its existing extension key and namespace for upgrade compatibility; **Smart Sellers** is its product name.

## Store connections

### Shopify and WooCommerce

Connect seller storefront credentials through the authenticated credential management API. Credentials are encrypted at rest and should not be placed in marketplace connection configuration:

- Shopify credentials use the Storefront API access token and the store domain.
- WooCommerce credentials use the consumer key, consumer secret and store domain.
- The default Shopify Storefront API version is `2026-07`. Set `CHATBOT_ECOMMERCE_SHOPIFY_STOREFRONT_API_VERSION` to select another supported version.

The storefront connection APIs are under:

`/api/v3/chatbot/ecommerce/{chatbot}/credentials/{provider}` (authenticated seller)

The seller must authenticate as the owner of the selected storefront. The extension never returns credential values in credential summaries.

### Amazon, eBay and Etsy

The marketplace adapters call a configured gateway. Each seller connection stores a credential reference that the gateway resolves to that seller’s provider credentials. Raw marketplace secrets are rejected in connection configuration.

Configure a shared gateway:

- `CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_URL`
- `CHATBOT_ECOMMERCE_MARKETPLACE_GATEWAY_SECRET`

Or provide provider-specific values:

- `CHATBOT_ECOMMERCE_AMAZON_GATEWAY_URL` and `CHATBOT_ECOMMERCE_AMAZON_GATEWAY_SECRET`
- `CHATBOT_ECOMMERCE_EBAY_GATEWAY_URL` and `CHATBOT_ECOMMERCE_EBAY_GATEWAY_SECRET`
- `CHATBOT_ECOMMERCE_ETSY_GATEWAY_URL` and `CHATBOT_ECOMMERCE_ETSY_GATEWAY_SECRET`
- `CHATBOT_ECOMMERCE_GENERIC_GATEWAY_URL` and `CHATBOT_ECOMMERCE_GENERIC_GATEWAY_SECRET`

The read transport posts to `/v1/marketplace/read`; the write transport posts to `/v1/marketplace/write`. Requests include provider, operation, account context, credential reference and payload. Requests are signed with HMAC-SHA256. Writes include an idempotency key and expected source hash and require the gateway response to return authoritative listing state.

Set `CHATBOT_ECOMMERCE_MARKETPLACE_DISPATCH_MODE=async` to queue marketplace order imports. The default is async. Configure workers for these queues:

- `chatbot-ecommerce-marketplace-read`
- `chatbot-ecommerce-marketplace-write`
- `chatbot-ecommerce-inventory-reconciliation`
- `chatbot-ecommerce-payment-webhooks`

Queue names can be overridden with `CHATBOT_ECOMMERCE_QUEUE_MARKETPLACE_READ`, `CHATBOT_ECOMMERCE_QUEUE_MARKETPLACE_WRITE`, `CHATBOT_ECOMMERCE_QUEUE_INVENTORY` and `CHATBOT_ECOMMERCE_QUEUE_PAYMENT_WEBHOOKS`.

### Gateway reliability

Marketplace gateway timeouts and retry settings can be configured with:

- `CHATBOT_ECOMMERCE_MARKETPLACE_CONNECT_TIMEOUT`
- `CHATBOT_ECOMMERCE_MARKETPLACE_TIMEOUT`
- `CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_TIMEOUT`
- `CHATBOT_ECOMMERCE_MARKETPLACE_READ_RETRIES`
- `CHATBOT_ECOMMERCE_MARKETPLACE_WRITE_RETRIES`
- `CHATBOT_ECOMMERCE_MARKETPLACE_RETRY_DELAY_MS`

Configure the signing secret outside source control. Marketplace credentials belong in the connected seller credential vault; use only a non-secret `credential_reference` in the connection record.

## Seller API map

Seller management endpoints use the `/api/v3/chatbot/ecommerce/{chatbot}` prefix and require an authenticated seller who owns the selected chatbot/store. The API currently exposes these primary workflows:

| Workflow | Endpoint | Purpose |
| --- | --- | --- |
| Store credentials | `PUT /{chatbot}/credentials/{provider}` | Save or rotate Shopify/WooCommerce credentials; use `POST .../{provider}/test` to verify and `DELETE .../{provider}` to revoke. |
| Marketplace channels | `GET/POST /{chatbot}/marketplaces`, `PUT /{chatbot}/marketplaces/{connection}` | Connect provider gateway references and manage channel state. |
| Marketplace orders | `POST /{chatbot}/marketplaces/{connection}/orders/import`, `GET /{chatbot}/marketplaces/orders` | Import external orders and view imported order snapshots. |
| Listing operations | `POST /{chatbot}/marketplace-write-proposals` and `/marketplace-bulk-batches` | Prepare, approve, execute and roll back governed listing changes. |
| Unified order desk | `GET /{chatbot}/unified-orders`, `/order-exceptions` | Review cross-channel orders, settlement reconciliation and exceptions. |
| Booking availability | `GET/POST /{chatbot}/bookings/slots` | Read and create capacity slots linked to the seller’s catalogue. |
| Booking operations | `GET /{chatbot}/bookings`, `POST /{chatbot}/bookings/{booking}/cancel` | Review and cancel reservations as the seller. |
| Customer support | `GET /{chatbot}/support/threads`, `GET/PUT /{chatbot}/support/policies` | Review threads, tune policies and resolve escalations. |

Customer-facing commerce APIs use `/api/v3/chatbot/ecommerce/{chatbot}/session/{sessionId}/...`. The host application must issue signed session authority at `POST /api/v3/chatbot/ecommerce/{chatbot}/commerce/session-authority`; session routes reject requests without valid authority. Those APIs cover catalogue, cart, checkout, orders, shipping, inventory, payments, booking availability/reservation/cancellation and customer support. Authenticated seller endpoints and customer session endpoints are separate trust boundaries.

## Seller AI roles

All three roles operate on behalf of the seller:

1. **Customer Shopping Assistant** answers from the seller’s catalogue, guides purchases, finds booking availability and prepares customer-approved reservations.
2. **Seller Commerce Steward** manages the seller’s connected listings, orders, inventory and sales work through seller-authorized marketplace tools.
3. **Customer Communications Agent** uses verified native and marketplace order context, drafts responses and feedback requests, prepares governed support actions, and escalates cases for human review. Reply and feedback drafts are stored in the support thread with order provenance and remain unsent. Automatic sending is unavailable until a channel transport integration exists, even when a store policy is configured to permit auto replies.

Customer-facing booking reservations require a signed session authority. Reservation preparation does not consume capacity; capacity is committed only after customer approval. Sellers manage availability and reservations through authenticated booking APIs.

## Human and action policies

Customer support automation is disabled by default. The default confidence threshold is 0.90, identity verification is required for private order facts, and human handoff is enabled. Configure:

- `CHATBOT_ECOMMERCE_AUTO_REPLY_CONFIDENCE`
- `CHATBOT_ECOMMERCE_HUMAN_HANDOFF`
- `CHATBOT_ECOMMERCE_SUPPORT_ACTION_TTL`

Per-store policies can further limit automatic replies and actions. Marketplace writes, bulk changes and support actions retain approval, idempotency and action-history controls.


## Commerce setup by capability

- **Catalogue and pricing:** Create products and variants under the selected seller storefront. Tax inclusion and default tax rates are deployment defaults; seller tax zones, rates, exemptions and pricing rules remain seller-managed records.
- **Checkout and delivery:** Checkout sessions use the configured lifetime and approval windows. The default delivery option list is empty; configure the seller’s shipping zones, methods and provider before offering delivery.
- **Payments:** The internal payment adapter exposes bank transfer, PayID, cash and externally fulfilled methods by default. Add seller-specific payment instructions through the payment configuration. Card, direct debit and BNPL intents require a hosted payment URL from a connected licensed provider.
- **Payment webhooks:** Configure `CHATBOT_ECOMMERCE_PAYMENT_WEBHOOK_SECRET` and keep the webhook worker enabled. Webhook signatures are time-bounded by `CHATBOT_ECOMMERCE_PAYMENT_WEBHOOK_TOLERANCE`.
- **Returns and support:** The default return window is 30 days. Automatic replies are off by default, monetary support limits default to zero, and sensitive cases route to human handoff.
- **Hire and rental:** The default payment methods are bank transfer, PayID, cash and external payment. Set seller payment instructions and hire agreements, rates and billing cycles before collecting rental payments.
- **Marketplace writes:** Set `CHATBOT_ECOMMERCE_MARKETPLACE_APPROVAL_SECRET` before preparing governed writes. Writes default to approval-only; bulk writing and inventory oversell remain disabled. Approval and action limits can be tuned through deployment configuration and seller policy.
- **Signed customer sessions:** Set `CHATBOT_ECOMMERCE_SESSION_AUTHORITY_SECRET` to enable signed shopper sessions. Keep it separate from marketplace, payment and BNPL secrets.
- **BNPL:** Licensed-provider enforcement is enabled by default. Configure an approved provider profile and `CHATBOT_ECOMMERCE_BNPL_STATE_SECRET` before enabling buy-now-pay-later offers.

The most important deployment-only secrets are `CHATBOT_ECOMMERCE_MARKETPLACE_APPROVAL_SECRET`, `CHATBOT_ECOMMERCE_SESSION_AUTHORITY_SECRET`, `CHATBOT_ECOMMERCE_PAYMENT_WEBHOOK_SECRET`, `CHATBOT_ECOMMERCE_BNPL_STATE_SECRET`, and the marketplace gateway signing secret. Use separate random values for each purpose.

## Runtime configuration

The extension merges `config/chatbot-ecommerce.php` through its service provider. Use environment configuration in each installation. Do not commit secrets to this repository. Ensure Laravel’s application encryption key is stable and backed up because encrypted credentials and configuration depend on it.

The extension’s scheduler registers reservation, checkout, payment, rental, marketplace import and inventory reconciliation jobs. Run the host application’s scheduler and queue workers for lifecycle processing. Provider calls remain unavailable until the corresponding credentials, gateway and seller connection are configured.
