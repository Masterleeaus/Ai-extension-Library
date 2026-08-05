---
title: Titan Pay Immutable Event and Ledger Contract
status: Architecture approved for implementation
created: 2026-08-05
updated: 2026-08-05
parent_issue: 259
implementation_issue: 367
product_family: Titan Hub, Titan Pay, MagicAI and WorkCore
structural_provenance:
  - docs/titan-pricebook-deep-design.md
  - docs/titan-hub/FOUNDATION.md
  - app/extensions/Chatbot/WORKCORE-PWA-INTEGRATION.md
---

# Titan Pay

### Immutable event, double-entry ledger, wallet and payment-reconciliation contract

**One reproducible financial authority for invoices, wallets, payment rails, deposits, milestones, bonds, refunds, credits, splits and receivables.**

This document is the implementation contract for Titan Pay. It uses the authority-first product-design structure established by Titan Pricebook and the integration boundaries already established by Titan Hub and WorkCore.

Titan Pay must not become a cosmetic rename of QRPay and must not copy QRPay's mutable balance model into MagicAI. QRPay Laravel is a payment workflow and provider donor. Titan Pay is the canonical financial authority.

Its end-to-end outcome is:

> Record every financial decision as an immutable domain event, represent every movement of value as a balanced ledger posting, derive customer-facing state through rebuildable projections, and reconcile external providers without allowing callbacks or clients to create value twice.

---

## 1. Product thesis

Payments become unsafe when several systems each believe they own the same balance or obligation.

Typical failure patterns include:

- a wallet balance column changed directly by a controller;
- a provider callback marking an invoice paid without a balanced posting;
- retries creating duplicate credits;
- a refund mutating the original transaction rather than recording a reversal;
- cash payments being treated as trusted because a client submitted them;
- QR payloads containing permanent or unsigned financial instructions;
- separate invoice totals in commerce, WorkCore and the payment donor;
- bonds being mixed with business revenue;
- deposits and milestones being represented only by labels on a transaction;
- provider settlement fees being hidden from reconciliation;
- out-of-order webhooks moving an attempt backwards;
- historical balances changing when current business rules change;
- reporting tables becoming the only source of financial truth.

Titan Pay prevents these failures through five foundations:

1. **Immutable events** describe what was decided or observed.
2. **Balanced ledger postings** describe every movement of value.
3. **Governed aggregates** enforce state transitions and concurrency.
4. **Rebuildable projections** provide fast customer and operator views.
5. **Provider reconciliation** proves external money movement agrees with internal records.

### Product promise

**Every financial state is explainable, reproducible, balanced and traceable to the command, actor, provider evidence and operational obligation that caused it.**

---

## 2. Application boundary

Titan Pay is the financial authority. It is not the customer, catalogue, booking, job, inventory, payroll or general-accounting authority.

### Titan Pay owns

- financial obligations and financial invoice state;
- payment intents and payment attempts;
- immutable provider transaction evidence;
- receipts and payment confirmations;
- the event store for Titan Pay aggregates;
- the Titan Pay double-entry subledger;
- customer and business wallet liabilities;
- pending, available, reserved, restricted and expiring wallet positions;
- refunds, credits, overpayments and unapplied funds;
- deposits, milestone obligations and milestone releases;
- bonds, bond holds, bond releases and bond applications;
- split tender and payment allocation;
- revenue-allocation instructions and beneficiary liabilities;
- payment plans and instalment schedules;
- provider settlement, fee and reconciliation records;
- receivables state used by payment follow-up;
- financial audit history and rebuildable financial projections.

### MagicAI remains authoritative for

- tenant and business identity;
- authenticated actor identity;
- plans, entitlements and feature flags;
- permission and approval policy;
- extension lifecycle and configuration;
- encrypted provider credentials through the approved credential-vault boundary.

### E-commerce remains authoritative for

- products, services, variants and catalogue content;
- carts and order construction;
- taxes, delivery, fulfilment and commerce policy where configured;
- order and return operational state.

### WorkCore remains authoritative for

- customers and contacts as operational identities;
- quotes and estimates as operational/commercial documents;
- jobs, projects, tasks and field activity;
- bookings where WorkCore is the configured booking authority;
- inventory where WorkCore is the configured inventory authority;
- expenses, supplier records and operational accounting references;
- general-ledger export references and external-accounting synchronisation.

### Chatbot remains authoritative for

- conversations, messages and attachments;
- tool discovery and routing;
- human handoff and conversation audit;
- agent orchestration subject to Titan governance.

### Titan Hub Flutter remains responsible for

- presenting customer-safe projections;
- collecting command inputs;
- biometric or device confirmation where configured;
- hosted-provider handoff;
- QR scanning and signed deep-link handling;
- displaying receipts, invoice status and allowed actions.

Flutter never owns a wallet balance, invoice balance, payment status, refund decision, provider result or ledger entry.

### Core boundary rule

Titan Pay answers:

> What financial obligation exists, what value has moved, where is that value held, what remains due, and what immutable evidence proves the answer?

It does not decide what service was delivered, whether a room is available, whether a job is complete, or whether inventory should be reserved. Those decisions arrive as governed commands or authoritative domain events.

---

## 3. Non-negotiable financial invariants

These rules apply to every implementation, adapter, migration, command, projection and test.

