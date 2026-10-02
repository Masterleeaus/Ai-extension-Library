# Titan Commerce Operations

This guide covers installation configuration for the commerce extension at `app/extensions/ChatbotEcommerce/`. The package keeps its existing extension key and namespace for upgrade compatibility; **Titan Commerce** is its product name.

## Store connections

### Shopify and WooCommerce

Connect seller storefront credentials through the authenticated credential management API. Credentials are encrypted at rest and should not be placed in marketplace connection configuration:

- Shopify credentials use the Storefront API access token and the store domain.
- WooCommerce credentials use the consumer key, consumer secret and store domain.
- The default Shopify Storefront API version is `2026-07`. Set `CHATBOT_ECOMMERCE_SHOPIFY_STOREFRONT_API_VERSION` to select another supported version.

The storefront connection APIs are under:

`/api/v3/chatbot/ecommerce/{chatbot}/credentials/{provider}`

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

## Seller AI roles

All three roles operate on behalf of the seller:

1. **Customer Shopping Assistant** answers from the seller’s catalogue, guides purchases, finds booking availability and prepares customer-approved reservations.
2. **Seller Commerce Steward** manages the seller’s connected listings, orders, inventory and sales work through seller-authorized marketplace tools.
3. **Customer Communications Agent** follows transaction-grounded support workflows, drafts responses, prepares governed customer actions and escalates cases for human review.

Customer-facing booking reservations require a signed session authority. Reservation preparation does not consume capacity; capacity is committed only after customer approval. Sellers manage availability and reservations through authenticated booking APIs.

## Human and action policies

Customer support automation is disabled by default. The default confidence threshold is 0.90, identity verification is required for private order facts, and human handoff is enabled. Configure:

- `CHATBOT_ECOMMERCE_AUTO_REPLY_CONFIDENCE`
- `CHATBOT_ECOMMERCE_HUMAN_HANDOFF`
- `CHATBOT_ECOMMERCE_SUPPORT_ACTION_TTL`

Per-store policies can further limit automatic replies and actions. Marketplace writes, bulk changes and support actions retain approval, idempotency and action-history controls.

## Runtime configuration

The extension merges `config/chatbot-ecommerce.php` through its service provider. Use environment configuration in each installation. Do not commit secrets to this repository. Ensure Laravel’s application encryption key is stable and backed up because encrypted credentials and configuration depend on it.

The extension’s scheduler registers reservation, checkout, payment, rental, marketplace import and inventory reconciliation jobs. Run the host application’s scheduler and queue workers for lifecycle processing. Provider calls remain unavailable until the corresponding credentials, gateway and seller connection are configured.
