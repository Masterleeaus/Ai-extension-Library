---
title: "MagicAI 11 Sales, Estimates, Invoices and Payments"
scan_number: 11
date: "2026-08-03"
magicai_version: "11.00"
status: "Completed with paid-extension limitation"
blocked_scan:
  number: 10
  reason: "The separate paid MagicAI CRM extension package was not present in current uploads or File Library."
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/11-magicai-sales-estimates-invoices-and-payments.md"
---

# Scan 11 — MagicAI 11 Sales, Estimates, Invoices and Payments

## Executive conclusion

MagicAI 11 core proves that the paid CRM extension intends to provide four customer-sales areas:

```text
Invoices
Payments
Proposals
Estimates
```

The core also supplies:

- One Sales menu root
- Four child route contracts
- One broad Sales plan entitlement
- One CRM provider dependency
- Shared navigation, plan and theme hooks

It does **not** contain the paid Sales implementation.

No paid-Sales routes, models, migrations, controllers, document renderers, payment services, policies, webhooks or tests are present in the supplied MagicAI package.

WorkCore, by contrast, contains a large operational finance domain:

| WorkCore Finance measure | Result |
|---|---:|
| PHP files | 313 |
| PHP syntax failures | 0 |
| Finance permissions | 67 |
| Finance tables | 78 |
| Registered built-in actions | 7 |
| Public finance API routes | 1 health route |
| Public finance web routes | 1 health route |
| Public finance webhooks | 1 PayPal webhook |
| Finance-specific automated tests | 0 |
| Production-ready flag | `false` |

WorkCore's financial model is substantially deeper than the MagicAI host contract. It includes estimates, versioned quotes, quote acceptance evidence, job-derived invoices, invoice lifecycle and immutable snapshots, payments and allocations, payment evidence, payment matching, receipts, refund and credit-note tables, recurring-invoice rules, collections, expenses, suppliers, payables, ledger, GST, reconciliation, forecasting, profitability, e-invoicing, budgets, procurement, point of sale and sales orders.

However, much of that depth is currently **schema and domain foundation rather than a complete reachable product**.

## Final authority recommendation

Until the paid CRM Sales extension is supplied and proven:

> **WorkCore Finance should remain the provisional operational financial authority.**  
> **MagicAI Sales should be treated as an optional future presentation and CRM workflow adapter.**

This means:

- MagicAI CRM may own pre-sale proposals and estimates when bridge mode is enabled.
- Accepted sales terms must convert through a governed WorkCore action.
- WorkCore should own job-derived invoice truth, allocations, payment evidence, GST, reconciliation and operational ledger consequences.
- MagicAI Sales may display projections of WorkCore invoices and payments.
- No independent duplicate invoice or payment authority may be enabled.

WorkCore Finance is not ready to become production authority until the defects documented in this scan are corrected and tested.

---

# 1. Scan status and limitation

The planned preceding report was:

```text
10-magicai-crm-extension-deep-scan.md
```

That scan requires the separately distributed paid CRM extension.

Searches across current-conversation uploads, File Library and the MagicAI server ZIP did not find the paid CRM source package.

Scan 10 therefore remains blocked rather than inferred.

This Scan 11 uses only:

- Confirmed MagicAI host contracts
- Confirmed MagicAI platform-order invoice code
- Full supplied WorkCore Commercial and Finance source

Any statement about the internal paid CRM Sales implementation is explicitly marked unverified.

---

# 2. MagicAI Sales host contract

MagicAI core registers the paid CRM provider:

```php
'crm' => CrmServiceProvider::class,
```

The Sales navigation is considered available when the CRM provider class exists and is loaded and `crm_enabled` is true.

There is no separate core Sales provider.

Therefore the intended paid Sales implementation is part of, or supplied by, the CRM extension.

---

# 3. Confirmed MagicAI Sales navigation

| Order | Menu key | Label | Named route |
|---:|---|---|---|
| 5 | `ext_sales_dropdown` | Sales | `dashboard.user.sales.invoices.index` |
| 1 | `crm_sales_invoices` | Invoices | `dashboard.user.sales.invoices.index` |
| 2 | `crm_sales_payments` | Payments | `dashboard.user.sales.payments.index` |
| 3 | `crm_sales_proposals` | Proposals | `dashboard.user.sales.proposals.index` |
| 4 | `crm_sales_estimates` | Estimates | `dashboard.user.sales.estimates.index` |