1. **No direct balance mutation.** No controller, agent, provider callback, migration helper or UI may increment or decrement a wallet or account balance column.
2. **Every value movement balances.** For each posting batch and currency, total debits equal total credits.
3. **Posted entries are immutable.** A posted event, batch or ledger entry is never edited or deleted. Corrections use new compensating events and reversal postings.
4. **One tenant per financial stream.** Events, accounts, entries, obligations, attempts and provider evidence are tenant-scoped and cannot cross tenant boundaries.
5. **One currency per posting line.** Amounts are stored as integer minor units plus currency. Cross-currency movement requires explicit paired legs and an exchange record.
6. **No floating-point money.** Financial amounts never use binary floating-point types.
7. **Provider evidence is not ledger authority.** A provider webhook is an observation. A governed aggregate decides whether it creates a new state transition and posting.
8. **Duplicate inputs do not duplicate value.** Command idempotency, provider-event uniqueness and posting-batch uniqueness are mandatory.
9. **Out-of-order inputs cannot regress state.** Provider sequence, occurrence time and aggregate transition rules prevent stale events moving an attempt backwards.
10. **An obligation is separate from payment.** An invoice can exist without payment, and a payment can exist as unapplied funds until allocated.
11. **A receipt follows recognised value.** A final receipt is issued only after the relevant payment posting is committed.
12. **Bonds are liabilities, not revenue.** Bond funds remain segregated liabilities until released or applied to an authorised obligation.
13. **Deposits are not automatically earned revenue.** Recognition depends on the configured accounting/export policy and operational fulfilment evidence.
14. **Projection failure does not lose truth.** Events and ledger postings commit before asynchronous projections. Projections can be rebuilt.
15. **Financial commands are online-required by default.** Offline clients may draft or queue explicitly safe non-value commands, but cannot finalise money movement without a defined online protocol.
16. **AI cannot self-authorise value movement.** Agents may explain, prepare, recommend and collect inputs. They cannot bypass permissions, limits, approvals or deterministic financial rules.
17. **No Titan percentage transaction fee.** Titan Pay may record configured provider costs or separately contracted fixed services, but must not silently calculate a Titan percentage of customer transactions.

---

## 4. Money and identifier representation

### Money

Canonical money values use:

```json
{
  "amount_minor": 42000,
  "currency": "AUD"
}
```

Rules:

- `amount_minor` is a signed integer in the currency's defined minor unit;
- currency uses an approved ISO 4217 code where applicable;
- zero-decimal and three-decimal currencies use currency metadata rather than hard-coded assumptions;
- display formatting is a presentation concern;
- calculations use explicit rounding policy and record the applied policy;
- tax, fee, discount and exchange calculations preserve source inputs and result evidence.

### Canonical identifiers

All public Titan Pay resources use opaque identifiers with type prefixes, for example:

- `obl_...` — financial obligation;
- `inv_...` — invoice projection/reference;
- `pi_...` — payment intent;
- `pa_...` — payment attempt;
- `ptx_...` — provider transaction evidence;
- `rcpt_...` — receipt;
- `rfnd_...` — refund;
- `cr_...` — credit;
- `wal_...` — wallet;
- `acct_...` — ledger account;
- `jrn_...` — journal/posting batch;
- `le_...` — ledger entry;
- `bond_...` — bond;
- `mile_...` — milestone;
- `plan_...` — payment plan;
- `rec_...` — reconciliation case.

Provider IDs and donor IDs are stored as source references, never exposed as Titan's sole public identity.

### Required context

Every financial command resolves:

- tenant ID;
- business ID;
- authenticated actor ID and actor type;
- customer relationship where applicable;
- permission and approval context;
- correlation ID;
- causation ID where applicable;
- idempotency key;
- expected aggregate version for state-dependent commands;
- client/device/channel metadata;
- authoritative source references.

---

## 5. Event-store contract

Titan Pay uses tenant-scoped aggregate streams. The event store is an append-only record of domain decisions and accepted external observations.

### Stream identity

A stream is identified by:

```text
tenant_id + aggregate_type + aggregate_id
```

Examples:

- `payment_intent:pi_123`;
- `invoice:inv_123`;
- `wallet:wal_123`;
- `bond:bond_123`;
- `payment_plan:plan_123`.

### Event envelope

Every event contains at least:

```json
{
  "event_id": "evt_...",
  "tenant_id": "tenant_...",
  "business_id": "business_...",
  "aggregate_type": "payment_intent",
  "aggregate_id": "pi_...",
  "aggregate_version": 4,
  "event_type": "payment.attempt_succeeded",
  "event_version": 1,
  "occurred_at": "ISO-8601",
  "recorded_at": "ISO-8601",
  "actor": {
    "type": "customer",
    "id": "customer_..."
  },
  "correlation_id": "corr_...",
  "causation_id": "cmd_or_evt_...",
  "idempotency_key": "...",
  "payload": {},
  "metadata": {
    "source_system": "titan_hub",
    "source_reference": "...",
    "provider": null,
    "request_id": "req_..."
  }
}
```

### Append rules

- aggregate versions begin at one and increase without gaps;
- append requires the expected current version;
- concurrent stale appends fail with an optimistic-concurrency error;
- one idempotency key maps to one canonical command hash and result;
- reusing the same key with different canonical input is rejected;
- event IDs are globally unique;
- tenant, aggregate and version uniqueness is enforced at the database level;
- event payloads are schema-versioned;
- sensitive provider payloads are minimised, encrypted or stored behind restricted evidence references;
- events contain enough canonical information to rebuild aggregate state without depending on mutable provider tables.

### Event taxonomy

Use past-tense domain events, not controller or UI names.

Representative groups:

#### Obligations and invoices

- `obligation.created`
- `obligation.adjusted`
- `invoice.issued`
- `invoice.due_date_changed`
- `invoice.disputed`
- `invoice.dispute_resolved`
- `invoice.voided`
- `invoice.written_off`
- `payment.allocated_to_invoice`
- `payment.deallocated_from_invoice`

#### Payment intents and attempts

- `payment.intent_created`
- `payment.intent_authorisation_required`
- `payment.intent_cancelled`
- `payment.attempt_started`
- `payment.attempt_requires_action`
- `payment.attempt_authorised`
- `payment.attempt_succeeded`
- `payment.attempt_failed`
- `payment.attempt_expired`
- `payment.attempt_cancelled`

#### Provider observations

- `provider.event_received`
- `provider.event_verified`
- `provider.event_rejected`
- `provider.transaction_observed`
- `provider.settlement_observed`
- `provider.chargeback_observed`

