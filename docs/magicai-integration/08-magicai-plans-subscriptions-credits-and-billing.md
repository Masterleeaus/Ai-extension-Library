---
title: "MagicAI 11 Plans, Subscriptions, Credits and Billing"
scan_number: 8
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/08-magicai-plans-subscriptions-credits-and-billing.md"
---

# Scan 08 — MagicAI 11 Plans, Subscriptions, Credits and Billing

## Executive conclusion

MagicAI 11 contains a broad SaaS monetisation layer supporting subscription and prepaid plans, monthly and annual billing, lifetime access, trials, free and hidden plans, team seats, feature entitlements, AI model limits, legacy and shared credits, coupons, taxes, orders, mobile subscriptions and multiple gateways.

It should remain Titan Zero's **platform subscription and AI-usage authority**.

WorkCore Finance should remain the **operational commercial authority** for customer quotes, job costing, customer invoices, payment evidence, reconciliation, expenses, GST, ledger and profitability.

> **MagicAI billing owns the subscription between Titan Zero and the business.**  
> **WorkCore finance owns the commercial relationship between the business and its customers, workers, suppliers and regulators.**

## Critical findings

1. MagicAI has overlapping subscription and credit systems rather than one canonical entitlement ledger.
2. Active-subscription resolution accepts gateway-specific status strings but does not validate `ends_at`.
3. It selects the first active subscription without deterministic ordering.
4. `User::activePlan()` uses the subscription row's `updated_at`, not `ends_at`, to decide monthly and annual validity.
5. `PaymentProcessController::isActiveSubscription($planId)` compares the subscription record ID with the plan ID instead of comparing `plan_id`.
6. The subscription-end command selects Paystack renewals using `iyzico_approved` instead of `paystack_approved`.
7. Paystack's `handleChargeSuccess()` webhook handler is empty.
8. Webhook history has no unique provider-event constraint.
9. Stripe and PayPal duplicate protection mainly compares the event time with subscription creation time rather than using event-ID idempotency.
10. Retried webhooks can create repeated orders or grant credits repeatedly.
11. Team shared-credit renewals mutate balances without creating ledger transactions.
12. Shared-credit cancellation subtracts the original plan allocation from the current balance, potentially removing separately purchased credits.
13. Shared-credit history cascades away when a user is deleted.
14. Legacy model credits are mutable JSON balances without an immutable ledger.
15. The plans API returns inactive and hidden plans without subscriber-facing filtering.
16. The order API can bypass ownership checks when `plan_id` is null.
17. Coupon checkout lookup does not consistently enforce usage limits.
18. Coupon redemption is not atomic and the pivot has no `coupon_id + user_id` uniqueness constraint.
19. Gateway secrets and webhook secrets are stored without encrypted casts.
20. Plan and order prices use floating-point columns.
21. Admin assignment grants a normal monthly plan one year of access.
22. Admin assignment labels an internal grant using the first active payment gateway and dereferences that gateway even when none exists.
23. The prepaid Stripe path records `stirpe` and calculates zero tax.
24. Several billing mutations still use GET routes, including plan deletion, bank-order deletion and web subscription cancellation.
25. `saveGatewayProducts()` can call both new and legacy implementations for a refactored gateway.
26. Plan deletion cancels provider subscriptions synchronously and can stop after the first provider error.

# 1. Plan taxonomy

Primary plan types:

```text
subscription
prepaid
```

Frequencies:

```text
monthly
yearly
lifetime_monthly
lifetime_yearly
lifetime
prepaid
```

Marketing/access labels also include:

```text
regular
premium
pro
enterprise
vip
```

WorkCore should rely on explicit feature entitlements, not these marketing labels.

# 2. Plan capabilities

The Plan model exposes 57 assignable fields across:

- Commercial details: price, currency, frequency, trial, private/hidden state
- Team plans and seat limits
- General AI tools and feature entitlements
- Generator-template access
- Per-model AI credits and unlimited flags
- Chatbot, channels and human-agent limits
- Voice, phone and dubbing limits
- Deep Research and Model Council access
- AI Agent workflow, channel, message and memory limits
- UGC, captions, social automation and marketing-bot limits
- Shared-credit configuration

Recommended WorkCore plan keys:

```text
ext_workcore_core
ext_workcore_operations
ext_workcore_workforce
ext_workcore_resources
ext_workcore_commercial
ext_workcore_offline
ext_workcore_ai_actions
```

Possible meters:

```text
workcore_company_limit
workcore_manager_seat_limit
workcore_field_user_limit
workcore_offline_device_limit
workcore_storage_limit
workcore_vertical_pack_limit
workcore_ai_action_monthly_limit
```

A MagicAI team seat must not be treated as a WorkCore worker.

# 3. Plans API and private plans

The pricing service filters active, non-hidden plans. The authenticated API endpoint loads all plans and gateway-product records without those filters.

Subscriber-facing APIs should return only plans that are:

```text
active
visible to the current actor
available in the actor's region/channel
within any subscriber cap
```

Use a dedicated public Plan resource rather than serialising the raw model.

# 4. Subscription state

The subscription table mixes provider and business status values such as:

```text
active
trialing
bank_approved
banktransfer_approved
bank_renewed
free_approved
stripe_approved
paypal_approved
iyzico_approved
paystack_approved
yokassa_approved
cancelled
bank_rejected
```

Recommended normalized states:

```text
pending
trial
active
grace_period
past_due
suspended
cancel_at_period_end
cancelled
expired
lifetime
```

Keep provider status separately:

```text
provider
provider_subscription_id
provider_status
provider_period_start
provider_period_end
```

WorkCore should consume a normalized entitlement projection, never raw gateway statuses.

# 5. Active-subscription defects

`Helper::getCurrentActiveSubscription()` returns the first subscription matching its status list. It does not order results or require a future period end.

`User::activePlan()` then considers monthly and annual validity from `updated_at`. This can both extend and shorten access incorrectly.

Required fields should be distinct:

```text
access_valid_until
credit_reset_at
provider_period_end
cancellation_effective_at
```

Permanent access must not expire merely because its credit allowance renews monthly.

# 6. Active-plan comparison defect

The current duplicate-subscription check compares:

```text
subscription.id === requested plan id
```

It must compare:

```text
subscription.plan_id === requested plan id
```

The current code can permit duplicate purchases or reject the wrong plan when numeric IDs happen to match.

# 7. Trials

Plans support `trial_days`, but gateway implementations differ and there is no single trial-consumption ledger.

Recommended trial record:

```text
trial_started_at
trial_ends_at
trial_plan_id
trial_consumed_at
trial_source
```

WorkCore module trials should be projected from MagicAI rather than creating a second trial system.

# 8. Subscription expiry and Paystack

The scheduled subscription-end command processes gateway-specific statuses. Its Paystack branch filters `iyzico_approved`, so Paystack lifetime renewals may not run while Iyzico records can enter the wrong event path.

The Paystack charge-success listener is also empty in the supplied source.

A table-driven normalized lifecycle service and gateway contract are required.

# 9. Administrator grants

The admin plan-assignment flow manufactures a payment-like subscription using the first active gateway.

Confirmed problems:

- Monthly plans receive one year unless they are `lifetime_monthly`.
- No-gateway installations can dereference null gateway data.
- No-payment grants are labelled with a real gateway.
- Auto renewal is enabled without a separate grant policy.

Replace this with an explicit grant record containing:

```text
grant reason
granting actor
plan
starts_at
ends_at
credit policy
renewal policy
no-payment source
audit metadata
```

# 10. Prepaid/token packs

Prepaid plans reuse the Plan model and grant either legacy model credits or shared credits.

The Stripe prepaid success path contains:

```text
payment_type = stirpe
```

and records tax using `taxToVal(plan price, 0)`, producing zero tax.

These defects damage reconciliation and reporting.

# 11. Plan deletion

Deleting a plan attempts to cancel all active subscriptions synchronously before deleting it.

Risks:

- Partial provider cancellation
- Long-running web requests
- No operation journal
- No retry queue
- Plan foreign keys becoming null on historical records
- Loss of historical entitlement meaning

Plans should normally be retired and hidden, not physically deleted. Orders and subscriptions should retain immutable plan-version snapshots.

# 12. Gateway inventory

The gateway enum contains 13 families:

```text
PayPal
Stripe
2Checkout
YooKassa
Iyzico
Paystack
Bank Transfer
RevenueCat
CoinGate
Paddle
Razorpay
CryptoMus
Midtrans
```

