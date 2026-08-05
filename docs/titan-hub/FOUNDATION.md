# Titan Hub Foundation

## Product boundary

Titan Hub is the customer-facing Flutter application for Titan Zero. It combines discovery, catalogue, commerce, bookings, hire and rental, quotes, invoices, messaging, feedback, rebooking, support, and Titan Pay.

Titan Go remains the staff and operator-facing mobile experience. Shared Flutter packages may serve both apps, but customer and staff permissions, navigation, and workflows remain separate.

## Donor and host systems

### QRPay Flutter

Primary donor for authentication journeys, KYC, biometrics, QR scanning, wallet and payment presentation, payment links, money requests, transaction history, receipts, notifications, hosted provider checkout, localisation, and theming.

### MobileKit

Visual and interaction reference only. Bootstrap HTML, CSS, and JavaScript must be translated into native Flutter components and Titan design tokens. Do not embed a WebView copy as the production interface.

### QRPay Laravel

Primary donor for merchant, customer, and agent workflows; gateway integrations; QR payment concepts; payment links; money requests; transaction limits; KYC administration; remittance and provider knowledge; and existing Flutter API behaviour.

### MagicAI

Host Laravel application and authority for tenant identity, extension lifecycle, configuration, plans, permissions, authentication, and core AI capabilities.

### E-commerce extension

Laravel authority for catalogue, product and service definitions, variants, inventory, carts, order construction, booking and rental source data, fulfilment rules, and commerce-specific policies. It remains a backend extension and is not converted into Flutter code.

### Chatbot extension

The existing Laravel Chatbot extension is the base conversational system for Titan Hub. It owns conversations, messages, attachments, knowledge sources, model/provider integration, human handoff, tool routing, and agent orchestration. Commerce-aware tools must call the same Titan application services used by visual Flutter screens.

### WorkCore

Operational authority and integration target for customers, contacts, products and services, estimates and quotes, jobs, projects, tasks, bookings, inventory, expenses, operational invoices, accounting references, and receivables workflows.

### Titan Pay

Financial authority for payment intents, attempts, invoices as financial obligations, wallet and ledger events, receipts, refunds, credits, bonds, deposits, milestones, splits, payment plans, provider reconciliation, and financial audit history.

## Target architecture

```text
Titan Hub Flutter
  -> /api/titan-hub/v1
     -> MagicAI identity and tenancy
     -> Titan Hub application services
        -> E-commerce adapter
        -> Booking / hire / rental adapters
        -> Chatbot adapter
        -> WorkCore adapter
        -> Titan Pay adapter

Chatbot and agents
  -> the same Titan Hub application services
  -> the same permissions, validation, pricing, availability, and approvals
```

Flutter never calls extension-specific controllers, tables, or routes directly. The Titan Hub API facade is the only supported mobile contract.

## Dual-overlay composition model

Titan Hub must not be forked into separate applications for every industry or transaction model. The resolved customer experience is composed from four layers:

```text
Titan Hub Core
+ one primary Vertical Profile
+ one or more Commerce Modes
+ business-specific configuration
= resolved customer application
```

- **Vertical Profile** answers: what kind of business is this?
- **Commerce Mode** answers: how does this business sell, schedule, reserve, hire, or deliver value?

A field-service business may use `service`, `booking`, `quote_first`, and `sales` together. A hotel may use `accommodation`, `reservation`, `booking`, `service`, `sales`, and `hire` together.

## Titan Hub Core

The core supplies industry-neutral capabilities:

- authentication, identity, KYC, account security, and consent;
- customer and business switching;
- home, explore, search, activity, messages, notifications, and support;
- QR scanning and signed deep links;
- quotes, invoices, receipts, wallet, payments, credits, and disputes;
- files, documents, feedback, preferences, and communication permissions;
- feature, navigation, route, and design-system registries;
- chatbot shell, human handoff, tool approval, and conversation history.

The core must not contain field-service, hotel, salon, automotive, rental, or other vertical assumptions.

## Vertical Profiles

A Vertical Profile contributes terminology, fields, specialist validation, templates, evidence requirements, workflow metadata, and chatbot domain tools. It does not directly create orders, bookings, payments, or wallet entries.

Initial profiles:

- `field_service`
- `hotel_bnb`
- `rooming_house`
- `real_estate_property`
- `salon_personal_care`
- `fitness_membership`
- `automotive`
- `facilities`
- `ecommerce_retail`
- `hire_rental`
- `booking_capacity`

## Commerce Modes

Initial reusable transaction modes:

- `service`
- `booking`
- `quote_first`
- `sales`
- `hire`
- `rental`
- `reservation`
- `membership`
- `subscription`
- `marketplace`
- `class`
- `accommodation`
- `order_ahead`

Each mode contributes capabilities and presentation metadata against shared Titan contracts. It does not introduce a separate customer, invoice, payment, or notification authority.

## Resolved application manifest

Laravel resolves overlays, tenant policy, permissions, branding, connected extensions, and provider capabilities into one signed manifest.

```text
GET /api/titan-hub/v1/app-manifest
```

Representative response:

```json
{
  "data": {
    "schema_version": 1,
    "business": {
      "id": "business_42",
      "name": "Titan Plumbing"
    },
    "vertical_profile": "field_service",
    "commerce_modes": ["service", "booking", "quote_first", "sales"],
    "features": {
      "chatbot": true,
      "wallet": true,
      "bnpl": true,
      "milestones": true,
      "bonds": false,
      "rebooking": true
    },
    "navigation": [],
    "routes": [],
    "home_widgets": [],
    "catalogue_types": [],
    "activity_types": [],
    "chatbot_tools": [],
    "payment_methods": [],
    "terminology": {},
    "permissions": {},
    "branding": {}
  },
  "meta": {
    "request_id": "req_...",
    "etag": "manifest-version",
    "generated_at": "ISO-8601",
    "expires_at": "ISO-8601"
  }
}
```

Flutter renders the backend-resolved manifest. It must not infer verticals, commerce modes, permissions, prices, availability, or payment eligibility locally.

## API facade contract

### Namespace and versioning

All mobile-facing endpoints use:

```text
/api/titan-hub/v1/*
```

Extension routes remain internal. Breaking contract changes require a new major API namespace. Additive fields are permitted within a version when clients ignore unknown fields.

### Core endpoint groups

```text
/api/titan-hub/v1/app-manifest
/api/titan-hub/v1/session
/api/titan-hub/v1/businesses
/api/titan-hub/v1/catalogue
/api/titan-hub/v1/cart
/api/titan-hub/v1/checkout
/api/titan-hub/v1/quotes
/api/titan-hub/v1/bookings
/api/titan-hub/v1/rentals
/api/titan-hub/v1/orders
/api/titan-hub/v1/activity
/api/titan-hub/v1/invoices
/api/titan-hub/v1/payments
/api/titan-hub/v1/wallet
/api/titan-hub/v1/conversations
/api/titan-hub/v1/feedback
/api/titan-hub/v1/support
```

These are stable facade resources, not promises about underlying extension tables or controllers.

### Success envelope

```json
{
  "data": {},
  "meta": {
    "request_id": "req_...",
    "correlation_id": "corr_...",
    "version": "v1"
  }
}
```

### Error envelope

```json
{
  "error": {
    "code": "BOOKING_SLOT_UNAVAILABLE",
    "message": "The selected time is no longer available.",
    "field_errors": {},
    "retryable": false
  },
  "meta": {
    "request_id": "req_...",
    "correlation_id": "corr_..."
  }
}
```

Error codes are stable machine-readable identifiers. Flutter may localise approved user-facing messages but must not derive business decisions from message text.

### Pagination

Collection endpoints use cursor pagination:

```json
{
  "data": [],
  "meta": {
    "next_cursor": "...",
    "has_more": true
  }
}
```

Offset pagination may be used internally but must not leak extension-specific pagination assumptions into Flutter.

### Idempotency

Every command that may create financial or operational value accepts:

```text
Idempotency-Key: client-generated-unique-key
```

Required for checkout, booking creation, rental creation, quote acceptance, invoice payment, refunds, wallet transfers, bond actions, milestone actions, and agent tool commands that create or alter records.

The server stores the key against tenant, actor, command type, and canonical request hash. Reusing the key with different data is rejected.

### Correlation and tracing

Clients send or receive:

```text
X-Request-ID
X-Correlation-ID
```