#### Wallets and stored value

- `wallet.opened`
- `wallet.funds_credited`
- `wallet.funds_reserved`
- `wallet.reservation_released`
- `wallet.funds_debited`
- `wallet.funds_restricted`
- `wallet.restriction_released`
- `wallet.credit_expired`
- `wallet.closed`

#### Refunds, credits and disputes

- `refund.requested`
- `refund.approved`
- `refund.attempt_started`
- `refund.succeeded`
- `refund.failed`
- `credit.issued`
- `credit.applied`
- `chargeback.opened`
- `chargeback.won`
- `chargeback.lost`

#### Deposits, milestones and bonds

- `deposit.required`
- `deposit.received`
- `milestone.created`
- `milestone.approved`
- `milestone.invoiced`
- `milestone.paid`
- `bond.required`
- `bond.received`
- `bond.release_requested`
- `bond.released`
- `bond.application_approved`
- `bond.applied`

#### Plans and subscriptions

- `payment_plan.created`
- `payment_plan.activated`
- `instalment.due`
- `instalment.paid`
- `instalment.failed`
- `payment_plan.defaulted`
- `payment_plan.completed`

### Snapshots

Snapshots may optimise aggregate loading but are never authoritative over events.

A snapshot records:

- tenant and aggregate identity;
- included aggregate version;
- state schema version;
- state hash;
- creation time;
- projector/application version.

Snapshots can be deleted and rebuilt. Events cannot.

### Replay and upcasting

- historical event payloads remain immutable;
- upcasters translate older event schemas into the current in-memory representation;
- replay never calls payment providers, sends notifications or repeats external side effects;
- projectors use deterministic event handlers;
- a replay run has an identifier, target projection, cursor, status and audit record;
- replay can operate tenant-by-tenant and projection-by-projection;
- production rebuilds use shadow tables or versioned projections before cutover.

---

## 6. Aggregate contract

Aggregates protect business invariants. They do not expose mutable ORM models as public services.

Initial aggregates:

- FinancialObligation;
- Invoice;
- PaymentIntent;
- PaymentAttempt where separate stream scale is required;
- Wallet;
- Refund;
- Credit;
- Bond;
- MilestoneSchedule;
- PaymentPlan;
- ReconciliationCase.

### Command handling

A command handler:

1. resolves tenant, actor, permission, entitlement and policy;
2. validates idempotency and canonical input hash;
3. loads the aggregate event stream at an expected version;
4. validates deterministic business rules;
5. produces zero or more new domain events;
6. translates approved value movement into a balanced posting batch;
7. appends events and postings atomically where they belong to the same financial decision;
8. writes outbox messages in the same transaction;
9. returns a stable command receipt and current projection hints.

No command handler trusts client-calculated totals, provider status text or chatbot prose.

---

## 7. Double-entry ledger contract

The Titan Pay subledger is the canonical record of value movement.

### Ledger dimensions

Every account and entry is scoped by:

- tenant;
- business or platform owner where applicable;
- currency;
- account type;
- account purpose;
- owner reference where applicable;
- restriction or programme reference where applicable.

### Account classes

Initial classes:

#### Assets

- provider clearing;
- bank clearing;
- cash on hand;
- settlement receivable;
- accounts receivable;
- chargeback receivable where policy permits;
- suspense asset.

#### Liabilities

- customer wallet available;
- customer wallet pending;
- customer wallet reserved;
- customer wallet restricted;
- customer expiring credit;
- unapplied customer funds;
- customer refund payable;
- bond liability;
- beneficiary allocation payable;
- tax payable where Titan Pay carries the subledger reference;
- provider settlement payable where applicable;
- suspense liability.

#### Revenue, expense and equity/reference accounts

- revenue-category references where configured;
- payment-fee expense;
- chargeback expense;
- bad-debt/write-off expense;
- promotional-credit expense;
- rounding gain/loss within approved limits;
- opening-balance equity or migration clearing.

Titan Pay's subledger does not replace an external general ledger. It exports balanced references and mappings to the configured accounting authority.

### Posting batch

A posting batch represents one atomic financial meaning.

```json
{
  "id": "jrn_...",
  "tenant_id": "tenant_...",
  "currency": "AUD",
  "event_id": "evt_...",
  "correlation_id": "corr_...",
  "posting_type": "payment_capture",
  "effective_at": "ISO-8601",
  "recorded_at": "ISO-8601",
  "reverses_batch_id": null,
  "entries": []
}
```

Rules:

- each batch has at least two entries;
- debits equal credits per currency;
- the batch is committed atomically;
- a domain event can reference one or more batches when explicit multi-stage accounting is required;
- one canonical posting fingerprint prevents duplicate posting for the same financial transition;
- reversals reference the original batch and use opposite entries;
- posted batches are never updated or deleted.

### Ledger entry

Each entry contains:

- entry ID;
- batch ID;
- tenant ID;
- account ID;
- debit or credit side;
- positive integer amount in minor units;
- currency;
- effective and recorded timestamps;
- source event ID;
- obligation, invoice, payment, wallet, bond, milestone, plan or provider references as dimensions;
- customer-safe description key and restricted internal memo where applicable.

### Balancing examples

These examples describe the intended accounting shape. Exact chart-of-account mappings remain configurable.

#### Invoice issued

```text
Debit  Accounts receivable
Credit Revenue reference
Credit Tax payable reference, when applicable
```

#### External payment captured

```text
Debit  Provider or bank clearing
Credit Accounts receivable
```

#### Provider settlement received with fee

```text
Debit  Bank asset
Debit  Payment-fee expense
Credit Provider clearing
```

#### Wallet top-up recognised

```text
Debit  Provider or bank clearing
Credit Customer wallet available liability
```

#### Wallet funds reserved

```text
Debit  Customer wallet available liability
Credit Customer wallet reserved liability
```

#### Wallet payment applied to invoice

