# WorkCore PWA Integration

This package adds a client-side WorkCore edge runtime without copying Laravel domain models or server business rules into the Chatbot extension, Titan Hub, or any mobile surface.

The same boundary applies to Titan Hub Flutter: clients render authorised projections and submit governed commands. WorkCore, Titan Pay, MagicAI, and the configured commerce source remain authoritative for their own domains.

## Integration goals

The integration must provide one continuous customer and operational workflow without creating duplicate truth for:

- customers and contacts;
- products, services, resources, and inventory;
- quotes and estimates;
- orders, jobs, projects, tasks, bookings, reservations, hire, and rentals;
- invoices, payments, receipts, refunds, deposits, bonds, and milestones;
- accounting references and customer activity.

Every major entity has one authoritative writer. Other systems hold projections, mappings, search indexes, caches, or accounting references only.

## Existing client runtime

The current Chatbot PWA edge runtime provides:

- versioned IndexedDB database (`titan-workcore-device`) with records, job packs, drafts, outbox, conflicts, attachments, metadata, and sync logs;
- typed WorkCore command contract aligned to `/api/v1/sync/operations` and `/api/v1/workcore/actions`;
- stable operation IDs and idempotency keys;
- queued, sending, sent, failed, needs-review, conflict, and cancelled states;
- HTTP 409/412 conflict capture with local and server snapshots;
- exponential retry delay, manual retry, cancellation, and online reconnection sync;
- Background Sync wake-up hooks;
- OPFS-first attachment storage with IndexedDB fallback and SHA-256 checksums;
- WorkCore local record and offline job-pack repositories;
- tenant, user, and device context bootstrap;
- floating offline queue and manual **Sync now** interface;
- full local-data reset API;
- backwards-compatible `window.chatbotPwa.queueTask()` facade.

Titan Hub Flutter should reproduce these capabilities using the closest existing QRPay/Titan patterns rather than inventing a separate offline protocol.

## Public client API

```js
await TitanWorkCore.client.configure({ company_id, user_id, api_base });
await TitanWorkCore.client.queue('operations.work-order.change-status', payload, {
  resource_key: 'work_order:123',
  base_revision: 4,
  base_etag: '"work-order-123-v4"'
});
await TitanWorkCore.client.sync();
await TitanWorkCore.client.saveJobPack(pack);
await TitanWorkCore.attachments.save(file, { resource_key: 'work_order:123' });
```

## Existing governed server routes

The WorkCore host overlay currently exposes:

```text
GET  /api/v1/workcore/actions
GET  /api/v1/workcore/actions/{action}
POST /api/v1/workcore/actions/{action}/confirm
POST /api/v1/workcore/actions/{action}/execute
POST /api/v1/workcore/flows/customer-property-work-order
GET  /api/v1/tools
POST /api/v1/sync/operations
GET  /api/v1/sync/operations/{operationId}
```

These routes already enforce authenticated user, active company, tenant, WorkCore API, and throttling middleware. Titan Hub must call them through the `/api/titan-hub/v1` application facade rather than exposing WorkCore route shapes directly to Flutter.

The existing `customer-property-work-order` flow demonstrates the required pattern:

- validate a complete command payload;
- resolve company and actor from the authenticated server context;
- require confirmation for governed actions;
- use a caller-supplied or generated idempotency key;
- execute through an application service;
- return a stable result projection.

## System authority boundaries

### MagicAI

MagicAI owns authenticated identity, tenant and company selection, plans, entitlements, roles, permissions, extension configuration, and application-manifest resolution. MagicAI user IDs are identity references, not WorkCore customer records.

### WorkCore

WorkCore owns operational business truth:

- business customers and contacts;
- properties, sites, vehicles, rooms, facilities, and service locations;
- operational products and services when WorkCore is the configured catalogue source;
- estimates and quotes;
- jobs, projects, work orders, tasks, schedules, assignments, and completion evidence;
- bookings, reservations, capacity, resource allocation, hire, and rental operations when their WorkCore modules are configured as authority;
- inventory quantities, reservations, movements, consumption, and operational costs;
- expenses, supplier references, payroll references, and accounting integration records;
- operational status policies and final conflict decisions.

### E-commerce extension

The e-commerce extension owns retail merchandising and order construction when configured as the commerce authority: categories, public product presentation, variants, media, carts, retail orders, fulfilment choices, shipping, and returns.