The correlation ID follows a workflow across Flutter, the API facade, extensions, WorkCore, Titan Pay, queues, webhooks, chatbot tools, and notifications.

### Optimistic concurrency

Mutable resources expose a version or ETag. Commands that depend on prior state supply:

```text
If-Match: resource-version
```

Stale writes return `409 CONFLICT` with the current resource summary. Flutter must refresh rather than silently overwrite newer bookings, carts, quotes, invoices, rentals, or profile data.

### Authentication and tenant scope

MagicAI remains identity and tenancy authority. Every request resolves:

- authenticated customer;
- selected tenant and business;
- membership and relationship to that business;
- allowed vertical profile and commerce modes;
- feature entitlement;
- resource-level permission.

Tenant or business identifiers supplied by the client are selectors, never trusted authority. Every backend query is tenant-scoped server-side.

### Offline behaviour

The manifest declares per-route offline behaviour:

- `read_cached`
- `queue_safe_command`
- `online_required`
- `blocked_offline`

Financial commands, availability confirmation, checkout, refunds, bond release, and final booking creation remain online-required unless a specific safe offline protocol is implemented.

## Domain authority matrix

| Domain | Authoritative owner | Titan Hub API role |
|---|---|---|
| Authentication, tenant, plans, permissions | MagicAI | Resolve actor, tenant, business, entitlements |
| App manifest and overlay resolution | Titan Hub application layer | Compose signed mobile experience |
| Catalogue definitions and variants | E-commerce extension or configured commerce source | Normalise into Titan catalogue contracts |
| Inventory and availability | Configured commerce/booking source | Query and validate; never cache as permanent truth |
| Cart and checkout orchestration | Titan Hub application services | Coordinate commerce, pricing, tax, availability, and payment intent |
| Orders and fulfilment | E-commerce extension / WorkCore according to configured ownership | Expose one stable order/activity representation |
| Bookings and reservations | Configured booking authority | Validate slots, capacity, resources, cancellation and rescheduling |
| Hire and rental | Configured rental authority | Validate dates, stock, bonds, condition and return lifecycle |
| Quotes and estimates | WorkCore or configured quote authority | Normalise intake, revisions, acceptance, and downstream commands |
| Jobs, projects, tasks, field operations | WorkCore | Expose customer-safe status and actions |
| Financial invoices, payments, wallet, refunds, bonds | Titan Pay | Execute financial commands and return projections |
| Conversations, messages, handoff | Chatbot extension | Expose tenant-safe conversation APIs |
| Agent orchestration and tools | Chatbot extension + Titan governance | Authorise tools and invoke Titan application commands |
| Notifications and lifecycle communication | Titan notification/lifecycle services | Deliver state-aware messages using authoritative events |

No domain may maintain two independent write authorities. When an existing extension currently writes overlapping data, the adapter must select one authority and make the other a projection or integration consumer.

## Adapter rules

Each backend extension is wrapped by an adapter that translates between stable Titan contracts and extension internals.

An adapter must:

- use existing extension services before controllers or direct table access;
- preserve tenant scoping and permissions;
- translate extension identifiers into opaque Titan resource identifiers or mapped references;
- translate extension exceptions into stable Titan error codes;
- emit correlation IDs and domain events;
- implement idempotency where commands create value;
- avoid exposing extension class names, route names, table names, or payload shapes;
- remain replaceable without a Flutter release.

Controllers remain thin. Business rules stay in application/domain services.

## E-commerce integration rule

The e-commerce extension remains Laravel code. Flutter receives native screens and invokes Titan APIs.

```text
Flutter product or booking screen
  -> Titan Hub API command/query
     -> Titan commerce application service
        -> e-commerce / booking / rental adapter
```

The backend remains authoritative for:

- current price and tax;
- variants and stock;
- booking and rental availability;
- deposits, bonds, cancellation, extension, and return policies;
- checkout eligibility and payment-method availability.

Flutter may display estimates but final confirmation always comes from an authoritative server response.

## Chatbot extension integration rule

The Chatbot extension is the base extension for Titan Echo inside Titan Hub. It owns conversation state and tool orchestration, but it does not own commerce or financial rules.

```text
Flutter visual action ─┐
                       ├─> Titan application service -> authoritative adapter
Chatbot tool action ───┘
```

