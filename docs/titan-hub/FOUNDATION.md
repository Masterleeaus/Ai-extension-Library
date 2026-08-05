# Titan Hub Foundation

## Product boundary

Titan Hub is the customer-facing Flutter application for Titan Zero. It combines discovery, catalogue, commerce, bookings, hire/rental, quotes, invoices, messaging, feedback, rebooking, support, and Titan Pay.

Titan Go remains the staff/operator-facing mobile experience. Shared Flutter packages may serve both apps.

## Donor systems

### QRPay Flutter

Primary donor for authentication journeys, KYC, biometrics, QR scanning, wallet/payment presentation, payment links, money requests, transaction history, receipts, notifications, hosted provider checkout, localisation, and theming.

### MobileKit

Visual and interaction reference only. Bootstrap HTML/CSS/JS must be translated into native Flutter components and Titan design tokens. Do not embed a WebView-based copy of the kit as the production interface.

### QRPay Laravel

Primary donor for merchant/customer/agent workflows, gateway integrations, QR payment concepts, payment links, money requests, transaction limits, KYC administration, remittance/provider knowledge, and Flutter API behaviour.

### MagicAI

Host Laravel application and authority for tenant identity, extension lifecycle, configuration, plans, permissions, and core AI capabilities.

### WorkCore

Operational integration target for customers, products, services, quotes, invoices, jobs, bookings, inventory, rentals, accounting, and receivables.

## Target architecture

```text
Titan Hub Flutter
  -> Titan API facade
     -> MagicAI identity and tenancy
     -> Titan Hub catalogue/commerce APIs
     -> Titan Pay domain
     -> WorkCore operational adapters
     -> Chatbot and agent orchestration

Titan Pay commands
  -> domain validation
  -> immutable event
  -> ledger posting
  -> projection update
  -> integration outbox
```

## Dual-overlay composition model

Titan Hub must not be forked into separate applications for every industry or transaction model. The resolved customer experience is composed from four layers:

```text
Titan Hub Core
+ one primary Vertical Profile
+ one or more Commerce Modes
+ business-specific configuration
= resolved customer application
```

The overlays are independent:

- **Vertical Profile** answers: what kind of business is this?
- **Commerce Mode** answers: how does this business sell, schedule, reserve, hire, or deliver value?

A business may activate several Commerce Modes at the same time. For example, a field-service business can use `service`, `booking`, `quote_first`, and `sales` without requiring separate apps or duplicated domain logic.

### Titan Hub Core

The core remains industry-neutral and supplies capabilities shared by all configurations:

- authentication, identity, KYC, account security, and consent;
- customer and business switching;
- home, explore, search, activity, messages, notifications, and support;
- QR scanning and signed deep links;
- quotes, invoices, receipts, wallet, payments, credits, and disputes;
- files, documents, feedback, preferences, and communication permissions;
- feature registry, navigation registry, route registry, design system, error states, and analytics;
- chatbot shell, human handoff, tool approval, and conversation history.

The core must not contain field-service, hotel, salon, automotive, rental, or other vertical assumptions.

## Vertical Profiles

A Vertical Profile contributes domain vocabulary and specialist behaviour without owning general commerce, payment, or identity functions.

A profile may contribute:

- terminology and labels;
- business-object aliases;
- required and optional fields;
- form fragments and validation metadata;
- home cards and activity summaries;
- specialist filters and catalogue metadata;
- workflow templates;
- document and feedback templates;
- chatbot knowledge domains and specialist tools;
- risk, compliance, evidence, and approval requirements;
- presentation metadata for existing Flutter components.

Initial profiles include:

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

Examples of profile-specific contributions:

### Field service

- service address, site contact, service zone, travel zone;
- technician preference, access instructions, site photos;
- before/after evidence, materials, variations, completion approval;
- service-area and job-site chatbot tools.

### Hotel and BnB

- property, room type, guest, check-in/check-out, occupancy;
- amenities, incidentals, stay services, housekeeping requests;
- reservation and guest-service terminology.

### Automotive

- vehicle, registration, VIN, odometer, workshop bay;
- service history, inspection report, labour and parts metadata.

A Vertical Profile must not directly create orders, bookings, payments, or wallet entries. It supplies configuration and domain-specific validation to the authoritative backend service.

## Commerce Modes

Commerce Modes are reusable transaction patterns that can be enabled independently or together.

Initial modes:

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

### Service mode

Contributes service catalogue, scope intake, location, duration, service options, completion, and rebooking.

### Booking mode

Contributes availability, time slots, duration, capacity, staff/resource selection, rescheduling, and cancellation.

### Quote-first mode

Contributes structured requirements, measurements, attachments, quote requests, revisions, acceptance, and deposits.

### Sales mode

Contributes products, variants, stock, cart, checkout, fulfilment, shipping, returns, and reorder.

### Hire and rental modes

Contribute date ranges, item availability, collection/delivery, bond, condition evidence, extension, return, late fees, and bond-release status.

### Membership and subscription modes

Contribute plans, recurring billing, entitlements, usage limits, renewal, pause, upgrade, downgrade, and cancellation.

Each mode registers capabilities against shared Titan contracts rather than introducing its own customer, payment, invoice, or notification authority.

## Resolved application manifest

Laravel is responsible for resolving overlays, tenant policy, permissions, branding, connected extensions, and provider capabilities into one signed manifest.

Recommended endpoint:

```text
GET /api/titan-hub/app-manifest
```

Representative manifest:

```json
{
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
  "branding": {},
  "cache": {
    "etag": "manifest-version",
    "expires_at": "ISO-8601"
  }
}
```