CryptoMus and Midtrans depend on extensions. The payment layer mixes legacy static services with a newer factory contract. Only 2Checkout is marked refactored.

All gateways should converge on one interface:

```text
create product
create price
start checkout
cancel subscription
resolve status
verify webhook
refund
```

Every mutating provider operation requires an idempotency key.

# 13. Gateway product duplication

`saveGatewayProducts()` calls the new factory when a gateway is refactored and then unconditionally calls the legacy selector too.

The legacy call must be placed in `else` or removed after migration.

# 14. Gateway credential security

The gateway table stores client secrets, application IDs and webhook secrets directly. No encrypted casts or redacted serializer were found.

Required controls:

- Encryption at rest
- Redacted administrator responses
- Secret rotation
- Audit of reads and writes
- Separate vaults for Titan platform billing and WorkCore company payment connections

# 15. Webhook idempotency

`webhookhistory` records provider event details but has no unique constraint on provider plus event ID.

The correct flow is:

```text
verify signature
→ start transaction
→ claim unique provider event
→ return success if already processed
→ transition subscription
→ create order and credit transaction
→ commit
```

A five-minute time comparison is not sufficient idempotency.

# 16. Stripe and Paystack listener defects

The Stripe recurring listener uses `$user->country` before assigning `$user`.

The Paystack charge-success handler performs no work.

Both findings support replacing duplicated listener logic with one normalized renewal service.

# 17. SaaS orders versus WorkCore invoices

MagicAI `user_orders` records platform plan and credit purchases. These are Titan Zero SaaS orders.

They are not WorkCore customer invoices.

The API's single-order permission check applies only when `plan_id` is not null. Every non-admin lookup must instead include:

```text
user_id = authenticated user
```

regardless of order type.

# 18. Monetary precision

Original plan and order prices use double/float storage. WorkCore already uses money value objects and minor-unit amounts.

The integrated rule should be:

```text
amount_minor integer
currency_code
tax_minor
discount_minor
total_minor
```

WorkCore money must not be weakened to match MagicAI floats.

# 19. Taxes

MagicAI supports platform-sales taxation through gateway default tax, optional country taxes, automated gateway tax and tax-inclusive plans.

WorkCore retains operational tax authority for:

- GST registration
- GST-inclusive/exclusive service lines
- GST-free lines
- Supplier taxes
- BAS periods
- Adjustments and credits
- Tax ledger postings

# 20. Coupons

Coupon records include percentage, global limit, duration and offer metadata.

Confirmed weaknesses:

- Checkout lookup does not consistently enforce the limit.
- Validation and checkout use different paths.
- Redemption is not atomic.
- The pivot has no coupon/user uniqueness constraint.
- Concurrent purchases can exceed the limit.
- Several gateways can attach duplicate usage rows.

Create one atomic coupon service that locks the coupon, validates dates and limits, reserves redemption, calculates in minor units and commits or releases the reservation with checkout.

# 21. Legacy separated credits

Legacy credits are nested JSON balances by engine and model with an unlimited flag.

Limitations:

- Mutable balances without an immutable transaction ledger
- Weak source attribution
- Difficult reconciliation
- Catalogue-shape migration risk
- Duplicate webhooks can grant twice
- Cancellation subtraction can interact badly with top-ups

Treat this as legacy compatibility.

# 22. Shared-credit system

The newer shared-credit system provides:

- User balance
- Team balance
- Per-entity unit costs
- Transaction rows
- Row-locked deduction
- Admin adjustment
- Low-balance notifications
- Plan allowances
- Migration tooling

Positive controls include database transactions and row locks for user and team deductions.

# 23. Shared-credit defects

## Missing team ledger entries

Team-plan top-up and renewal update `teams.shared_credits` directly without a shared-credit transaction.

## Ambiguous balance-after value

A team-funded transaction records the user's balance only, not the combined balance or team balance.

## Membership trust

`deductWithTeam()` accepts a Team object and does not itself prove the user belongs to it.

## Cancellation removes unrelated credits

Cancellation subtracts the plan's original allowance from the current balance. If subscription credits were spent and prepaid credits were later added, cancellation can remove the prepaid credits.

Use source-aware credit lots:

```text
subscription allowance
prepaid top-up
admin grant
promotion
refund
consumption
expiry
reversal
```

Cancellation expires only the unused subscription lot.

## History deletion

Shared-credit transactions cascade-delete with the user. Usage and billing records should be retained or anonymised according to policy.

# 24. WorkCore AI usage funding

MagicAI shared credits can fund WorkCore AI calls through an adapter, but each rated use must additionally record:

```text
WorkCore company
actor
action or operation
model/entity
quantity
cost
credit source
correlation ID
```

MagicAI Team remains the credit group. It does not become the WorkCore tenant.

# 25. WorkCore Finance capability

WorkCore Finance/Titan Money includes:

- Estimates and quotes
- Invoice lifecycle and approvals
- Payments and allocations
- Payment evidence and matching
- Cash, PayID, bank transfer and PayPal adapters
- QR payment requests
- Receivables ageing
- Expenses and suppliers
- Budgets and forecasting
- Purchase orders and receipts
- E-invoicing
- Reconciliation
- Ledger and accounting periods
- GST reporting
- Payables
- Job profitability
- Accountant exports
- Collections automation
- Audit and idempotency

It exposes more than 60 `money.*` permissions.

This is operational finance, not platform subscription billing.

# 26. Authority matrix

| Record or decision | Authority |
|---|---|
| Titan Zero subscription plan | MagicAI |
| Titan Zero gateway charge | MagicAI |
| MagicAI team seat | MagicAI |
| AI credit account | MagicAI |
| WorkCore module entitlement | MagicAI projected into WorkCore |
| Company feature override | WorkCore |
| Customer quote | WorkCore |
| Job cost | WorkCore |
| Customer invoice | WorkCore |
| Customer payment evidence | WorkCore |
| Reconciliation and GST | WorkCore |
| Supplier expense | WorkCore |
| Operational ledger | WorkCore |
| Titan's own Xero export | MagicAI/platform accounting |
| Business customer's Xero export | WorkCore company integration |

# 27. Xero boundary

MagicAI's `UserOrder` creation hook attempts to create an accounts-receivable invoice in Xero when the extension is installed. This is platform accounting for Titan Zero's SaaS sales.

It must not be reused for WorkCore customer invoices.

The hook is synchronous, swallows exceptions and does not show durable retry or external-ID storage. WorkCore needs a company-scoped Xero outbox, retry policy and record mapping.

# 28. Required events

MagicAI to WorkCore:

```text
SubscriptionActivated
SubscriptionChanged
SubscriptionSuspended
SubscriptionCancelled
PlanEntitlementsChanged
TeamSeatActivated
TeamSeatRemoved
SharedCreditLow
SharedCreditExhausted
```

WorkCore to MagicAI:

```text
WorkCoreAiUsageRated
WorkCoreStorageUsageChanged
WorkCoreActiveSeatUsageChanged
WorkCoreDeviceUsageChanged
```

Customer invoices and operational payments must never be written into MagicAI's SaaS order tables.

# 29. Required MagicAI fixes

## Critical

1. Build a normalized subscription resolver.
2. Use period-end dates, not `updated_at`.
3. Fix subscription ID versus plan ID comparison.
4. Fix Paystack renewal filtering and success handling.
5. Add unique webhook-event idempotency.
6. Make credit grants idempotent.
7. Add source-aware shared-credit cancellation.
8. Add team credit ledger entries.
9. Fix null-plan order ownership.
10. Filter hidden/inactive plans from subscriber APIs.
11. Encrypt gateway secrets.

## High

12. Replace floats with minor units.
13. Add deterministic active-subscription selection.
14. Preserve plan-version snapshots.
15. Replace fake gateway grants with explicit grants.
16. Correct monthly grant expiry.
17. Fix no-gateway assignment.
18. Fix Stripe prepaid metadata.
19. Unify gateway interfaces.
20. Make coupon redemption atomic.
21. Preserve or anonymise credit history.
22. Replace state-changing GET routes.
23. Queue cancellation and retirement workflows.

# 30. Required WorkCore integration work