A chatbot tool must not call e-commerce, WorkCore, or Titan Pay tables directly. It invokes the same application command used by the visual app.

Examples:

- `catalogue.search`
- `availability.check`
- `cart.add_item`
- `quote_request.create`
- `booking_draft.create`
- `booking.confirm`
- `rental_draft.create`
- `invoice.get_status`
- `payment_intent.create`
- `rebooking.options`
- `support_case.create`

Tool definitions declare:

- required tenant capability;
- required actor permission;
- input schema;
- confirmation requirement;
- idempotency requirement;
- approval policy;
- audit event;
- safe retry behaviour.

Agents cannot silently move money, issue refunds, release bonds, cancel paid bookings, override prices, or change obligations. Those commands require explicit configured authority and, where applicable, human approval.

## Composition and conflict rules

1. Titan Hub Core registers immutable base capabilities.
2. The Vertical Profile contributes terminology, fields, specialist validation, templates, and presentation metadata.
3. Commerce Modes contribute transaction capabilities and workflow metadata.
4. Business configuration enables, disables, renames, orders, or restricts contributions.
5. Permissions and provider capabilities remove unavailable actions.
6. The resolver rejects unresolved route, capability, or authority collisions.

Resolve conflicts in this order:

1. security, legal, and permission restrictions;
2. authoritative backend capability;
3. explicit business configuration;
4. Vertical Profile specificity;
5. Commerce Mode priority;
6. Titan Hub Core default.

Conflicting price, inventory, availability, deposit, bond, refund, cancellation, or eligibility data is never resolved in Flutter or chatbot language-model output.

## Example compositions

### Field service

```json
{
  "vertical_profile": "field_service",
  "commerce_modes": ["service", "booking", "quote_first", "sales"]
}
```

### Hotel and BnB

```json
{
  "vertical_profile": "hotel_bnb",
  "commerce_modes": ["accommodation", "reservation", "booking", "service", "sales", "hire"]
}
```

### Salon

```json
{
  "vertical_profile": "salon_personal_care",
  "commerce_modes": ["service", "booking", "sales", "membership"]
}
```

### Fitness

```json
{
  "vertical_profile": "fitness_membership",
  "commerce_modes": ["class", "booking", "membership", "subscription", "sales"]
}
```

### Automotive

```json
{
  "vertical_profile": "automotive",
  "commerce_modes": ["service", "booking", "quote_first", "sales", "hire"]
}
```

### Hire and rental

```json
{
  "vertical_profile": "hire_rental",
  "commerce_modes": ["hire", "rental", "booking", "sales"]
}
```

## Flutter implementation boundary

Flutter owns native presentation, local view state, accessibility, secure device storage, deep-link handling, controlled offline presentation, manifest rendering, and invoking stable Titan commands and queries.

Flutter does not own authoritative pricing, inventory, availability, orders, bookings, rentals, quotes, invoices, wallet state, payments, eligibility, refunds, bonds, discounts, or approvals.

When adding UI, first locate the closest existing QRPay or Titan Hub screen. Duplicate and adapt it so routes, state management, components, theming, validation, and interaction styles remain consistent. Create a new structure only when no suitable donor exists.

## Security gate

Before donor import and production use:

- remove `.env` files, credentials, signing keys, OAuth private keys, and packaged Firebase configurations;
- remove generated builds, caches, logs, and dependency vendor artefacts;
- replace insecure mobile token storage;
- redact sensitive request and response logging;
- audit direct wallet balance mutation and concurrency;
- add signed webhooks, replay protection, idempotency, and tenant isolation tests.

## Delivery sequence

1. Import and sanitise licensed donor sources.
2. Establish Flutter feature-module architecture and Titan design system.
3. Implement the Titan Hub API facade and dual-overlay resolver.
4. Convert the QRPay mobile shell into Titan Hub navigation and identity.
5. Integrate catalogue, services, sales, bookings, rentals, and hire through adapters and Commerce Modes.
6. Integrate Titan Pay invoicing, wallets, QR, rails, BNPL, milestones, bonds, and split payments.
7. Integrate WorkCore operational and accounting domains.
8. Add customer messaging, chatbot, and task-oriented agents through the shared application-command layer.
9. Add rebooking, feedback, service recovery, loyalty, and lifecycle automation.
10. Harden, test, migrate, and release.