Active route patterns:

```text
dashboard.user.sales.*
dashboard.user.sales.invoices.*
dashboard.user.sales.payments.*
dashboard.user.sales.proposals.*
dashboard.user.sales.estimates.*
```

The root is shown when:

```php
MarketplaceHelper::isRegistered('crm')
&&
(bool) setting('crm_enabled', '1')
```

The four children check the CRM provider but do not each repeat the global setting. They are normally hidden with the parent, but menu visibility is not server-side access control.

---

# 4. MagicAI Sales entitlement

MagicAI contributes one plan key:

```text
ext_sales_dropdown
```

The tooltip describes invoices, payments, proposals and estimates.

The route middleware maps every route under:

```text
dashboard.user.sales.*
```

to the single entitlement:

```text
ext_sales_dropdown
```

Core MagicAI does not define separate entitlements for estimates, proposals, invoices, payments, online payment, recurring invoices or advanced finance.

Recommended future entitlement split:

```text
ext_sales_estimates
ext_sales_proposals
ext_sales_invoices
ext_sales_payments
ext_sales_recurring
ext_sales_customer_portal
```

WorkCore company permissions remain separate from plan entitlements.

---

# 5. No paid Sales routes or data model in core

The following route names are referenced by the menu but do not exist in MagicAI core:

```text
dashboard.user.sales.invoices.index
dashboard.user.sales.payments.index
dashboard.user.sales.proposals.index
dashboard.user.sales.estimates.index
```

The paid extension must register them.

No core models or migrations were found for CRM Sales concepts such as:

```text
CRM estimate
CRM proposal
CRM customer invoice
CRM customer payment
CRM payment allocation
CRM credit note
CRM recurring invoice
```

The core therefore does not prove route middleware, customer access, public acceptance links, numbering, tax calculation, document delivery, tenant isolation or payment callbacks.

---

# 6. Do not confuse SaaS order invoices with CRM customer invoices

MagicAI core has working user-order invoice routes:

```text
dashboard.user.orders.index
dashboard.user.orders.invoice
dashboard.user.orders.invoices.export
```

They render or export invoices for MagicAI subscription purchases, token-pack purchases and platform orders.

These records use `user_orders`, plans, platform gateway transactions and platform invoice-address settings.

They represent:

```text
Titan Zero → subscriber
```

The missing CRM Sales invoices should represent:

```text
subscriber's business → subscriber's customer
```

The two invoice systems must remain separate.

---

# 7. Unverified paid Sales capabilities

The supplied source cannot confirm whether paid CRM Sales supports:

- Versioned estimates
- Proposal acceptance
- Digital signatures
- Deposits
- Milestone billing
- Partial payments
- Payment allocation
- Overpayments
- Refunds
- Credit notes
- Recurring invoices
- Tax jurisdictions
- Australian GST
- Discounts by line
- Variations
- Job-cost linkage
- Customer statements
- Collections
- Ledger posting
- Bank reconciliation
- Xero mapping
- Idempotent webhooks
- Multi-company tenancy
- Audit history
- Offline payments
- Customer portal

No final replacement or deletion decision should be made until the paid extension is scanned.

---

# 8. WorkCore Finance inventory

The supplied WorkCore Finance/Titan Money module contains 313 PHP files.

Major distribution:

| Area | PHP files |
|---|---:|
| Infrastructure | 83 |
| Domain | 75 |
| Contracts | 55 |
| Application | 31 |
| Automation | 15 |
| Integrations | 14 |
| Tenancy | 5 |
| HTTP | 5 |
| Authorization | 4 |
| AI | 3 |
| Audit | 2 |
| Console | 2 |

All 313 files passed PHP syntax validation.

The module declares:

```text
version: 0.8.1
schema version: 1.6
production_ready: false
```

That explicit flag must be respected.

---

# 9. WorkCore Finance schema

Eight finance migration files create 78 tables.

## Tax registration

```text
tm_tax_registrations
```