It must not independently own WorkCore inventory, service jobs, bookings, or Titan Pay obligations. When WorkCore is the catalogue or inventory authority, e-commerce stores a projection and stable source mapping.

### Titan Pay

Titan Pay owns financial truth:

- financial obligations and customer-facing invoice state;
- payment intents and attempts;
- immutable ledger events and postings;
- wallet balances derived from ledger entries;
- deposits, milestones, bonds, credits, refunds, and split payments;
- receipts, reconciliation, provider webhooks, and financial audit history.

WorkCore may store accounting references and financial projections, but it must not mutate Titan Pay ledger balances or maintain a competing paid/unpaid authority.

### Chatbot extension

The Chatbot extension owns conversations, messages, attachments, knowledge retrieval, human handoff, tool discovery, agent orchestration, confirmation prompts, and tool audit records.

Chatbot tools invoke the same Titan Hub application commands as Flutter screens. They do not write WorkCore, e-commerce, or Titan Pay tables directly.

### Titan Hub

Titan Hub owns the customer experience and unified projections: catalogue, quote, order, booking, rental, job, invoice, payment, and activity views; stable mobile commands and queries under `/api/titan-hub/v1`; customer-safe action availability; and correlation across systems.

Titan Hub is an orchestration and presentation boundary, not a second operational or financial database authority.

## Authority matrix

| Entity or state | Authoritative writer | Other-system role |
|---|---|---|
| User identity, tenant, active company | MagicAI | WorkCore and Titan Pay store actor references |
| Business customer and contact | WorkCore | MagicAI identity linked by mapping; e-commerce and Titan Hub project |
| Public shopper profile | MagicAI identity + Titan Hub preferences | Linked to WorkCore customer when a business relationship exists |
| Product/service definition | One configured source: WorkCore or e-commerce | Other source stores projection and source mapping |
| Inventory quantity and reservation | WorkCore when operational inventory is enabled | E-commerce requests reservations and reads projections |
| Retail cart | E-commerce/Titan commerce service | WorkCore receives committed demand only |
| Retail order | E-commerce when retail flow is enabled | WorkCore may receive fulfilment work and stock movements |
| Quote/estimate | WorkCore | Titan Hub and Chatbot present customer-safe projection |
| Job/project/work order/task | WorkCore | Titan Hub exposes status and permitted actions |
| Booking/reservation/capacity | One configured WorkCore booking authority | E-commerce and Titan Hub project availability |
| Hire/rental operation and condition | One configured WorkCore rental authority | Titan Pay owns bond/deposit value |
| Operational invoice reference | WorkCore | Linked to one Titan Pay obligation |
| Financial invoice/obligation | Titan Pay | WorkCore stores immutable reference and accounting projection |
| Payment, refund, credit, bond, deposit | Titan Pay | WorkCore receives confirmed financial events only |
| Expense and supplier cost | WorkCore | Titan Pay may later settle approved payable commands |
| Receipt | Titan Pay | WorkCore and Titan Hub project the receipt reference |
| Customer activity timeline | Titan Hub projection | Built from authoritative events; never independently writable |
| Conversation and handoff | Chatbot extension | May reference all mapped business entities |

No deployment may enable two authoritative writers for the same row. Business configuration must fail validation when authority is ambiguous.

## Canonical identifiers and provenance

Every cross-system reference includes enough provenance to resolve the source without guessing:

```json
{
  "titan_id": "titan_customer_01J...",
  "company_id": 42,
  "source_system": "workcore",
  "source_type": "customer",
  "source_id": "12345",
  "source_revision": 8,
  "source_etag": "customer-12345-v8",
  "mapping_version": 1,
  "correlation_id": "corr_01J..."
}
```

Rules:

- `titan_id` is stable and opaque to clients;
- source IDs are never accepted without tenant and company scope;
- mappings are unique by company, source system, source type, and source ID;
- mappings are not silently reassigned;
- merges and replacements append provenance history;
- deleted source records become tombstoned mappings so delayed events cannot recreate them;
- Flutter receives opaque Titan IDs and display-safe external references only.

## Governed command path

```text
Flutter screen or Chatbot tool
  -> /api/titan-hub/v1 command
     -> Titan Hub application service
        -> permission and entitlement checks
        -> confirmation/approval policy
        -> idempotency and concurrency checks
        -> WorkCore action/flow, e-commerce command, or Titan Pay command
        -> authoritative commit
        -> transactional outbox event
        -> projection updates and notifications
```

