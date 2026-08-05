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
3. Build Titan API compatibility facade.
4. Convert QRPay mobile shell into Titan Hub navigation and identity.
5. Integrate catalogue, services, sales, bookings, rentals, and hire.
6. Integrate Titan Pay invoicing, wallets, QR, rails, BNPL, milestones, bonds, and split payments.
7. Integrate WorkCore operational and accounting domains.
8. Add customer messaging, chatbot, and task-oriented agents.
9. Add rebooking, feedback, service recovery, loyalty, and lifecycle automation.
10. Harden, test, migrate, and release.