```text
Debit  Customer wallet available or reserved liability
Credit Accounts receivable
```

#### Bond received

```text
Debit  Provider or bank clearing
Credit Bond liability
```

#### Bond returned

```text
Debit  Bond liability
Credit Bank or provider clearing
```

#### Bond applied to an authorised damage invoice

```text
Debit  Bond liability
Credit Accounts receivable
```

#### Credit issued to customer wallet

```text
Debit  Approved expense, contra-revenue or migration account
Credit Customer wallet available or expiring liability
```

### Invariant enforcement

Database and application safeguards must ensure:

- no unbalanced batch can reach `posted`;
- an account currency matches its entry currency;
- entries cannot be negative; side determines direction;
- closed or frozen accounts reject prohibited postings;
- tenant IDs match across batch, account and referenced aggregates;
- a reversal cannot exceed or repeatedly reverse the same unreversed amount;
- restricted balances cannot satisfy unauthorised obligations;
- expiring credits cannot become negative through race conditions;
- suspense accounts require reconciliation ownership and ageing alerts.

---

## 8. Wallet contract

A wallet is a governed view over ledger accounts, not a mutable pot of money.

### Wallet identity

A wallet belongs to one tenant and one owner context, such as:

- customer;
- business;
- contractor or beneficiary where approved;
- platform programme for promotional credits.

A wallet may contain multiple currencies, but each currency has separate ledger accounts and balances.

### Balance buckets

Customer-facing wallet projections may expose:

- **available** — can be used for eligible commands now;
- **pending** — value observed but not yet available;
- **reserved** — value committed to a payment, bond, milestone or authorisation;
- **restricted** — value limited to a tenant, business, programme, item or purpose;
- **expiring** — promotional or credit value with expiry terms;
- **total** — display summary only, never an authority for spending.

Each bucket is derived from mapped ledger-account balances.

### Wallet rules

- no wallet table stores an independently mutable authoritative balance;
- cached balance columns, if used for performance, are projections with version and rebuild support;
- reserve and capture are separate commands and postings;
- insufficient funds is checked against the latest locked/serialised ledger position;
- concurrent spends cannot overdraw an account;
- wallet-to-wallet transfers require explicit debit and credit accounts and policy approval;
- promotional credits record issuer, programme, restrictions and expiry;
- expired credit uses a specific expiry event and posting;
- wallet closure requires zero or explicitly migrated/released balances;
- wallet history is rendered from financial activity projections linked to events and batches.

---

## 9. Financial obligations and invoice contract

Titan Pay represents the amount legally or commercially due. Operational systems provide the source context, but cannot directly mark the financial obligation paid.

### Obligation sources

An obligation may originate from:

- accepted quote;
- confirmed booking or reservation;
- order checkout;
- completed job or approved variation;
- deposit requirement;
- milestone approval;
- rental extension, damage or late return;
- membership or subscription cycle;
- manual authorised invoice;
- approved fee or adjustment.

### Source contract

Every obligation records:

- tenant and business;
- customer/debtor reference;
- authoritative source system and source ID;
- obligation type;
- line-level description, amount, tax and allocation evidence;
- currency;
- due and service dates;
- versioned terms;
- source correlation ID;
- immutable source snapshot hash or version reference;
- allowed payment methods and policy.

### Invoice states

Canonical projection states:

- `draft`;
- `issued`;
- `partially_paid`;
- `paid`;
- `overpaid`;
- `due`;
- `overdue`;
- `disputed`;
- `payment_plan_active`;
- `void`;
- `written_off`.

State is derived from obligation events, allocations, due dates, disputes and plan status. A provider callback cannot directly assign it.

### Invoice balances

Projected fields include:

- original amount;
- adjustments;
- tax;
- amount allocated;
- amount refunded or deallocated;
- credits applied;
- amount due;
- unapplied overpayment;
- next due date;
- ageing bucket;
- allowed actions.

These fields are rebuilt from events and ledger-linked allocations.

### Adjustments

An issued obligation is not silently overwritten. Changes use:

- adjustment events;
- additional obligation lines;
- credit notes;
- void-and-reissue where legally or operationally required;
- versioned source evidence.

---

## 10. Payment-intent and attempt contract

A payment intent represents the business request to collect a specific amount for a defined purpose. Attempts represent individual rail/provider executions.

### Payment intent

Required fields:

- tenant, business and customer context;
- amount and currency;
- purpose;
- target obligations or unapplied-funds policy;
- eligible payment rails;
- expiry;
- capture policy;
- return/callback context;
- risk and approval policy;
- idempotency and correlation IDs;
- signed metadata safe for provider round trips.

Canonical states:

- `draft`;
- `requires_payment_method`;
- `requires_action`;
- `processing`;
- `partially_satisfied`;
- `satisfied`;
- `cancelled`;
- `expired`;
- `failed`.

### Payment attempt

Each attempt records:

- selected rail and adapter version;
- provider account reference;
- provider transaction/reference IDs;
- amount and currency;
- current canonical status;
- provider status evidence;
- initiation, authorisation, success, failure and expiry times;
- failure category and retryability;
- hosted-checkout or action requirements;
- posting and allocation references;
- immutable request/response evidence hashes;
- reconciliation status.

Canonical attempt states:

- `created`;
- `initiating`;
- `requires_action`;
- `authorised`;
- `processing`;
- `succeeded`;
- `failed`;
- `cancelled`;
- `expired`;
- `reversed`;
- `chargeback_open`;
- `chargeback_lost`;
- `chargeback_won`.

Terminal state transitions are controlled. Stale provider events cannot move a succeeded attempt back to processing or failed.

### Split tender

One intent may be satisfied by several attempts, for example:

- wallet plus card;
- cash plus bank transfer;
- deposit credit plus PayID;
- two cards where provider rules permit.

Each attempt posts independently. Allocation projections determine when the intent and obligations are fully satisfied.

---

## 11. Payment-rail adapter contract