A Chatbot tool and a visual Flutter action use the same application command, input schema, confirmation rule, and idempotency policy.

Commands that create or change business value carry a stable command ID, command type, expected revision, idempotency key, confirmation ID, correlation ID, causation ID, and payload. The server derives tenant, company, actor, and permission authority from authentication.

## Idempotency

Idempotency is required for:

- customer/contact creation;
- quote acceptance;
- booking, reservation, order, job, hire, or rental creation;
- inventory reservation or release;
- invoice and payment-intent creation;
- payment capture, refund, credit, bond, deposit, and milestone commands;
- agent tools that create or modify authoritative records.

The idempotency record is scoped to company, actor, command type, and canonical request hash. Reuse with a different payload is rejected. A repeated matching request returns the original outcome.

## Optimistic concurrency and conflicts

Mutable operational resources expose a revision and ETag. State-dependent commands require the expected revision or `If-Match` value.

- `409` indicates a domain conflict or now-invalid action.
- `412` indicates a stale revision/ETag.
- The response includes the current projection, rejected command, and customer-safe recovery actions.
- Clients never use last-write-wins for quotes, bookings, rentals, jobs, invoices, stock, or financial records.
- Queued offline commands move to `needs-review` when their base state is stale.
- WorkCore remains final authority for operational merge decisions.
- Titan Pay never merges conflicting financial commands automatically.

## Events, outbox, and inbox

Authoritative writes and their outgoing events commit atomically through a transactional outbox. Each event includes event ID, versioned event type, occurred time, company, aggregate type and ID, aggregate revision, correlation ID, causation ID, idempotency key, and payload.

Consumers use an inbox keyed by event ID and consumer name. Duplicate or out-of-order events must not duplicate customers, jobs, invoices, payments, stock movements, or ledger value.

Delivery is at least once. Business effects are exactly once through idempotent consumers.

## Retry and failure policy

- Retry transport failures, timeouts, `429`, and explicitly transient `5xx` responses using bounded exponential backoff and jitter.
- Do not retry permission failures, validation failures, stale revisions, unavailable slots, insufficient stock, rejected approvals, or financial declines without a new command.
- Persist every failed integration attempt with correlation ID, source, destination, command/event type, attempt count, next attempt, and last error.
- Move exhausted operations to a reconciliation queue rather than discarding them.
- Customer-facing state must say `processing`, `needs attention`, or `failed`; it must not falsely claim completion.

## Quote-to-payment lifecycle

### 1. Customer and context resolution

Titan Hub resolves the authenticated MagicAI identity and selected business. The integration finds or creates the linked WorkCore customer/contact through an idempotent governed command.

Field-service and property flows may also resolve a property/site. Automotive resolves a vehicle. Accommodation resolves a guest/stay context. These are WorkCore operational records.

### 2. Quote intake

Flutter or Chatbot collects scope, options, dates, address/site, attachments, measurements, and vertical-specific fields. The application service submits a WorkCore quote command. WorkCore validates pricing, taxes, service rules, capacity assumptions, and approvals.

### 3. Quote publication and revision

WorkCore publishes a revisioned quote projection. Titan Hub displays only the latest customer-visible revision and server-returned allowed actions. A revision invalidates stale acceptance tokens.

### 4. Quote acceptance

The customer accepts through an idempotent `quote.accept` command with expected revision and explicit confirmation. WorkCore records acceptance and emits `workcore.quote.accepted.v1`.

Acceptance does not itself move money. It triggers configured operational and financial commands.

### 5. Operational commitment

Based on Commerce Modes and business configuration, the accepted quote creates or links a WorkCore job/work order/project, booking/reservation, hire/rental operation, e-commerce order plus fulfilment job, or membership/service workflow.

The integration stores immutable links between quote, order, booking, job, rental, and source items.

### 6. Deposit, bond, or milestone intent

When money is due before fulfilment, the workflow requests a Titan Pay obligation and payment intent. Titan Pay returns the authoritative amount, currency, due date, eligible methods, and payment state. WorkCore receives a financial reference, not a mutable balance.

A booking or resource hold becomes confirmed only according to configured policy, such as deposit authorised or paid.

### 7. Scheduling and fulfilment

WorkCore controls scheduling, capacity, assignments, resource allocation, inventory reservations, job status, and completion evidence. Reschedule, extension, cancellation, and variation commands are validated by WorkCore and may request Titan Pay adjustments.

### 8. Variations and additional charges