Flutter must not infer a vertical or enable a Commerce Mode from local assumptions. It renders the backend-resolved manifest and may cache the last valid signed configuration for controlled offline use.

## Composition and conflict rules

Overlay resolution must be deterministic.

1. Titan Hub Core registers immutable base capabilities.
2. The Vertical Profile contributes terminology, fields, specialist validation, templates, and presentation metadata.
3. Commerce Modes contribute transaction capabilities, routes, actions, and workflow metadata.
4. Business configuration enables, disables, renames, orders, or restricts allowed contributions.
5. Permissions and provider capabilities remove unavailable actions.
6. The resolver validates the final graph and rejects unresolved collisions.

### Navigation rules

- Merge semantically equivalent entries instead of displaying duplicates.
- Use the most specific approved business or vertical label.
- One route owner controls each canonical destination.
- Multiple modes may contribute actions inside one destination.
- Core payment, account, activity, and messaging destinations remain shared.
- Business configuration may reorder or hide optional destinations but cannot bypass permissions.

Example: `service`, `booking`, and `reservation` may all request booking-related navigation. The resolver should expose one canonical `Book` destination with mode-specific flows inside it, not separate `Book Service`, `Bookings`, `Schedule`, and `Appointments` tabs.

### Capability conflict rules

Resolve collisions in this order:

1. security, legal, and permission restrictions;
2. authoritative backend capability;
3. explicit business override;
4. Vertical Profile specificity;
5. Commerce Mode priority;
6. Titan Hub Core default.

Conflicting price, inventory, availability, deposit, bond, refund, or cancellation data must never be resolved in Flutter. The authoritative Laravel service returns the final allowed state.

### Route rules

Every manifest route declares:

- canonical route key;
- owning feature;
- required capabilities;
- required permissions;
- supported item types;
- deep-link patterns;
- offline behaviour;
- fallback destination.

Flutter registers only routes present in the resolved manifest and maps them to existing or duplicated native screen patterns.

## Example compositions

### Field service

```json
{
  "vertical_profile": "field_service",
  "commerce_modes": ["service", "booking", "quote_first", "sales"]
}
```

Customer capabilities include service discovery, job-detail intake, appointment selection, larger-work quote requests, parts/product purchase, deposits, invoices, rebooking, and site communication.

### Hotel or BnB

```json
{
  "vertical_profile": "hotel_bnb",
  "commerce_modes": ["accommodation", "reservation", "booking", "service", "sales", "hire"]
}
```

Customer capabilities include stays, room selection, guest services, amenity sales, spa/dining bookings, equipment hire, incidentals, checkout, and stay feedback.

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

Flutter is the native presentation and interaction layer.

It owns:

- native screens, widgets, navigation rendering, local view state, accessibility, secure device storage, deep-link handling, and controlled offline presentation;
- rendering the resolved manifest;
- invoking stable Titan commands and queries;
- presenting chatbot cards and approved actions.

Flutter does not own:

- authoritative pricing;
- inventory or availability truth;
- order, booking, rental, quote, invoice, wallet, or payment state;
- eligibility, refund, bond, discount, or approval decisions;
- chatbot business tools.

When adding screens or UI elements, first locate the closest existing QRPay or later Titan Hub screen. Duplicate and adapt that implementation so route, state, component, theme, validation, and interaction styles remain consistent. Create new UI structures only when no suitable donor exists.

## Laravel implementation boundary

Laravel remains the authority for:

- tenant and business configuration;
- overlay resolution and manifest signing;
- catalogue, pricing, inventory, availability, carts, orders, bookings, rentals, and quotes;
- Titan Pay, invoices, receivables, wallets, bonds, milestones, BNPL, and reconciliation;
- chatbot tools, agents, permissions, approvals, audit, and WorkCore integration.

The e-commerce and chatbot extensions remain Laravel modules. They are not converted into Flutter code. A Titan API facade protects Flutter from extension-specific routes, models, and version changes.

## Chatbot and agent composition

The chatbot receives the same resolved context as the visual app:

```text
Core chatbot tools
+ Vertical Profile tools
+ Commerce Mode tools
+ business permissions and policy
= authorised Titan Echo tool set
```

The chatbot and Flutter screens must call the same backend application services. Chat must never create alternative pricing, availability, booking, order, payment, or refund logic.

Example for field service with service and booking modes:

- profile tools: collect service address, site details, photos, service-zone checks;
- service tools: search services, collect scope, create service draft;
- booking tools: check availability, propose slots, create booking draft;
- quote-first tools: create quote request and collect measurements;
- Titan Pay tools: present an approved deposit or invoice payment action.

Sensitive actions remain permissioned and approval-controlled.

## Security gate

Before donor import:

- remove `.env` files and secrets;
- remove OAuth private keys;
- remove Android signing keys and signing properties;
- replace Firebase and iOS service configurations;
- remove build artefacts and caches;
- replace insecure mobile token storage;
- redact request/response logs;
- audit direct wallet balance mutation and concurrency;
- add webhook signature verification and idempotency.

## Delivery sequence

1. Import and sanitise licensed donor sources.
2. Establish Flutter feature-module architecture and Titan design system.
3. Build Titan API compatibility facade and dual-overlay manifest resolver.
4. Convert QRPay mobile shell into Titan Hub navigation and identity.
5. Integrate catalogue, services, sales, bookings, rentals, and hire through Commerce Modes.
6. Integrate Titan Pay invoicing, wallets, QR, rails, BNPL, milestones, bonds, and split payments.
7. Integrate WorkCore operational and accounting domains.
8. Add customer messaging, chatbot, and task-oriented agents using the resolved overlay tool set.
9. Add rebooking, feedback, service recovery, loyalty, and lifecycle automation.
10. Harden, test, migrate, and release.