Adapters translate provider-specific operations into Titan Pay commands and observations. They never write invoices, wallets or ledger entries directly.

Every adapter declares:

- rail name and adapter version;
- supported currencies and countries;
- capabilities such as authorise, capture, refund, void, recurring, webhook, settlement and dispute;
- required credential references;
- provider account and environment;
- amount limits and provider restrictions;
- idempotency support;
- webhook verification method;
- hosted or native interaction mode;
- reconciliation evidence available;
- health and capability-discovery status.

### QR

A QR code is a signed transport mechanism, not a balance or permanent payment authority.

QR payloads must be:

- signed;
- expiring;
- tenant and business bound;
- purpose bound;
- amount/currency bound when fixed;
- nonce or intent bound;
- single-use where required;
- safe against substitution and replay.

Scanning resolves a server-side payment intent. Flutter does not trust the QR payload as final authority.

### Cash

Cash attempts require an authorised collector, merchant or staff confirmation.

Rules:

- the customer client cannot self-confirm cash received;
- collector identity, time, location/evidence where policy permits and receipt number are recorded;
- cash postings use cash-on-hand or agent-clearing accounts;
- cash handover/deposit reconciliation remains explicit;
- reversals require approval and cannot erase the original receipt.

### Bank transfer and PayID

Bank/PayID attempts may use:

- unique payment references;
- virtual-account or provider identifiers;
- manual proof submission as unverified evidence;
- bank feed, provider callback or authorised operator confirmation;
- unmatched-funds reconciliation.

A customer marking a transfer sent does not mark an invoice paid. It creates a report/evidence state until authoritative receipt is confirmed.

### PayPal and card

Prefer hosted or provider-tokenised checkout. Titan does not store raw card data.

Adapters must support where available:

- idempotent intent/order creation;
- strong customer authentication or provider action;
- verified callbacks;
- capture and refund;
- dispute and chargeback evidence;
- settlement and fee reconciliation.

### Wallet

Wallet payment is an internal ledger command. It still uses a payment intent and allocation path so the customer experience and audit remain consistent with external rails.

### BNPL and payment plans

Titan Pay distinguishes:

- external BNPL provider financing;
- tenant-provided instalment plans;
- promises to pay.

External BNPL uses a provider adapter and settlement evidence. Internal plans create scheduled obligations and do not pretend future instalments are already paid.

### Optional crypto

Cryptomus or another approved crypto adapter remains optional and feature-gated.

Requirements include:

- explicit supported assets/networks;
- quoted fiat and crypto amounts with expiry;
- confirmation thresholds;
- underpayment/overpayment policy;
- address/invoice uniqueness;
- webhook verification and replay protection;
- volatility and refund rules;
- accounting currency and exchange evidence;
- jurisdiction, plan and tenant eligibility.

Crypto balances do not become a parallel wallet authority.

---

## 12. Provider webhook inbox and replay protection

All provider callbacks enter a fail-closed inbox before domain processing.

### Inbox record

Records include:

- tenant/provider endpoint resolution;
- provider event ID;
- event type;
- signature/key version;
- received time;
- provider occurrence time;
- raw-body hash;
- encrypted or restricted evidence reference;
- verification result;
- replay/duplicate result;
- processing status;
- attempt and transaction references;
- error and retry metadata.

### Processing rules

1. Resolve endpoint and tenant without trusting payload tenant fields.
2. Verify signature against the exact raw body.
3. Enforce timestamp tolerance and replay rules.
4. Store the verified provider event with uniqueness constraints.
5. Translate it into a provider observation.
6. Load the relevant aggregate.
7. Ignore duplicate or non-advancing observations safely.
8. Emit canonical domain events only when a valid state transition occurs.
9. Post ledger value only once.
10. Record processing and reconciliation outcome.

Provider endpoints return safe acknowledgements without leaking tenant or transaction details.

---

## 13. Idempotency, ordering and concurrency

### Command idempotency

Required for commands that can create or alter value, including:

- create payment intent;
- begin attempt;
- confirm cash receipt;
- reserve/capture/release wallet funds;
- issue invoice;
- allocate/deallocate payment;
- request/approve/execute refund;
- receive/release/apply bond;
- approve/invoice/pay milestone;
- create or alter payment plan;
- create credit;
- execute provider reconciliation adjustment.

The idempotency record includes tenant, actor, command type, key, canonical request hash, result reference and expiry/retention policy.

### Provider uniqueness

Uniqueness is enforced for the strongest provider identity available, such as:

```text
provider + account + environment + provider_event_id
provider + account + provider_transaction_id + canonical_transition
```

### Ordering

- aggregate version controls internal command ordering;
- provider occurrence time is evidence, not the sole state-order authority;
- provider sequence/version fields are retained when available;
- terminal-state precedence and transition rules reject regression;
- late settlement or chargeback events may create new valid transitions without rewriting earlier history.

### Database concurrency

Implementation must use database constraints and transactional locking where needed. Application-level checks alone are insufficient for:

- wallet available-funds checks;
- one-time capture;
- one-time refund amount limits;
- posting fingerprints;
- aggregate sequence numbers;
- provider-event uniqueness;
- invoice allocation totals;
- bond release/application limits.

---

## 14. Receipts, refunds, credits and overpayments

### Receipts

A final receipt references:

- tenant and business;
- payer/customer;
- payment intent and successful attempts;
- allocated obligations;
- amount and currency;
- payment method display metadata;
- effective payment time;
- ledger batch references;
- provider references safe for display;
- immutable receipt number/version;
- reversal/refund status.

Receipts can be superseded or annotated but not erased.

### Refunds

Refund workflow:

1. request with amount, reason and target attempt/allocation;
2. validate refundable amount, policy, permission and approval;
3. record approval where required;
4. create refund attempt through the original or approved rail;
5. accept provider observation;
6. post reversal/refund value once;
7. update allocation and invoice projections;
8. issue customer notification and refund receipt.

A failed provider refund does not post completed value.