## Commercial documents

```text
tm_estimates
tm_estimate_versions
tm_estimate_lines
tm_quotes
tm_quote_versions
tm_quote_lines
tm_quote_acceptances
tm_invoices
tm_invoice_lines
tm_invoice_events
tm_recurring_invoice_rules
tm_customer_statements
```

## Payments, expenses and payables

```text
tm_payment_requests
tm_payments
tm_payment_claims
tm_payment_evidence
tm_payment_allocations
tm_refunds
tm_credit_notes
tm_suppliers
tm_supplier_bills
tm_supplier_bill_lines
tm_expenses
tm_expense_attachments
```

## Ledger, tax and accounting periods

```text
tm_accounts
tm_tax_codes
tm_tax_rates
tm_accounting_periods
tm_journal_entries
tm_journal_lines
```

## Reconciliation, forecasting and export

```text
tm_bank_accounts
tm_bank_transactions
tm_reconciliations
tm_reconciliation_lines
tm_forecast_scenarios
tm_job_profitability_snapshots
tm_accountant_exports
```

## Automation

```text
tm_automation_policies
tm_collection_sequences
tm_collection_sequence_steps
tm_scheduled_actions
tm_action_attempts
tm_financial_exceptions
tm_payment_instruction_deliveries
tm_qr_artifacts
tm_payment_match_candidates
tm_payment_promises
tm_outbox_events
```

## Collections runtime

```text
tm_receipts
tm_collection_messages
tm_collection_controls
tm_payment_plans
```

## Additional money domains

```text
tm_budgets
tm_budget_lines
tm_budget_approval_thresholds
tm_budget_actuals
tm_budget_variances
tm_budget_forecast_scenarios
tm_expense_claims
tm_expense_claim_receipts
tm_reimbursement_batches
tm_reimbursements
tm_purchase_orders
tm_purchase_order_lines
tm_purchase_receipts
tm_purchase_receipt_lines
tm_supplier_credits
tm_einvoice_profiles
tm_einvoice_transmissions
tm_einvoice_events
tm_workforce_loans
tm_workforce_loan_repayments
tm_pos_sales
tm_pos_sale_lines
tm_pos_sale_payments
tm_sales_orders
tm_sales_order_lines
```

---

# 10. WorkCore monetary model

WorkCore uses integer minor units, currency codes, scaled quantities, basis-point tax rates and immutable money value objects.

Example invoice-line data:

```text
quantity_scaled
quantity_scale
unit_price_minor
discount_minor
tax_rate_basis_points
tax_minor
line_total_minor
currency_code
```

This supports deterministic calculation and Australian GST and is stronger than MagicAI platform billing tables that still contain floating-point monetary columns in places.

---

# 11. Estimates

The WorkCore schema supports estimate numbers, versions, customer/site/job references, approval, conversion to quote, optimistic lock version, scope, assumptions, line items, tax, discounts and snapshot hashes.

Permissions exist:

```text
money.estimates.draft
money.estimates.manage
```

No estimate action class, controller, route or user interface was found.

The estimates capability is currently primarily schema and permission foundation.

---

# 12. Quotes and proposals

WorkCore has a quote state enum, lifecycle, versioned schema, line items, customer/seller snapshots, validity date, acceptance evidence and conversion fields.

Lifecycle:

```text
draft
→ ready
→ sent
→ viewed
→ revised
→ accepted / declined / expired
→ converted
```

No WorkCore class or table uses `Proposal` in its name.

The practical mapping is:

```text
MagicAI Proposal
→ WorkCore Quote
```

unless the paid extension proves a meaningful distinction.

No quote create, send, accept or conversion action was found in the finance action registry. No quote controller, public acceptance route or document renderer was found.

---

# 13. Invoices

WorkCore's invoice schema supports company, customer, site, job, source identity, source quote, numbering, currency, dates, totals, allocation, credits, balance, snapshots, issue state, optimistic locking, events, recurring rules and customer statements.

Lifecycle:

```text
draft
→ approved
→ issued
→ delivered
→ viewed
→ partially paid
→ paid
→ overdue
→ credited / voided
```

Invalid transitions are rejected by the domain lifecycle.

---

# 14. Completed-job invoice action

