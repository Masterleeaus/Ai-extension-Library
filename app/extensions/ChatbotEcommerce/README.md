# Titan Commerce

**A seller-owned store and AI sales team for products, services, hire and bookings.**

Titan Commerce gives a seller one product line, one commerce system and three coordinated AI roles. The seller can publish its catalogue through a storefront and supported marketplace channels, while customers get a knowledgeable shopping assistant and responsive post-sale support.

## The three seller-side AI roles

- **Customer Shopping Assistant** — represents the seller on its website and supported customer-facing channels. It answers from the seller’s catalogue, helps customers compare options, checks availability and guides them through the store’s cart and checkout.
- **Seller Commerce Steward** — manages listings and sales operations across connected marketplaces. It prepares product content, monitors inventory and orders, identifies listing or stock conflicts, and carries out seller-authorized actions.
- **Customer Communications Agent** — follows up with customers, answers order and delivery questions using recorded transaction facts, requests feedback, prepares support actions and hands sensitive cases to a person.

Each role serves the seller and uses the same catalogue, availability, inventory, order records and seller policies. Role-specific tools and approval rules govern what each agent can do.

## One catalogue for diverse offerings

The commerce model can cover:

- Physical and digital products with variants, bundles and channel listings.
- Services with options, availability, appointments and deposits.
- Hire and rentals with periods, agreements, extensions, returns and charges.
- Bookings and reservations with capacity, time slots, reminders and cancellation rules.
- Mixed product lines combining goods with installation, events, accommodation or other bookable services.

## Core capabilities

- Product catalogue, variants, categories, pricing and brand voice.
- Storefront shopping, product discovery, conversational assistance, cart and checkout.
- Marketplace connections, listing proposals, approvals, bulk operations and rollback.
- Inventory by location, reservations, channel allocation and conflict reconciliation.
- Unified orders, fulfilment, returns, refunds and settlement reconciliation.
- Shipping, tax, payment and BNPL provider contracts.
- Customer communications across native and marketplace orders, feedback-request drafts, governed support actions and human handoff.
- Rental and hire accounts, agreements, charges, payments and receipts.
- Seller-managed appointment and event slots exposed through the same booking assistant for native, Shopify and WooCommerce storefronts, with session-scoped availability, customer approval before capacity is reserved, idempotency and cancellation.

## Governed commerce architecture

The extension separates seller operations from customer shopping and support. Marketplace and payment actions can be prepared, reviewed, executed under authority, journaled and rolled back. Tenant context, scoped credentials, idempotency, asynchronous jobs and audit records support safe operation across connected channels.

The Laravel extension is located at `app/extensions/ChatbotEcommerce/`. Its current extension key and package paths remain compatible with existing installations; **Titan Commerce** is the customer-facing product name.

## Implementation status

The source contains foundations for catalogue and variants, carts and checkout sessions, inventory reservations, orders and returns, marketplace listings and write proposals, customer communication threads and support actions, a unified order workbench, and capacity-managed booking slots. Sellers can associate slots with catalogue products; the shopping and customer communications roles can discover availability across supported store sources, while customer approval is required before capacity is reserved through a signed storefront session.

Some workflows are still being built. Booking currently provides dated capacity slots and reservations; recurring schedules, resource assignment, deposits and booking-specific payment flows remain to be completed. Marketplace coverage, live payment processing, storefront embedding, rental lifecycle depth and end-to-end role handoffs also need further implementation before being presented as production-complete.

## Scope

Titan Commerce owns the seller’s catalogue experience and commerce workflows across supported channels. Shared identity, tenant, security, integration and operational services remain dependencies where the extension uses them. General-purpose AI extensions and unrelated Titan product suites are outside this product’s feature scope.

## Development and release

Follow the repository’s Laravel, PHP, Composer and CI configuration. Validate extension boot and routes in the complete host application, then verify tenant isolation, permissions, migrations, checkout, payments, marketplace reads and writes, inventory concurrency, communication actions, queues, schedules, upgrade, rollback and uninstall flows.

Check the repository and provider-specific license terms before deployment or redistribution.