### Credits

Credits may be:

- invoice credit notes;
- wallet credits;
- promotional restricted credits;
- service-recovery credits;
- migration opening credits.

Each credit has issuer, reason, funding account, restrictions, expiry and audit evidence.

### Overpayments and unapplied funds

Overpayments become explicit unapplied customer funds or wallet liabilities according to tenant policy. They are not silently treated as revenue.

Allowed actions include:

- apply to another obligation with permission;
- retain as wallet credit with consent/policy;
- refund;
- remain unapplied pending reconciliation.

---

## 15. Deposits, milestones and bonds

### Deposits

A deposit is linked to a quote, booking, order, job, rental, membership or other source commitment.

It records:

- required amount or percentage evidence;
- due date;
- refundable/non-refundable terms;
- target obligation or future allocation policy;
- cancellation and forfeiture rules;
- source version.

Receiving a deposit posts value and creates an allocation or liability according to configured policy. It does not by itself prove fulfilment.

### Milestones

A milestone schedule includes:

- ordered milestones;
- amount, percentage or formula evidence;
- trigger and approval requirements;
- payer and beneficiary;
- due policy;
- source contract/version;
- dispute rules;
- completion and invoicing status.

Operational completion arrives from the authoritative source. Titan Pay validates approval before issuing or satisfying the milestone obligation.

### Bonds

Bond lifecycle:

- required;
- awaiting payment;
- received/held;
- release requested;
- release approved;
- partially released;
- fully released;
- application proposed;
- application disputed;
- application approved;
- partially applied;
- fully applied;
- closed.

Rules:

- bond value is held in bond-liability accounts;
- a bond cannot be applied without an authorised obligation and evidence;
- bond release and application amounts cannot exceed held balance;
- release/application concurrency is protected;
- disputes pause prohibited actions;
- customer-safe condition, damage and approval evidence is linked but sensitive internal notes remain restricted.

---

## 16. Splits, beneficiary allocations and settlements

Titan Pay supports two distinct concepts.

### Split tender

Several payment attempts satisfy one intent or invoice. Each rail posts independently and allocations show the combined result.

### Revenue or beneficiary allocation

A collected payment may create amounts payable to:

- contractor;
- property owner;
- platform business unit;
- tax authority reference;
- franchise/network party;
- other approved beneficiary.

Allocation rules are versioned and sourced from the authoritative agreement. They do not alter the customer invoice total.

Until paid out or exported, beneficiary amounts are represented as liabilities. Payouts use separate governed commands, provider attempts and reconciliation.

No agent may invent or alter split percentages from conversation context.

---

## 17. Payment plans, subscriptions and promises to pay

### Payment plans

A plan records:

- covered obligations;
- total scheduled amount;
- instalments, amounts and due dates;
- interest/fees only where legally and contractually approved;
- payment method/mandate references;
- grace and default policy;
- approvals and customer consent;
- active version.

Plan projection states:

- `draft`;
- `offered`;
- `accepted`;
- `active`;
- `past_due`;
- `defaulted`;
- `completed`;
- `cancelled`;
- `restructured`.

Changing a plan creates a new version or restructuring event; it does not rewrite paid instalments.

### Subscriptions

Subscription operations may remain in a commerce or membership authority. Titan Pay owns the resulting financial obligations, attempts, mandate references and receipts.

### Promise to pay

A promise to pay is a receivables commitment, not a payment. It records promised amount/date and affects follow-up scheduling but does not reduce the ledger amount due.

---

## 18. Reconciliation contract

Reconciliation proves internal attempts, ledger postings and allocations agree with external evidence.

### Reconciliation inputs

- provider transaction API;
- settlement reports;
- bank statements/feeds;
- PayID references;
- cash till or agent handover reports;
- refund reports;
- dispute/chargeback reports;
- crypto invoice and confirmation evidence;
- manually reviewed evidence with actor and approval.

### Matching dimensions

- tenant/provider account;
- provider transaction ID;
- amount and currency;
- payment reference;
- customer/business reference;
- occurrence and settlement windows;
- intent/attempt ID;
- settlement batch;
- fee amount;
- refund/chargeback reference.

### Reconciliation outcomes

- matched;
- matched with fee;
- matched with timing difference;
- unmatched external funds;
- internal success missing external evidence;
- external success missing internal posting;
- amount mismatch;
- currency mismatch;
- duplicate evidence;
- refund mismatch;
- chargeback mismatch;
- manual review required;
- resolved with authorised adjustment.

### Adjustment rules

Reconciliation never edits prior events or entries. Resolution uses:

- missing canonical event acceptance;
- compensating/reversal posting;
- suspense posting;
- allocation correction;
- provider evidence linkage;
- authorised write-off or adjustment.

Every case records owner, status, age, evidence, decision and correlation IDs.

---

## 19. Outbox, inbox and side effects

### Transactional outbox

When a Titan Pay decision must notify another domain, the outbox message is committed with the financial transaction.

Examples:

- payment succeeded;
- invoice paid;
- refund completed;
- bond received/released/applied;
- milestone paid;
- plan defaulted;
- reconciliation exception opened.

Outbox delivery is at-least-once. Consumers must be idempotent.

### Idempotent inbox

WorkCore, commerce, lifecycle and notification consumers store processed event IDs and consumer versions. Re-delivery cannot duplicate jobs, notifications or accounting exports.

### Side-effect boundary

Replay and projection rebuilds never:

- call payment providers;
- send emails/SMS/push notifications;
- create WorkCore records;
- execute payouts;
- change external accounting systems.

Only explicit side-effect workers process outbox messages.

---

## 20. Customer-safe projections and API contract

Titan Hub and Chatbot consume `/api/titan-hub/v1` facade projections. They do not query event or ledger tables directly.

### Customer projections

- invoice list and detail;
- amount due and due date;
- payment history;
- allowed payment methods;
- payment intent/action status;
- wallet balance buckets and expiry summaries;
- receipts;
- refund status;
- deposits and milestones;
- bond held/released/applied status;
- payment plan schedule;
- customer-safe disputes and support actions;
- unified financial Activity entries.