WorkCore implements:

```text
money.create_invoice_from_job
```

The action can:

1. Verify finance permissions.
2. Require company and actor context.
3. Claim an idempotency key.
4. Detect an existing invoice by job source key.
5. Load a completed WorkCore job.
6. Load billable job lines.
7. Check invoice eligibility.
8. Require completed work, billable lines, customer and evidence.
9. Assemble GST-aware invoice lines.
10. Create an invoice draft.
11. Append invoice events.
12. Append finance outbox events.
13. Issue the invoice when policy allows.
14. Post a ledger entry.
15. Create payment instructions and QR details.
16. Schedule collection follow-up.
17. Return a generative UI envelope.

This is a strong operational flow.

WorkCore can create an invoice from actual delivered work and evidence. A CRM Sales system normally cannot determine actual labour, actual materials, approved variations, completion evidence, field exceptions or job profitability.

---

# 15. Invoice eligibility and snapshots

Default policy requires:

```text
completed job
billable job
customer
at least one billable line
complete evidence
no existing job invoice
```

WorkCore stores customer snapshots, seller snapshots, draft snapshots, issued snapshot hashes and line-source references.

Issued financial documents should preserve the facts used when issued even if names, addresses, tax settings or catalogue prices later change.

---

# 16. Payments

WorkCore supports:

- Payment requests
- Payment methods
- Payment evidence
- Payment claims
- Payments
- Allocations
- Receivable balances
- Refund and credit-note tables
- Receipts
- Matching candidates
- Payment promises and plans
- Collection controls
- Ledger posting
- Outbox events
- Financial exceptions

Payment methods currently registered:

```text
cash
PayID
bank transfer
PayPal card
```

This matches the intended ZeroPay ordering.

---

# 17. Payment evidence and matching

Registered action:

```text
money.ingest_payment_evidence
```

The action claims idempotency, deduplicates by payload hash, stores evidence, finds open receivables, ranks candidates, stores matches, emits proposals and can auto-confirm only when confidence and policy allow.

Default automatic confidence threshold:

```text
95
```

It requires payment-confirm and payment-allocate permissions before automatic allocation.

This is more sophisticated than a simple CRM payment-status field.

---

# 18. Payment confirmation

`PaymentConfirmationService` runs transactionally and:

- Prevents duplicate payment creation by evidence
- Verifies currency
- Allocates up to the receivable balance
- Creates payment and allocation rows
- Reduces invoice balance
- Posts the ledger
- Cancels collection actions when paid
- Issues a receipt
- Emits payment, allocation, receipt and collection events
- Creates an exception for unallocated overpayment

---

# 19. GST, ledger, reconciliation and collections

WorkCore includes company tax registrations, tax codes, effective rates, GST calculators, accounting periods, balanced journals, invoice/payment posting templates, Australian bank CSV import, reconciliation sessions, strict close rules, forecasting, profitability and an Australian collection sequence.

Default collection stages include three days before due, due date, 3, 7, 14, 21 and 30 days overdue.

Suppressions include paid, disputed, payment plan, payment claim, paused, voided and credited states.

This depth is not proven in the missing paid MagicAI Sales extension.

---

# 20. Public runtime surface

Despite the deep schema, the routed Finance surface is small.

## Web

```text
GET /dashboard/user/titan-money/health
```

## API

```text
GET /api/titan-money/health
```

## Webhook

```text
POST /api/titan-money/webhooks/paypal/{connectionKey}
```

No routed endpoints were found for estimate CRUD, quote CRUD, proposal CRUD, quote acceptance, invoice CRUD, invoice issue, payment listing, payment confirmation, refund, credit note, recurring invoice, customer statement, collections UI, ledger, GST report or reconciliation UI.

The Finance module routes are disabled by default:

```text
WORKCORE_FINANCE_ROUTES_ENABLED=false
```

---

# 21. User interface state

The Finance module contains one minimal Blade view:

```html
<div data-workcore-money>
    Titan Money
</div>
```

There is no complete native finance workspace in the supplied module.

Integration needs manager views, customer documents, public acceptance, PDFs, payment request pages, reconciliation workspace, finance exception queue and mobile payment-evidence capture.