Operational variations originate in WorkCore and require configured customer or staff approval. Approved variations produce a new financial-obligation command in Titan Pay. No client or chatbot model may calculate or directly add an enforceable charge.

### 9. Completion and invoice issue

WorkCore records operational completion. The completion event requests Titan Pay to create or finalise the financial obligation using approved quote, variations, fulfilled quantities, taxes, deposits, credits, and milestones.

Titan Pay emits `titan_pay.invoice.issued.v1`. WorkCore stores the Titan invoice ID and accounting projection.

### 10. Payment

Titan Hub requests a Titan Pay payment intent. Provider callbacks are verified, deduplicated, and reconciled by Titan Pay. Only a confirmed Titan Pay event may mark the obligation paid. WorkCore updates its accounting/payment projection from that event.

### 11. Receipt and close-out

Titan Pay creates the receipt and emits the confirmed financial event. WorkCore closes the receivable or operational job according to policy. Titan Hub updates Activity and lifecycle automation.

### 12. Refund, dispute, or bond release

Refunds, credits, disputes, deposits, and bond releases remain Titan Pay commands. WorkCore supplies operational evidence and eligibility context but does not directly alter ledger value.

## Synchronisation rules

### WorkCore to Titan Hub

WorkCore events update customer-safe projections for quote status/revision, booking/reservation/job/hire/rental status, service windows, fulfilment milestones, stock-backed availability summaries, and allowed customer actions.

### Titan Pay to WorkCore

Titan Pay events update WorkCore projections for obligations, deposit/payment pending, paid, partially paid, overdue, disputed, refunded, credited, written off, bond held/released/claimed, receipts, and reconciliation references.

### E-commerce to WorkCore

Committed retail orders may request inventory reservations/movements, fulfilment/delivery/installation work, and return inspection. Draft carts do not create WorkCore jobs or permanent stock movements.

## Unified customer Activity projection

Titan Hub Activity is a read model built from authoritative events. Each record identifies its type, status, business, primary resource, related quote/booking/job/order/invoice/payment IDs, occurred time, next action, allowed actions, and correlation ID.

Activity records are never directly mutated by Flutter or Chatbot. They are rebuilt from source projections and events.

## Customer-safe projections

Flutter and Chatbot receive only fields approved for the customer relationship. Internal labour cost, margin, payroll, private notes, supplier pricing, staff-only risk flags, internal approvals, and accounting internals remain hidden.

Every projection declares its source revision, generated time, freshness, customer-safe status, allowed actions, offline behaviour, and related opaque Titan IDs. The language model may explain a projection but cannot invent missing state or actions.

## Offline rules

Safe offline operations may include drafting quote/service-request inputs, collecting photos/signatures/measurements/notes, acknowledging messages, preparing non-final reschedule/support requests, and staff work-order updates permitted by WorkCore policy.

Online confirmation is required for quote acceptance, final booking/reservation creation or rescheduling, inventory reservation, checkout, payment/refund/credit/deposit/bond/milestone actions, financially effective cancellation, and any command whose price, availability, eligibility, or financial result may have changed.

Offline commands remain pending until accepted by the server. The UI must never present queued work as confirmed.

## Reconciliation

Scheduled reconciliation checks for:

- orphaned or duplicate customer mappings;
- accepted quote without downstream operational commitment;
- order or booking without the expected WorkCore record;
- WorkCore completion without a Titan Pay obligation;
- Titan Pay invoice without a WorkCore accounting reference;
- paid Titan Pay obligation still shown unpaid in WorkCore;
- stock reservation without an order, booking, or rental owner;
- refund or bond event not projected to WorkCore;
- missing or stalled outbox/inbox events;
- mismatched currency, amount, tax, or revision.

Reconciliation produces repair commands or human-review cases. It never edits ledger entries or operational history silently.

## Vertical scenarios

### Field and home services

```text
Customer/property -> quote -> booking -> work order -> completion evidence
-> Titan Pay invoice -> payment -> receipt -> rebooking
```

WorkCore owns property, quote, booking, work order, schedule, materials, variations, and evidence. Titan Pay owns deposit, invoice, payment, and receipt.

### Accommodation, BnB, hotel, and rooming

```text
Guest -> room/stay reservation -> deposit -> stay services/incidentals
-> checkout obligation -> payment -> receipt
```

WorkCore owns room/resource availability, reservation, guest operations, housekeeping/service tasks, and incidentals evidence. Titan Pay owns deposit, bond where applicable, checkout obligation, and payment.