### Internal/operator projections

- ledger account balances;
- posting batches and entries;
- provider attempts and evidence;
- settlement and fee reports;
- receivables ageing;
- unapplied funds;
- suspense balances;
- reconciliation cases;
- projection/replay health;
- aggregate and event audit explorer subject to permission.

### Allowed-action contract

The backend returns permitted actions such as:

- `pay`;
- `retry_payment`;
- `change_payment_method`;
- `report_bank_transfer`;
- `request_refund`;
- `dispute_invoice`;
- `accept_payment_plan`;
- `make_plan_payment`;
- `view_receipt`;
- `request_bond_release`;
- `contact_business`.

Flutter and Chatbot render returned actions. They do not derive authority from state labels.

### Command endpoints

Representative stable facade commands:

```text
POST /api/titan-hub/v1/payment-intents
POST /api/titan-hub/v1/payment-intents/{id}/attempts
POST /api/titan-hub/v1/payment-attempts/{id}/confirm-action
POST /api/titan-hub/v1/invoices/{id}/disputes
POST /api/titan-hub/v1/refunds
POST /api/titan-hub/v1/wallet/reservations
POST /api/titan-hub/v1/bonds/{id}/release-requests
POST /api/titan-hub/v1/payment-plans/{id}/accept
```

Actual route implementation remains versioned and thin. Commands call Titan Pay application services, not provider or donor controllers directly.

---

## 21. Flutter and Chatbot authority

### Flutter

Flutter may:

- display projections;
- collect payment-method choice;
- initiate a governed command;
- open hosted checkout;
- perform required device confirmation;
- poll or subscribe to projection status;
- display signed receipts and allowed next actions.

Flutter may not:

- calculate final invoice balance;
- set attempt success;
- credit a wallet;
- release a bond;
- approve a refund;
- apply a payment to an invoice;
- trust a redirect alone as payment confirmation.

### Chatbot and agents

Chatbot tools may:

- explain invoice and payment state;
- show allowed payment methods;
- create a draft payment intent;
- collect bank-transfer evidence;
- prepare a refund request;
- explain a payment plan;
- request human handoff;
- surface reconciliation/support status safe for the customer.

High-risk tools require deterministic permission and approval. Agents cannot:

- move wallet funds directly;
- fabricate provider confirmation;
- change obligation amounts;
- issue unapproved credits;
- release/apply bonds;
- execute refunds beyond authority;
- alter beneficiary splits;
- approve their own proposals.

Flutter screens and Chatbot tools call the same application commands and receive the same result contracts.

---

## 22. WorkCore, commerce and accounting integration

### Quote to payment

```text
WorkCore or commerce creates authoritative quote/order/booking context
-> customer accepts through governed application command
-> operational authority creates job/booking/order commitment
-> Titan Pay creates deposit or invoice obligation
-> payment intent and attempts collect value
-> ledger posting commits
-> payment allocation satisfies obligation
-> outbox publishes payment/invoice state
-> WorkCore and commerce update projections through idempotent consumers
-> accounting export references the same ledger batches and source IDs
```

### Job completion and milestones

Operational completion is evidence, not a ledger posting by itself. Titan Pay validates the configured contract and approval before creating or issuing the financial obligation.

### Inventory and fulfilment

Payment state may authorise fulfilment through an outbox event, but Titan Pay does not mutate stock or job status directly.

### External accounting

Titan Pay exports:

- posting-batch references;
- mapped account codes;
- invoice and payment references;
- tax evidence;
- settlement fees;
- refunds, credits and write-offs;
- beneficiary liabilities/payouts;
- reconciliation status.

External-accounting acknowledgements are retained. Export failure does not rewrite Titan Pay history.

---

## 23. Migration from QRPay and existing finance tables

QRPay and existing MagicAI/WorkCore tables are donor sources, not automatically canonical.

### Inventory before migration

Classify each source table/field as:

- identity/reference;
- provider configuration;
- historical transaction evidence;
- financial obligation;
- wallet/balance snapshot;
- workflow state;
- generated/cache data;
- secret or signing material;
- obsolete/unsafe authority.

### Migration rules

- never import `.env`, OAuth private keys, signing keys, keystores or live secrets;
- preserve donor IDs in mapping tables;
- import immutable transaction evidence before creating opening balances;
- reconcile donor transaction totals to imported ledger batches;
- convert mutable wallet balances into opening-balance postings with evidence and signed migration reports;
- do not replay imported historical events into external side effects;
- flag unbalanced or ambiguous donor records for suspense/manual review;
- retain migration run ID, source hash, counts, totals, exceptions and rollback boundary;
- use shadow reads and reconciliation before authority cutover;
- freeze donor writes or use dual-write only under an explicit temporary migration plan;
- remove donor write authority only after parity and rollback checks pass.

### Duplicate migration warning

The repository currently contains more than one migration filename for `event_envelope_events`. Implementation of Issue #259 must reconcile duplicate migration ownership before introducing Titan Pay event-store tables. Do not ship colliding table creation migrations.

---

## 24. Security and governance

- provider credentials remain encrypted and tenant-scoped;
- webhook verification uses raw bodies, key versions, timestamps and replay controls;
- hosted payment flows use signed state and allowlisted return targets;
- raw card data is never stored;
- logs redact tokens, account numbers, private keys, full provider payloads and sensitive personal data;
- financial exports use least privilege and signed/audited delivery where supported;
- permissions distinguish view, initiate, approve, capture, refund, credit, write off, release bond, apply bond, reconcile and administer provider access;
- high-risk actions use step-up authentication or explicit approval according to policy;
- tenant and business scope is resolved server-side;
- rate limits apply to intent creation, attempts, webhook processing, refunds, wallet commands and evidence uploads;
- immutable audit entries link command, decision, event, posting, actor and approval;
- retention and legal-hold policies distinguish operational projections from immutable financial evidence;
- customer-facing descriptions exclude sensitive internal accounting and fraud notes.