MagicAI Sales UI may become a useful presentation donor after the paid extension is scanned.

---

# 22. Registered action catalogue

WorkCore registers seven actions:

```text
money.health
money.create_invoice_from_job
money.ingest_payment_evidence
money.confirm_payment_match
money.collection.execute_followup
money.bank_statement.import
money.reconciliation.close
```

Only two AI-facing finance tools were found:

```text
money.create_invoice_from_job
money.ingest_payment_evidence
```

---

# 23. Critical WorkCore action-key mismatch

The action registry registers:

```text
money.create_invoice_from_job
```

The action class uses this internal constant for idempotency and audit:

```text
money.invoice.create_from_job
```

Consequences:

- Registry catalogue and audit records use different keys.
- Idempotency records use a different key from the externally invoked action.
- Policy and approval lookup can miss the action.
- Scheduled action matching can fail.
- Diagnostics can show two apparent actions.

One canonical key must be used everywhere.

---

# 24. Incomplete action-input factory

The generic `FinancialActionInvoker` resolves actions through the action registry and asks `TitanMoneyActionInputFactory` to build their input.

The input factory supports only:

```text
money.collection.execute_followup
money.reconciliation.close
```

It does not support the other registered actions:

```text
money.health
money.create_invoice_from_job
money.ingest_payment_evidence
money.confirm_payment_match
money.bank_statement.import
```

Therefore the generic scheduled/headless action invoker cannot execute five of the seven registered actions.

Direct AI tools bypass the input factory by instantiating DTOs themselves. The action contract is not yet unified.

---

# 25. Approval metadata is not centrally enforced

Each `ActionDefinition` declares risk level and approval mode.

`LaravelFinancialActionInvoker` does not inspect either value. It loads the action, builds input and calls `execute()`.

Confirmed gaps:

- `CloseReconciliation` is registered as requiring explicit approval but does not check `context.approvalId`.
- `ConfirmPaymentMatch` is registered as requiring user confirmation but does not verify confirmation or approval.
- AI tools invoke actions directly and bypass registry approval metadata.

Every finance action must pass through the canonical WorkCore governed-action and approval verifier.

Approval must bind company, actor, action key, payload hash, risk, expiry and one-time consumption.

---

# 26. Payment confirmation consistency gap

`PaymentConfirmationService` commits payment, allocation, ledger, receipt and outbox work inside its transaction.

`ConfirmPaymentMatch` marks evidence verified and records audit after that transaction.

If evidence marking or audit fails:

- Payment may already be committed.
- The action returns failure.
- The caller may retry.
- Evidence and audit state can remain inconsistent.

Evidence status and action audit should be included in the same durable boundary, or completion should use a resumable saga.

---

# 27. Publish-path defect

`WorkCoreFinanceServiceProvider::registerPublishables()` uses:

```php
dirname(__DIR__) . '/config/...'
dirname(__DIR__) . '/resources/schemas'
```

The actual files live under:

```text
Finance/config/
Finance/resources/schemas/
```

The configured publish sources do not exist.

Use:

```php
__DIR__ . '/config/...'
__DIR__ . '/resources/schemas'
```

---

# 28. Authentication mismatch

Finance API routes use:

```text
auth:sanctum
```

MagicAI uses Passport:

```text
auth:api
```

This must become host-configurable before integration.

---

# 29. Dependency portability

Finance declares Bacon QR Code as a required dependency.

MagicAI includes `bacon/bacon-qr-code`; the standalone WorkCore `composer.json` does not.

Therefore QR rendering can work inside MagicAI, but standalone WorkCore requires an explicit dependency and the extension manifest must declare it.

---

# 30. Silent signal loss

`WorkCoreSignalPublisher` catches and discards all outbox publication failures after Finance commits.

Avoiding a second failure after commit is sensible, but complete silence creates unobservable lost integration signals.

Use a durable local outbox, retriable relay, failure metric, dead-letter record and alert.

---

# 31. Schema-rich but unwired capabilities

The following exist in schema or domain definitions but have no complete registered action and routed workflow in the scanned module:

```text
estimates
quote creation
quote sending
quote acceptance
quote conversion
refund execution
credit-note execution
recurring invoice generation
customer statements
supplier bill workflow
expense workflow
budget workflow
purchase-order workflow
e-invoice transmission
workforce-loan workflow
point-of-sale workflow
sales-order workflow
accountant export
```

They should be classified as:

```text
foundation present
workflow incomplete
```

not production-complete features.

---

# 32. Test coverage

The complete WorkCore package contains 15 test files, but no finance-specific test was found.

There are no direct tests proving quote transitions, invoice transitions, GST calculations, duplicate invoice prevention, payment allocation, overpayment handling, refunds, credit notes, webhook verification, bank import, reconciliation close, approval enforcement, outbox reliability, company isolation or MagicAI integration.

The module's `production_ready = false` setting is accurate.

---

# 33. Provisional authority matrix

| Capability | MagicAI CRM Sales | WorkCore | Recommended authority |
|---|---|---|---|
| Sales estimate presentation | Intended, unverified | Schema foundation | MagicAI UI or WorkCore UI; one authority |
| Proposal presentation | Intended, unverified | Map to Quote | MagicAI front-office if proven |
| Customer acceptance | Unverified | Quote acceptance schema | WorkCore evidence store preferred |
| Accepted commercial snapshot | Unverified | Strong snapshots | WorkCore |
| Job billable quantities | Not evidenced | Confirmed | WorkCore |
| Variations | Not evidenced | Operational domain | WorkCore |
| Invoice-ready decision | Not evidenced | Confirmed eligibility policy | WorkCore |
| Invoice document UI | Intended | Not completed | MagicAI UI adapter if proven |
| Invoice financial truth | Unverified | Deep model | WorkCore after hardening |
| Payment status display | Intended | Confirmed | Projection to MagicAI |
| Payment evidence | Not evidenced | Confirmed | WorkCore |
| Payment allocation | Not evidenced | Confirmed | WorkCore |
| Refunds and credit notes | Unverified | Schema only | WorkCore after workflow completion |
| GST | Unverified | Confirmed foundation | WorkCore |
| Ledger | Not evidenced | Confirmed foundation | WorkCore |
| Reconciliation | Not evidenced | Confirmed | WorkCore |
| Collections | Unverified | Confirmed foundation | WorkCore |
| Job profitability | Not evidenced | Confirmed | WorkCore |

---

# 34. Recommended estimate/proposal flow

```text
CRM deal
→ CRM estimate or proposal
→ customer reviews
→ customer accepts
→ signed/accepted snapshot event
→ WorkCore bridge validates mapping
→ WorkCore customer and service site resolved
→ WorkCore quote/contract snapshot stored
→ governed work-order creation
→ operations and field delivery
```

The accepted document must include source system, external ID, version, payload hash, customer, scope, line items, tax, total, acceptance evidence and correlation ID.

---

# 35. Recommended invoice flow

```text
WorkCore job completed
→ evidence and variations verified
→ billable lines frozen
→ invoice eligibility check
→ WorkCore invoice draft
→ approval policy
→ WorkCore invoice issued
→ ledger posting
→ payment request and QR
→ MagicAI Sales receives display projection
→ customer receives document through selected channel
```

If MagicAI Sales generates the visual PDF, it consumes a locked WorkCore invoice snapshot, returns a document ID/hash, does not recalculate totals and does not create another invoice balance.

---

# 36. Recommended payment flow

```text
customer pays
→ payment evidence received
→ evidence deduplicated
→ WorkCore match candidates
→ confidence/policy decision
→ confirmed allocation
→ invoice balance updated
→ receipt
→ ledger
→ collections stopped
→ MagicAI CRM receives payment-status projection
```

MagicAI CRM may display unpaid, partially paid, paid, overdue, credited and voided states. It should not independently allocate or reconcile payment.

---

# 37. External mapping

Required Sales mappings:

```text
CRM estimate ↔ WorkCore quote/source snapshot
CRM proposal ↔ WorkCore quote/source snapshot
CRM invoice display ↔ WorkCore invoice
CRM payment display ↔ WorkCore payment/allocation
CRM customer ↔ WorkCore customer
```

Each mapping includes company, CRM workspace, source ID, source version, WorkCore public ID, authority, payload hash, sync status and conflict state.