### Real estate and property management

```text
Tenant/owner/property -> maintenance request -> quote -> approval
-> contractor work order -> completion -> owner/tenant allocation -> payment
```

WorkCore owns property, maintenance case, quote, approvals, contractor job, and allocations. Titan Pay owns obligations, payment events, credits, and refunds.

### Automotive

```text
Customer/vehicle -> inspection -> estimate -> approval -> workshop booking
-> job/parts/labour -> final invoice -> payment
```

WorkCore owns vehicle, inspection, estimate, workshop bay, job, parts consumption, and service history. Titan Pay owns deposits, final invoice, payment plan, and receipt.

### Salon and fitness

```text
Customer/member -> service/class/membership selection -> booking
-> attendance/fulfilment -> subscription or one-off obligation -> payment
```

WorkCore owns capacity, staff/resource schedule, attendance, and service delivery. Titan Pay owns membership/subscription obligation and payment state.

### E-commerce and retail

```text
Cart -> retail order -> Titan Pay payment -> WorkCore stock movement
-> fulfilment/delivery/install job -> return/refund when required
```

E-commerce owns cart and retail order. WorkCore owns operational stock and fulfilment when configured. Titan Pay owns payment and refund.

### Hire and rental

```text
Customer/item/dates -> availability hold -> rental agreement
-> deposit/bond -> dispatch -> condition evidence -> return/extension
-> final charges -> bond release/refund
```

WorkCore owns item availability, rental operation, dispatch, return, condition, and damage evidence. Titan Pay owns deposit, bond, rental obligations, additional charges, and release/refund value.

## Security and audit requirements

- Scope every read, command, mapping, event, and projection by tenant and company.
- Resolve actor identity server-side.
- Require permission, entitlement, resource ownership, confirmation, and approval checks.
- Encrypt sensitive integration credentials and personal data where applicable.
- Do not place secrets, payment credentials, private staff notes, or unrestricted source payloads in offline storage.
- Record immutable audit entries for confirmations, approvals, commands, authoritative results, financial references, and repair actions.
- Propagate request and correlation IDs through Flutter, Chatbot, Titan Hub, WorkCore, Titan Pay, queues, and notifications.

## Server additions still required

The unified WorkCore server currently exposes operation submission and status endpoints. Full offline and Titan Hub integration should add or confirm stable application services for:

```text
GET  /api/v1/workcore/offline/job-packs
GET  /api/v1/workcore/offline/changes?cursor=...
POST /api/v1/workcore/offline/attachments
```

Additional capabilities should be exposed through action discovery, confirmation, execution, or versioned business-flow services rather than direct CRUD controllers:

- customer/contact resolve-or-create;
- quote create, revise, publish, accept, decline, and expire;
- order/job/booking/reservation/rental commitment;
- inventory reserve, release, consume, return, and adjust;
- completion and variation approval;
- Titan Pay obligation/reference projection;
- reconciliation query and repair-case creation.

Until job-pack pull endpoints exist, local job packs can be supplied by existing authenticated application responses through `TitanWorkCore.client.saveJobPack()`.

## Implementation sequence

1. Confirm one authoritative source per entity in business configuration.
2. Implement canonical ID mappings and provenance history.
3. Wrap existing WorkCore actions and flows behind Titan Hub application commands.
4. Add transactional outbox and idempotent inbox consumers.
5. Implement quote acceptance through operational commitment.
6. Integrate Titan Pay obligation, payment, receipt, refund, and bond events.
7. Build customer-safe Activity and detail projections.
8. Add reconciliation, repair cases, metrics, and alerts.
9. Reproduce the existing PWA offline protocol in Flutter by duplicating the closest QRPay/Titan patterns.
10. Verify every vertical scenario end to end.

## Contract completion checklist

- [x] MagicAI, WorkCore, e-commerce, Chatbot, Titan Hub, and Titan Pay boundaries are explicit.
- [x] One writer is named for customer, catalogue, quote, order, job, booking, rental, stock, invoice, and payment state.
- [x] Identifier mapping and provenance rules are defined.
- [x] Commands, idempotency, confirmation, concurrency, events, retry, and reconciliation are defined.
- [x] Quote-to-payment lifecycle is explicit.
- [x] Flutter and Chatbot share the same application-command path.
- [x] Offline-safe and online-required actions are explicit.
- [x] Initial Titan Zero vertical scenarios are covered.