---

## 25. Observability and operations

Required metrics and alerts include:

- event append failures and concurrency conflicts;
- unbalanced posting attempts;
- projection lag and failed projector events;
- outbox age and delivery failures;
- provider webhook verification failures and replay attempts;
- duplicate provider events;
- attempts stuck in processing;
- settlement delays and mismatches;
- suspense balances and ageing;
- unapplied funds ageing;
- wallet projection divergence;
- refund and chargeback failure rates;
- reconciliation backlog;
- event replay progress and failures;
- tenant-specific anomaly thresholds without exposing cross-tenant data.

Every alert links to correlation IDs and safe operational context.

---

## 26. Minimum test matrix

### Event store

- sequential append;
- optimistic-concurrency rejection;
- idempotent command replay;
- same key/different payload rejection;
- snapshot load and invalidation;
- event upcasting;
- deterministic aggregate replay;
- tenant isolation.

### Ledger invariants

- balanced posting accepted;
- unbalanced posting rejected;
- cross-currency batch rejected unless explicit paired exchange contract is used;
- immutable posted entries;
- reversal correctness;
- duplicate posting fingerprint rejection;
- closed/restricted account rules;
- concurrent wallet spend cannot overdraw;
- projection rebuild equals ledger-derived balance.

### Payment rails

- QR signature, expiry, tenant, amount and replay checks;
- cash confirmation authority;
- PayID/bank reported-versus-confirmed states;
- hosted card/PayPal redirect does not self-confirm;
- wallet reserve/capture/release;
- external BNPL settlement;
- optional crypto confirmation and under/overpayment policy.

### Provider webhooks

- valid signature;
- invalid signature;
- stale timestamp;
- duplicate event;
- duplicate transaction transition;
- out-of-order events;
- provider retry after internal success;
- unknown account/tenant fail closed;
- chargeback after settlement;
- refund webhook after timeout.

### Obligations and receivables

- invoice issue and adjustment;
- partial payment;
- split tender;
- overpayment/unapplied funds;
- dispute and resolution;
- payment plan activation, failure, default and completion;
- promise to pay does not reduce balance;
- void/write-off permissions;
- lifecycle follow-up suppression after state change.

### Deposits, milestones and bonds

- deposit allocation;
- milestone approval concurrency;
- partial milestone payment;
- bond receipt as liability;
- release/application race prevention;
- partial release and application;
- dispute pause;
- bond amount cannot exceed held balance.

### Refunds, credits and reconciliation

- partial and full refund limits;
- duplicate refund callback;
- credit restrictions and expiry;
- settlement with provider fee;
- unmatched external funds;
- internal success missing external evidence;
- authorised suspense resolution;
- reconciliation adjustment uses new postings.

### Surface and authority tests

- Flutter cannot submit server-owned totals/status;
- Chatbot cannot bypass approval;
- customer cannot self-confirm cash or bank receipt;
- WorkCore and commerce cannot directly mutate Titan Pay balances;
- disabled rail is absent from manifest and tools;
- every command and event is correlated and audited.

---

## 27. Implementation sequence

### Phase 1 — foundations

- reconcile existing event-envelope migrations and ownership;
- implement tenant-scoped event store and optimistic concurrency;
- implement account, posting batch and immutable ledger-entry model;
- add invariant tests and projection framework;
- add transactional outbox and idempotent inbox.

### Phase 2 — obligations and core payments

- implement obligations/invoices, allocations and receipts;
- implement payment intents and attempts;
- implement wallet reserve/capture/release;
- implement cash, bank/PayID, hosted PayPal/card and QR adapters;
- implement provider webhook inbox and reconciliation baseline.

### Phase 3 — advanced financial workflows

- refunds, credits, overpayments and disputes;
- deposits and milestones;
- bonds;
- split tender and beneficiary allocations;
- payment plans and external BNPL;
- optional Cryptomus adapter.

### Phase 4 — surfaces and integrations

- Titan Hub Flutter projections and commands;
- Chatbot tools and approval controls;
- WorkCore/commerce outbox consumers;
- accounting export adapters;
- lifecycle follow-up and notification integration;
- audit explorer and reconciliation workspace.

### Phase 5 — migration and release

- QRPay donor compatibility adapter;
- data mapping and opening-balance migration;
- shadow reconciliation;
- cutover/rollback rehearsals;
- full security, concurrency, replay, tenant and provider-fake test suites;
- monitored pilot rollout.

---

## 28. Explicit non-goals

Titan Pay does not:

- replace MagicAI tenant identity;
- replace WorkCore operations or general accounting;
- own catalogue, inventory or booking availability;
- store raw card details;
- treat QR codes as permanent authority;
- permit direct wallet balance updates;
- trust client/provider status without canonical transition processing;
- erase financial history;
- use AI to approve money movement;
- impose an undeclared Titan percentage transaction fee;
- convert QRPay wholesale without bounded adapters and migration evidence.

---

## 29. Completion checklist for Issue #259

Issue #259 is not complete until implementation proves:

- immutable tenant-scoped event streams;
- optimistic concurrency and idempotency;
- balanced immutable ledger postings;
- rebuildable wallet, invoice and activity projections;
- QR, cash, bank/PayID, PayPal/card and wallet flows end to end;
- provider webhook verification, replay protection and ordering;
- refund, credit and reconciliation correctness;
- deposit, milestone, bond and split invariants;
- WorkCore and commerce integration without duplicate authority;
- Flutter and Chatbot governed-command parity;
- donor migration and rollback evidence;
- automated tenant-isolation and financial-invariant coverage;
- operational monitoring and reconciliation runbooks.

This contract is the financial model authority for subsequent Titan Pay implementation work. Provider adapters, donor code and UI flows must conform to it rather than redefine financial truth.
