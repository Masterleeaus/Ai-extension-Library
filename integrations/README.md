# Integrations

This directory contains donor-system integrations for Titan Zero.

## Planned structure

- `qrpay-web/` — Laravel QRPay web base adapted as a donor integration for Titan Pay and Titan Hub APIs.
- `magicai-adapter/` — anti-corruption layer mapping QRPay concepts into MagicAI/Titan Zero identity, tenancy, extension, and API contracts.
- `workcore-adapter/` — integration with WorkCore quotes, invoices, accounting, jobs, bookings, inventory, customers, and receivables.

## Rules

1. MagicAI remains the application, identity, tenancy, and extension host.
2. WorkCore remains authoritative for operational records and accounting consequences where those modules already exist.
3. Titan Pay owns payment intents, attempts, wallets, reconciliation, receivables, bonds, milestones, split tender, BNPL, and payment-plan orchestration.
4. QRPay donates workflows, gateway knowledge, merchant/customer/agent journeys, QR flows, and mobile APIs; it must not remain a competing financial source of truth.
5. Cryptomus is an optional crypto rail only.
6. Financial state changes must move toward immutable events, idempotent commands, balanced ledger postings, and rebuildable projections.

Do not commit `.env` files, OAuth keys, Android/iOS signing assets, Firebase secrets, provider secrets, or vendor-generated cache/build directories.