---

# 38. Menu design

Recommended final navigation when both systems are installed:

```text
CRM
├── Contacts
├── Companies
├── Deals
├── Sales Activities
├── Projects/Tasks
└── CRM Assistant

Sales
├── Estimates and Proposals
├── Accepted Sales
├── Customer Documents
└── Sales Reports

Commercial
├── Service Catalogue
├── Job Costing
├── Variations
├── Billable Work
├── Invoice-Ready Jobs
└── Profitability

Finance
├── Invoices
├── Payments
├── Receivables
├── Reconciliation
├── Expenses and Payables
├── GST and Ledger
└── Reports
```

Avoid showing two independent invoice lists.

---

# 39. AI tool boundary

Potential MagicAI CRM Sales tools remain unverified and may include drafting estimates/proposals, sending documents and recording customer acceptance.

Confirmed or planned WorkCore tools include:

```text
create invoice from completed job
ingest payment evidence
confirm payment match
execute collection follow-up
import bank statement
close reconciliation
```

All high-consequence tools require verified approval and one record authority.

---

# 40. Required MagicAI host work

1. Obtain and scan the paid CRM extension.
2. Verify Sales tenancy and permissions.
3. Verify estimate/proposal acceptance evidence.
4. Verify invoice numbering and tax logic.
5. Verify payment gateway and webhook handling.
6. Verify partial payment and allocation.
7. Verify refund and credit-note behaviour.
8. Add finer Sales plan entitlements.
9. Add server-side Sales settings.
10. Add WorkCore document-projection adapters.
11. Prevent CRM Sales from becoming a second ledger.
12. Add external-ID and version hooks.

---

# 41. Required WorkCore fixes

## Critical

1. Keep `production_ready=false` until testing is complete.
2. Fix the invoice action-key mismatch.
3. Complete input factories for every registered action.
4. Enforce approval metadata centrally.
5. Require verified approval for reconciliation close.
6. Require verified confirmation for payment allocation.
7. Fix Finance publish paths.
8. Replace `auth:sanctum` with host-configurable authentication.
9. Make payment-confirmation completion consistent.
10. Add finance-specific tests.

## High

11. Build quote and estimate actions.
12. Build quote acceptance and conversion.
13. Build routed invoice/payment APIs.
14. Build customer-facing document routes.
15. Build refund and credit-note actions.
16. Build recurring invoice generator.
17. Build customer statements.
18. Add finance workspace UI.
19. Add durable outbox-failure monitoring.
20. Declare QR dependency in extension metadata.
21. Add company-scope and cross-record validation tests.
22. Add database constraints or invariant checks for related finance records.

---

# 42. Required tests

## Estimates and quotes

- Estimate version is immutable.
- Quote version is immutable after send.
- Acceptance binds the exact quote version.
- Expired quote cannot be accepted.
- Cross-company quote cannot be viewed or accepted.
- Replayed acceptance is idempotent.
- Accepted quote creates one operational conversion.

## Invoices

- Completed job produces one invoice.
- Incomplete job cannot invoice.
- Missing evidence blocks invoice.
- Duplicate source key returns existing invoice.
- GST-inclusive and exclusive totals are correct.
- Discounts cannot exceed gross line value.
- Issue snapshot remains immutable.
- Invalid lifecycle transition is rejected.
- Cross-company job cannot be invoiced.

## Actions and approvals

- Registry key equals action constant.
- Every registered action has an input factory.
- High-risk actions reject missing approval.
- Approval binds action, payload, actor and company.
- AI tools cannot bypass action approval.
- Approval cannot be replayed.

## Payments

- Duplicate evidence creates no duplicate payment.
- Currency mismatch is rejected.
- Partial payment updates balance.
- Overpayment creates an unallocated-credit exception.
- Concurrent allocations cannot over-allocate.
- Confirmation is idempotent.
- Receipt and ledger post exactly once.
- Evidence, audit and payment reach consistent completion.

## Refunds and credits

- Refund cannot exceed settled payment.
- Credit note follows invoice-balance rules.
- Ledger reversals balance.
- Refund is idempotent.
- Customer balance projection is correct.

## Reconciliation