1. Create `EffectiveSubscriptionResolver`.
2. Create `WorkCorePlanEntitlementAdapter`.
3. Store a company-scoped entitlement projection.
4. Include entitlement revision in WorkCore caches.
5. Add a MagicAI credit-account adapter.
6. Attribute WorkCore AI usage to company and actor.
7. Keep WorkCore invoices out of `user_orders`.
8. Keep business gateway credentials company-scoped.
9. Publish invoice-ready and payment events through WorkCore outbox.
10. Keep all WorkCore money in fixed minor units.
11. Test subscription downgrade, cancellation and entitlement revocation.

# 31. Required tests

- Hidden and inactive plans are not publicly returned.
- Active subscription selection is deterministic.
- Expired subscriptions are not entitled.
- Monthly validity uses period end.
- Existing plans are detected by `plan_id`.
- Paystack renewal is processed exactly once.
- Duplicate webhooks grant credits once.
- Team top-ups create ledger entries.
- Cancellation preserves prepaid and admin-granted credits.
- Users cannot read another null-plan order.
- Coupon limits cannot be exceeded concurrently.
- WorkCore AI usage records company and actor.
- MagicAI cancellation disables premium WorkCore modules while preserving operational data.
- WorkCore customer invoices never appear in MagicAI SaaS orders.
- Platform and company Xero connections remain isolated.

# 32. Decisions established by Scan 08

1. MagicAI remains the SaaS subscription authority.
2. MagicAI remains the AI credit authority.
3. WorkCore remains the operational finance authority.
4. MagicAI plans project WorkCore module entitlements.
5. MagicAI Team seats do not equal WorkCore workers.
6. MagicAI orders do not equal WorkCore invoices.
7. Platform gateways do not become company payment gateways.
8. WorkCore money remains fixed precision.
9. WorkCore accounting integrations remain company-scoped.
10. A normalized subscription resolver is required.
11. Shared credits need source-aware lots.
12. Webhook processing must become idempotent.
13. Access renewal and credit renewal must be separate.
14. Plan deletion becomes retirement.
15. Subscription changes reach WorkCore through durable events and projections.

# 33. Next scan

```text
09-magicai-crm-host-contract.md
```

It will map CRM provider registration, routes, menus, plan features, settings, assistant hooks, Agent tools, contact capture, Sales contracts, missing extension source and the exact WorkCore CRM bridge.

# Evidence files

## MagicAI

```text
app/Models/Plan.php
app/Enums/Plan/FrequencyEnum.php
app/Models/Finance/Subscription.php
app/Models/UserOrder.php
app/Models/Gateways.php
app/Models/Coupon.php
app/Models/SharedCreditTransaction.php
app/Models/WebhookHistory.php
app/Helpers/Classes/Helper.php
app/Services/Finance/PlanService.php
app/Services/SharedCredit/SharedCreditService.php
app/Services/PaymentGateways/Contracts/CreditUpdater.php
app/Http/Controllers/Finance/PaymentProcessController.php
app/Http/Controllers/Api/PaymentApiController.php
app/Livewire/Admin/Finance/Plan/SubscriptionPlanCreate.php
app/Console/Commands/CheckSubscriptionEnd.php
app/Listeners/StripeWebhookListener.php
app/Listeners/PaystackWebhookListener.php
routes/api.php
routes/panel.php
```

## WorkCore

```text
app/Domains/WorkCore/System/Modules/Finance/WorkCoreFinanceServiceProvider.php
app/Domains/WorkCore/System/Modules/Finance/Authorization/TitanMoneyPermission.php
app/Domains/WorkCore/System/Modules/Finance/Application/Actions/CreateInvoiceFromCompletedJob.php
app/Domains/WorkCore/System/Modules/Finance/Application/Actions/IngestAndMatchPaymentEvidence.php
app/Domains/WorkCore/System/Modules/Finance/Application/Actions/ConfirmPaymentMatch.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120040_create_tm_tax_registrations_table.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120044_create_tm_commercial_documents_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120045_create_tm_payments_expenses_and_payables_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120046_create_tm_ledger_tax_and_period_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120047_create_tm_reconciliation_forecasting_and_export_tables.php
```

# Evidence limitations

This was a static scan. It did not execute gateways, webhook verification, concurrent delivery, trial conversion, subscription cancellation, credit migration, coupon races, RevenueCat reconciliation, Xero export or WorkCore finance actions.

The confirmed defects are directly supported by the supplied source and should become regression tests before billing integration.