- Unbalanced reconciliation cannot close.
- Unresolved lines prevent close.
- Approval is mandatory.
- Closed reconciliation cannot be modified.
- Imported duplicate bank row is rejected.

## Integration

- MagicAI SaaS invoice never becomes a WorkCore customer invoice.
- CRM Sales projection never recalculates WorkCore totals.
- CRM invoice display maps to one WorkCore invoice.
- CRM payment display maps to WorkCore allocations.
- Disabling CRM leaves WorkCore finance intact.
- Disabling WorkCore blocks CRM financial mutations safely.

---

# 43. Decisions established by Scan 11

1. Scan 10 remains blocked until the paid CRM package is supplied.
2. MagicAI core proves Sales presentation contracts only.
3. MagicAI SaaS order invoices are separate from CRM customer invoices.
4. WorkCore is the provisional operational finance authority.
5. MagicAI Sales may become a presentation and front-office adapter.
6. No dual invoice or payment authority is permitted.
7. Accepted sales terms convert through a governed bridge.
8. WorkCore owns invoice-ready decisions from delivered work.
9. WorkCore owns payment evidence, allocation, GST, reconciliation and ledger.
10. WorkCore Finance is not production-ready yet.
11. Schema presence does not equal completed workflow.
12. Approval metadata must become enforceable.
13. Finance action keys and input factories must be normalised.
14. Public finance routes and UI must be built.
15. Finance-specific test coverage is mandatory before launch.

---

# 44. Next scan

```text
12-magicai-customer-lifecycle-and-workcore-conversion.md
```

It should map lead capture, contact capture, chatbot capture, phone and message capture, CRM conversion, deal won, estimate/proposal acceptance, customer onboarding, WorkCore customer and site creation, service agreement creation, work-order generation, invoice-ready and payment events, retention, mappings, idempotent conversion, consent, failure recovery and conflict handling.

---

# Evidence files

## MagicAI core

```text
app/Domains/Marketplace/MarketplaceServiceProvider.php
app/Services/Common/MenuService.php
app/Http/Middleware/CheckTemplateTypeAndPlan.php
routes/panel.php
app/Http/Controllers/Dashboard/UserController.php
app/Models/UserOrder.php
resources/views/default/panel/user/orders/
```

## WorkCore Finance

```text
app/Domains/WorkCore/System/Modules/Finance/
app/Domains/WorkCore/System/Modules/Finance/WorkCoreFinanceServiceProvider.php
app/Domains/WorkCore/System/Modules/Finance/config/titan-money.php
app/Domains/WorkCore/System/Modules/Finance/Authorization/TitanMoneyPermission.php
app/Domains/WorkCore/System/Modules/Finance/Application/Actions/
app/Domains/WorkCore/System/Modules/Finance/Application/Services/
app/Domains/WorkCore/System/Modules/Finance/Domain/Commercial/
app/Domains/WorkCore/System/Modules/Finance/Domain/Payments/
app/Domains/WorkCore/System/Modules/Finance/Domain/Ledger/
app/Domains/WorkCore/System/Modules/Finance/Domain/Tax/
app/Domains/WorkCore/System/Modules/Finance/Domain/Reconciliation/
app/Domains/WorkCore/System/Modules/Finance/Infrastructure/Persistence/
app/Domains/WorkCore/System/Modules/Finance/Http/Routes/
app/Domains/WorkCore/Database/Migrations/2026_07_23_120040_create_tm_tax_registrations_table.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120044_create_tm_commercial_documents_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120045_create_tm_payments_expenses_and_payables_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120046_create_tm_ledger_tax_and_period_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120047_create_tm_reconciliation_forecasting_and_export_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120048_create_tm_autonomous_money_management_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120050_create_tm_collections_and_payment_runtime_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120052_create_tm_missing_money_domains_tables.php
```

---

# Evidence limitations

The paid MagicAI CRM Sales extension was not available.

This report does not prove paid Sales route behaviour, data model, tenant isolation, tax handling, payment processing, acceptance/signature workflow, invoice lifecycle or tests.

WorkCore findings are based on static source and schema inspection. The module was not booted with Composer dependencies or a database, and no finance-specific runtime tests were supplied.
