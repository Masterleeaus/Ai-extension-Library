---
title: "MagicAI 11 Customer Lifecycle and WorkCore Conversion"
scan_number: 12
date: "2026-08-03"
magicai_version: "11.00"
status: "Completed with paid-extension limitations"
blocked_dependency:
  scan: 10
  reason: "The paid MagicAI CRM extension was not available, so CRM-extension-specific capture, conversion and Sales behaviour remains unverified."
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/12-magicai-customer-lifecycle-and-workcore-conversion.md"
---

# Scan 12 — MagicAI 11 Customer Lifecycle and WorkCore Conversion

## Executive conclusion

MagicAI core and WorkCore together contain most of the components required for a complete customer lifecycle, but they do not yet form one reliable end-to-end workflow.

MagicAI core provides:

- Platform user registration
- Optional Mailchimp subscription on registration
- Optional HubSpot contact creation on registration
- Optional Xero contact creation on registration
- A generic `ContactCapturedEvent` contract
- Host contracts for CRM, Chatbot, Booking, channels and Sales extensions
- AI Chat transport and CRM Assistant integration hooks

The supplied MagicAI package does not include the extension code that would capture external business customers through:

- Chatbot
- Booking
- WhatsApp
- Messenger
- Instagram
- Telegram
- Phone agent
- Paid CRM
- Sales estimates or proposals

WorkCore provides working company-scoped domains for:

- Leads
- Customers
- Contacts
- Opportunities
- Pipelines
- Premises/service sites
- Service agreements
- Recurring-service generation
- Work orders
- Appointments
- Job completion controls
- Invoicing
- Payment evidence and matching
- Reviews
- Service recovery
- Rebooking

Across the main lifecycle domains, WorkCore contains approximately:

| Domain | PHP files | Action classes | Declared route calls |
|---|---:|---:|---:|
| CRM | 63 | 20 | 23 |
| Premises | 410 | 16 | 17 |
| Recurring Services | 22 | 7 | 9 |
| Operations | 18 | 5 | 6 |
| Scheduling | 15 | 4 | 5 |
| Reviews | 18 | 6 | 7 |
| Finance | 313 | 7 registered built-in actions | Limited public routes |

The strongest current architecture is:

> **Capture channels create or enrich a lead.**  
> **A governed conversion creates operational customer records.**  
> **Accepted commercial terms initiate an onboarding saga.**  
> **WorkCore creates sites, agreements, work orders and appointments.**  
> **Completion creates invoice-ready state.**  
> **WorkCore Finance issues and reconciles the invoice.**  
> **Reviews, recovery and rebooking continue the relationship.**

That full saga does not yet exist.

## Critical findings

1. MagicAI's `ContactCapturedEvent` is not dispatched anywhere in core.
2. Its payload is insufficient for multi-company WorkCore conversion.
3. MagicAI platform user registration can create HubSpot and Xero contacts, conflating Titan Zero subscribers with the subscriber's business customers.
4. Those external integration failures are silently swallowed.
5. WorkCore lead conversion creates only a customer record.
6. It does not create a primary contact, premises, accepted-scope snapshot, service agreement or onboarding workflow.
7. Lead conversion checks `converted_customer_id` before its transaction but does not lock the lead row.
8. Concurrent conversions can create duplicate customers.
9. Duplicate detection is advisory only; customer and lead creation still proceed.
10. Customer and lead tables have indexes but no company-scoped uniqueness constraints for normalized email or phone.
11. Lead conversion does not reconcile against an existing matching customer.
12. Opportunity-won only closes the opportunity.
13. It does not convert its lead, create a customer, create a commercial snapshot or start operations.
14. Opportunity monetary amounts use decimal/float-oriented DTO handling rather than WorkCore Finance minor-unit money.
15. There is no accepted-proposal or accepted-estimate bridge because the paid CRM Sales extension is unavailable.
16. WorkCore customer and contact records lack a general communication-consent and preference model.
17. Customer creation has no update, merge, archive or lifecycle action in the supplied CRM action catalogue.
18. Contact creation has no duplicate detector and no update action beyond setting the primary contact.
19. Primary-contact uniqueness is enforced only by application updates, not a database constraint or lock across concurrent requests.
20. Premises creation is separate from lead/customer conversion and requires another explicit action.
21. Service agreements have no source opportunity, proposal, estimate or accepted-sale mapping.
22. There is no customer-onboarding saga coordinating customer, contact, premises, agreement and first work order.
23. Recurring-service generation directly inserts work orders and appointments.
24. It bypasses the WorkCore business-action dispatcher, approval, audit, domain-event recording and idempotency layer.
25. Its appointment inserts bypass the normal appointment repository's relationship and scheduling-conflict checks.
26. Recurring-created work orders do not emit the normal `workcore.work_order.created` event.
27. Recurring-created appointments do not emit the normal `workcore.appointment.created` event.
28. Work-order completion emits `workcore.work_order.status_changed`.
29. The finance completion listener expects `work.job.completed`.
30. No registration of `CompletedJobMoneyFlowListener` was found.
31. Automatic completion-to-invoice flow is therefore disconnected.
32. Review and rebooking actions exist but no completion listener automatically creates a review request.
33. Review reference resolution queries `tz_contacts`, but the actual WorkCore contact table is `tz_customer_contacts`.
34. Review requests that include a contact reference will therefore fail.
35. Review and rebooking tables mostly use unconstrained integer references rather than foreign keys.
36. There is no unified consent, event mapping, conversion checkpoint, compensation or recovery workflow.
37. Most WorkCore module routes are disabled by default and still use Sanctum while MagicAI uses Passport.

---

# 1. Target customer lifecycle

The required Titan Zero lifecycle is:

```text
Anonymous enquiry
→ captured contact
→ lead
→ qualification
→ opportunity
→ estimate/proposal
→ acceptance
→ operational conversion
→ customer
→ contacts
→ service site/premises
→ service scope or agreement
→ work order
→ appointment and dispatch
→ field delivery and evidence
→ completion
→ invoice-ready
→ invoice
→ payment and allocation
→ receipt
→ review
→ service recovery where needed
→ rebooking or recurring service
→ retention
```

Every transition must preserve:

```text
company
actor
source
consent
external IDs
idempotency
correlation
causation
record authority
version
audit
```

---

# 2. Customer identity boundaries

The integrated system contains several different customer-like concepts.

## Titan Zero subscriber

A MagicAI `User` who purchases or uses the platform.

This is:

```text
Titan Zero customer
```

It is not automatically a customer of the user's field-service business.

## MagicAI CRM contact/company

A prospect or customer record inside the missing paid CRM extension.

Its exact schema and tenancy remain unverified.

## WorkCore customer

A customer of a WorkCore operating company.

It may be:

- Individual
- Business
- Property owner
- Property manager
- Facility
- NDIS participant or representative
- Service recipient

## WorkCore customer contact

A named contact associated with a WorkCore customer.

## Portal user

An optional MagicAI user linked to a WorkCore customer or contact for portal access.

Portal identity must remain separate from operational record authority.

---

# 3. MagicAI capture surfaces

## 3.1 Platform registration

When a MagicAI user is created, `UserObserver` can:

- Increase platform usage count
- Assign initial AI credits
- Subscribe the user to Mailchimp
- Create a HubSpot CRM contact
- Create a Xero contact
- Clear platform usage cache

These integrations concern Titan Zero's subscriber lifecycle.

They should not create records inside the subscriber's WorkCore company.

## Risk

The same terms—contact, CRM and Xero contact—are used for two different business relationships:

```text
Titan Zero → subscriber
subscriber's business → service customer
```

Integration adapters must name those boundaries explicitly.

Suggested naming:

```text
PlatformSubscriberContact
OperationalCustomerContact
```

---

# 4. Silent registration integration failures

Mailchimp, HubSpot and Xero calls in `UserObserver` catch and discard exceptions.

Consequences:

- Registration succeeds while integrations silently fail.
- No retry is scheduled.
- No external mapping status is stored.
- No operator alert is generated.
- The user may be duplicated on a later retry.
- There is no consent snapshot for marketing subscription.

## Required correction

Registration integration should write an outbox event:

```text
platform.user.created
```

Dedicated consumers should:

- Check consent and configuration
- Use idempotency
- Store external IDs
- Retry safely
- Dead-letter failures
- Surface integration health

---

# 5. ContactCapturedEvent v1

MagicAI core defines:

```php
ContactCapturedEvent(
    userId,
    name,
    email,
    phone,
    countryCode,
    source
)
```

The comment says extensions may fire it and CRM may mirror it.

## Confirmed core state

- No core dispatcher was found.
- No core listener was found.
- The event is inert unless an extension explicitly uses it.
- Chatbot and channel implementations are not present.

## Payload limitations

The event lacks:

```text
event ID
event version
occurred timestamp
WorkCore company
CRM workspace
source record ID
source conversation
idempotency key
consent
privacy notice version
marketing preference
preferred contact channel
actor
correlation
causation
update/create intent
```

`countryCode` is typed as integer and cannot safely preserve `+61`.

---

# 6. Contact capture v2

Recommended integration event:

```text
business.contact.captured.v2
```

Suggested envelope:

```json
{
  "event_id": "ULID",
  "event_version": 2,
  "occurred_at": "ISO-8601",
  "source_system": "magicai-chatbot",
  "source_channel": "whatsapp",
  "source_workspace_id": "CRM-WORKSPACE",
  "source_record_type": "conversation_contact",
  "source_record_id": "external-id",
  "workcore_company_id": "company-public-id",
  "platform_user_id": 123,
  "actor_subject_id": "actor-public-id",
  "name": "Customer Name",
  "email": "customer@example.com",
  "phone": "+614...",
  "organisation_name": "Example Pty Ltd",
  "preferred_contact_channel": "sms",
  "consent": {
    "service_contact": true,
    "marketing": false,
    "privacy_notice_version": "2026-08"
  },
  "idempotency_key": "source-specific-key",
  "correlation_id": "ULID",
  "causation_id": "ULID",
  "metadata": {}
}
```

---

# 7. Capture-source registry

WorkCore seeds these lead sources:

```text
website
phone
referral
chat
```

A complete integration should add stable source keys for:

```text
chatbot
whatsapp
messenger
instagram
telegram
voice_agent
phone_agent
email
web_form
booking
manual
import
partner
marketplace
existing_customer
review_rebooking
```

Source labels may change, but keys should remain stable for attribution and reporting.

---

# 8. Lead creation

WorkCore `CreateLead`:

1. Normalizes email and phone.
2. Finds duplicate candidates.
3. Creates a company-scoped lead.
4. Returns duplicate candidates.
5. Emits `workcore.lead.created`.

The lead contains:

```text
name
email
phone
company name
notes
source
status
owner
priority
follow-up state
follow-up date
converted customer
```

## Positive controls

- Company scope
- Governed action
- Permission
- Confirmation
- Audit and domain event
- Source and status references
- Owner membership validation
- Normalized email and phone
- Duplicate-candidate search

## Gap

Duplicate candidates do not prevent or merge duplication.

The lead is created regardless.

This may be intentional for human review, but automated capture requires a policy.

---

# 9. Duplicate policy

Recommended outcomes:

```text
no match
    → create lead

one high-confidence open lead match
    → enrich existing lead

one existing customer match
    → create activity or service enquiry against customer

multiple possible matches
    → create review-required capture record

explicit external mapping match
    → update mapped record idempotently
```

Do not create a new customer or lead solely because an inbound event was replayed.

---

# 10. Lead conversion

`ConvertLeadToCustomer` calls `LeadLifecycleService::convert()`.

Current conversion:

1. Finds lead by company and public ID.
2. Returns existing conversion if `converted_customer_id` is set.
3. Creates a new customer.
4. Copies name, company name, email, phone and notes.
5. Marks lead status won.
6. Stores converted customer.
7. Records activity.
8. Emits `workcore.lead.converted`.

## Positive controls

- Company scope
- Transaction
- Idempotent return after a prior conversion
- Customer linkage
- Conversion timestamp
- CRM activity
- Governed high-risk action

---

# 11. Concurrent lead-conversion defect

The initial lead lookup occurs before the transaction and is not locked.

Two concurrent conversion requests can both observe:

```text
converted_customer_id = null
```

and each create a customer.

The later lead update will point to only one of them.

## Required correction

Inside the transaction:

```text
select lead for update
→ re-check converted_customer_id
→ reserve conversion idempotency key
→ reconcile duplicate customer
→ create or link customer
→ update lead
→ create mapping and events
```

Add a unique conversion record or idempotency constraint.

---

# 12. Incomplete lead conversion

Current conversion does not create:

- Customer contact
- Primary contact
- Communication preferences
- Consent record
- Portal invitation
- Premises
- Opportunity relationship update
- Accepted commercial snapshot
- Service agreement
- Work order
- Appointment
- Onboarding checklist
- External record mapping

The action name should remain narrow:

```text
convert lead to customer
```

A separate sale-to-operations saga should perform operational onboarding.

---

# 13. Existing-customer reconciliation

Lead conversion always creates a customer when the lead is not previously converted.

It does not call `CrmDuplicateDetector`.

If a customer already has the same normalized email or phone, conversion can create a duplicate.

## Required conversion policy

```text
mapped external customer exists
    → link it

single high-confidence customer match
    → propose link/merge according to policy

ambiguous matches
    → require review

no match
    → create customer
```

Merges must preserve source records and audit history.

---

# 14. Customer record

WorkCore customer fields currently include:

```text
name
legal name
email
normalized email
phone
normalized phone
address
notes
status
portal user
created/updated actor
```

## Missing lifecycle fields

Recommended additions or related tables:

```text
customer type
preferred name
billing address
service address default
timezone
locale
communication preference
service-contact consent
marketing consent
privacy notice version
consent timestamp
consent source
do-not-contact state
retention category
lifecycle state
customer since
last service date
next recommended service
risk flags
external mappings
merge parent
```

Avoid putting every field directly on `tz_customers`; use related preference, consent and mapping tables where appropriate.

---

# 15. Customer lifecycle actions

The current CRM action catalogue supports:

```text
create customer
search customer
```

It does not register:

```text
update customer
archive customer
merge customer
restore customer
set communication preferences
record consent
link portal identity
```

A complete customer lifecycle requires those governed actions.

---

# 16. Customer contacts

WorkCore contact creation:

- Validates the customer belongs to the company.
- Can mark the contact primary.
- Clears other primary contacts transactionally.
- Creates a company-scoped contact.
- Emits `workcore.contact.created`.

## Gaps

- No duplicate detection.
- No update action.
- No archive action.
- No preference or consent fields.
- No separate billing, operational or emergency-contact role model.
- No database-level guarantee of one primary contact.
- Concurrent primary-contact requests can race.
- Portal identity and communication identity are not fully separated.

## Recommended model

A customer may have:

```text
primary operational contact
billing contact
site contact
after-hours contact
decision maker
property manager
tenant/occupant
emergency contact
```

Use roles and communication purposes rather than one free-text role alone.

---

# 17. Opportunity lifecycle

WorkCore opportunities support:

- Pipeline
- Stage
- Lead
- Customer
- Contact
- Owner
- Amount
- Currency
- Probability
- Expected close
- Metadata
- Won/lost state
- Activity history

`MarkOpportunityWon`:

1. Locks the opportunity.
2. Finds a won stage.
3. Moves it.
4. Sets status won.
5. Sets probability 100.
6. Sets close time.
7. Records activity.
8. Emits `workcore.opportunity.won`.

## Critical lifecycle gap

Winning an opportunity does not:

- Convert its linked lead
- Verify or create a customer
- Bind accepted estimate/proposal
- Create site/premises
- Create service agreement
- Create work order
- Trigger onboarding
- Reserve an idempotent conversion

The won event exists, but no consumer implementing the conversion was found.

---

# 18. Opportunity money model

Opportunity DTOs use:

```text
float amount
```

and database decimal values.

WorkCore Finance uses integer minor units.

At conversion, the sale snapshot must convert once into:

```text
amount_minor
currency_code
tax treatment
line-level scope
version/hash
```

Do not use opportunity headline value as invoice truth.

---

# 19. Accepted sale

The paid MagicAI Sales extension is unavailable, so accepted estimate/proposal behaviour is unverified.

The integration needs a normalized event:

```text
commercial.offer.accepted.v1
```

Required fields:

```text
source system
source workspace
source document type
source document ID
source version
payload hash
company
customer/contact
currency
line items
tax
discount
total
acceptance timestamp
signer
acceptance evidence
idempotency key
correlation
```

The event starts onboarding but does not directly insert operational records.

---

# 20. Customer onboarding saga

Recommended saga:

```text
validate accepted sale
→ resolve external mapping
→ resolve or create customer
→ resolve or create contacts
→ record consent/preferences
→ resolve or create premises
→ store accepted scope snapshot
→ choose one-off or recurring delivery
→ create service agreement when recurring
→ create first work order
→ create appointment when dates are known
→ create portal invitation when allowed
→ publish onboarding-completed event
```

Each step records:

```text
pending
completed
failed
compensated
manual_review
```

The saga must be resumable.

---

# 21. Premises/service-site creation

WorkCore premises require:

```text
company
customer
name
address
location
access instructions
parking
hazards
service requirements
status
```

Positive controls:

- Company scope
- Customer relationship
- Governed creation action
- Premises readiness
- Access points
- Hazards
- Service windows
- Contacts
- Documents
- Approval and archive actions

## Integration gap

Premises are not created or resolved during lead conversion or opportunity-won.

The accepted-sale bridge must distinguish:

```text
existing known site
new site
customer without fixed site
multiple sites
property manager acting for another party
```

---

# 22. Premises deduplication

A customer can have multiple legitimate premises.

Site deduplication should compare:

```text
external mapping
normalized address
geocoded coordinate
customer
unit/space
property identifiers
```

It must not merge sites merely because street text is similar.

The existing Managed Premises domain includes later deduplication-link migrations, but the lightweight `tz_premises` operational path still needs a clear resolver for sale conversion.

---

# 23. Service agreements

WorkCore service agreements support:

- Customer
- Premises
- Service
- Job template
- Recurring rules
- Start/end dates
- Renewal policy
- Work-order generation
- Appointment generation
- Access notes
- Source rebooking request
- Status history

## Positive lifecycle bridge

A rebooking request can become a recurring agreement through:

```text
source_rebooking_request_id
```

The request is then marked:

```text
converted_to_recurring
```

## Missing source links

Service agreements have no first-class source references for:

```text
opportunity
CRM deal
estimate
proposal
accepted quote
external agreement
```

Those should be added through a general source mapping rather than one column per system.

---

# 24. Recurring generation

Recurring agreements can generate occurrences, work orders and appointments.

Occurrence keys are company-unique, providing useful duplicate protection.

The generated occurrence stores:

```text
work_order_id
appointment_id
status
generated_at
```

This is a strong recurring-delivery foundation.

---

# 25. Recurring generation bypasses governed actions

`EloquentRecurringServiceRepository` directly inserts into:

```text
tz_work_orders
tz_work_order_status_history
tz_appointments
tz_appointment_status_history
```

It does not dispatch:

```text
workcore.work_order.create
workcore.appointment.create
```

## Consequences

The generated records bypass:

- Business-action permissions
- Confirmation
- Idempotency store
- Action audit
- Domain event recording
- Standard route/action validation
- Work-order creation event
- Appointment creation event
- Appointment conflict checks
- Participant validation
- Asset validation
- Some vertical workflow policies

## Required correction

Recurring generation should invoke internal trusted variants of the same canonical domain services or governed actions.

Automation may use a system actor and pre-authorized policy, but it must preserve:

```text
same invariants
same audit
same events
same idempotency
```

---

# 26. Work orders

WorkCore work-order creation validates:

- Active company actor
- Customer belongs to company
- Premises belongs to customer and company
- Initial status is draft or ready
- Priority is valid
- Business line
- Service/template expansion

It emits:

```text
workcore.work_order.created
```

Work-order status policy requires:

- Valid transition
- Reason for cancellation or reopening
- Required tasks completed before completion
- Trade-compliance readiness before completion

This is a strong operational boundary.

---

# 27. Appointments

Normal appointment creation validates:

- Work order/customer/premises relationships
- Contact belongs to selected customer
- Internal participants are company members
- Duplicate participants
- Appointment time range
- Scheduling conflicts
- Premises overlap policy
- Assets where configured

Recurring generation bypasses these checks.

The recurring path must be refactored to share the appointment scheduling service.

---

# 28. Completion event mismatch

Normal work-order completion produces:

```text
workcore.work_order.status_changed
```

Payload:

```text
work_order_public_id
status
reason
```

WorkCore Finance contains:

```text
CompletedJobMoneyFlowListener
```

It expects:

```text
work.job.completed
```

and searches for:

```text
payload.job_id
or
job_id
```

## Confirmed disconnect

- The names do not match.
- The payload identifiers do not match.
- No listener registration was found.
- The listener calls Finance directly rather than through a clearly registered integration consumer.

Therefore work-order completion does not automatically create an invoice through this listener.

---

# 29. Canonical completion event

Recommended event:

```text
workcore.work_order.completed.v1
```

Payload:

```text
company_id
work_order_public_id
customer_public_id
premises_public_id
completed_at
completed_by_actor_id
evidence_status
billable_status
commercial_snapshot_hash
preferred_payment_method
delivery_channels
correlation_id
causation_id
```

A Finance consumer should:

1. Reserve event ID.
2. Verify WorkCore Finance entitlement.
3. Evaluate invoice eligibility.
4. Create or return existing invoice by unique source key.
5. Record result.
6. Project invoice status to CRM/customer channels.

---

# 30. Invoice-ready versus automatic issue

Completion should first create:

```text
invoice-ready
```

or:

```text
invoice draft
```

Automatic issue depends on company policy:

```text
all evidence complete
no unresolved variation
approved commercial scope
tax configuration valid
customer billing contact available
no manual review flag
amount below automation threshold
```

This preserves the user's preferred approval-based automation model.

---

# 31. Payments

After WorkCore Finance hardening, customer payment lifecycle should be:

```text
invoice issued
→ payment instructions delivered
→ payment evidence received
→ duplicate check
→ candidate matching
→ confidence/approval
→ payment and allocation
→ receipt
→ ledger
→ collection cancellation
→ CRM status projection
```

MagicAI platform orders and subscription payments remain separate.

---

# 32. Review lifecycle

WorkCore Reviews supports:

- Review request
- Review response
- Private/public publication status
- Public-permission check
- Service recovery
- Rebooking request
- Review history

A low rating can indicate that service recovery is required.

This is a useful post-service domain.

---

# 33. Review contact-table defect

`EloquentReviewRepository::refs()` resolves contact IDs from:

```text
tz_contacts
```

The WorkCore CRM contact table is:

```text
tz_customer_contacts
```

No `tz_contacts` table was found in the CRM foundation.

## Consequence

A review request or review record containing `contact_public_id` will fail with a missing-table error.

## Required correction

Replace the table name and add a regression test.

---

# 34. Review relationship integrity

The original reviews/rebooking migration creates integer reference columns without foreign-key constraints for:

- Company
- Customer
- Contact
- Premises
- Work order
- Appointment
- Worker
- Review
- Service
- Created actor

The repository performs company checks for supplied references, which is positive.

Database constraints should still be added where lifecycle and deletion policy permit.

Where historical references must survive purge, use:

```text
public ID snapshot
display snapshot
nullable internal foreign key
```

rather than unconstrained integers alone.

---

# 35. No automatic review request

No consumer was found that listens to work-order completion and creates a review request.

No consumer was found that:

- Selects a contact channel
- Applies consent
- Waits for invoice/payment policy
- Avoids duplicate requests
- Schedules reminders
- Suppresses requests after incidents or disputes

Review actions exist but the customer-lifecycle automation is not wired.

---

# 36. Rebooking and retention

WorkCore can create a rebooking request and convert it into a recurring-service agreement.

This is a valuable retention path.

Missing lifecycle pieces include:

- Automatic rebooking suggestion after eligible service
- Customer preference and consent
- Recommended next-service date
- Service-specific cadence
- Customer response channel
- Reminder schedule
- Duplicate request suppression
- Expiry and cancellation workflow
- One-off rebooking conversion into work order/appointment
- Attribution back to review or campaign

---

# 37. Communication consent

Core CRM customers and contacts do not have a general consent model.

Premises vacancy applications and NDIS domains contain isolated consent fields, but they do not provide a general customer communication authority.

## Required tables

Suggested:

```text
tz_customer_communication_preferences
tz_customer_consents
tz_contact_points
tz_contact_suppressions
```

Consent record fields:

```text
company_id
customer_id
contact_id
purpose
channel
status
source
notice_version
captured_at
expires_at
withdrawn_at
evidence
```

Purposes may include:

```text
service delivery
appointment reminders
billing
safety
review request
rebooking
marketing
```

Operational messages and marketing must not share one undifferentiated consent flag.

---

# 38. Lifecycle event catalogue

Recommended canonical events:

## Capture and CRM

```text
business.contact.captured.v2
workcore.lead.created.v1
workcore.lead.enriched.v1
workcore.lead.qualified.v1
workcore.lead.converted.v2
workcore.opportunity.won.v2
commercial.offer.accepted.v1
```

## Onboarding

```text
workcore.customer.created.v2
workcore.contact.created.v2
workcore.premises.created.v2
workcore.customer.onboarding.started.v1
workcore.customer.onboarding.completed.v1
workcore.service_agreement.created.v2
```

## Delivery

```text
workcore.work_order.created.v2
workcore.appointment.created.v2
workcore.work_order.completed.v1
workcore.invoice.ready.v1
```

## Finance

```text
money.invoice.issued.v1
money.payment.confirmed.v1
money.receipt.issued.v1
```

## Retention

```text
workcore.review.requested.v2
workcore.review.recorded.v2
workcore.service_recovery.created.v2
workcore.rebooking.requested.v2
workcore.customer.retained.v1
```

Events should be versioned before external consumers depend on them.

---

# 39. External mapping

Use the mapping table proposed in Scans 09 and 11.

Lifecycle mappings include:

```text
channel contact ↔ WorkCore lead/contact
CRM lead ↔ WorkCore lead
CRM contact ↔ WorkCore contact
CRM company ↔ WorkCore customer
CRM deal ↔ WorkCore opportunity
CRM accepted document ↔ WorkCore commercial snapshot
CRM invoice display ↔ WorkCore invoice
CRM payment display ↔ WorkCore allocation/payment
```

Every mapping is company and workspace scoped.

---

# 40. Idempotency strategy

Each lifecycle transition needs an idempotency key.

Examples:

```text
capture:{source}:{external-contact-id}:{source-version}
lead-convert:{lead-public-id}
sale-convert:{source-system}:{document-id}:{version}
premises-create:{customer}:{normalized-address}
agreement-create:{accepted-document-hash}
occurrence:{agreement}:{rule}:{local-date}
invoice-from-work-order:{work-order-public-id}
review-request:{work-order}:{audience}:{channel}
```

Database uniqueness should support the highest-risk transitions.

---

# 41. Saga and recovery model

A sale-to-service conversion crosses several tables and modules.

A single database transaction may be too large and may include external systems.

Use a durable saga:

```text
tz_customer_onboarding_runs
tz_customer_onboarding_steps
```

Suggested states:

```text
pending
running
waiting_for_review
completed
failed
compensating
compensated
```

Steps store:

```text
input hash
result reference
attempt count
last error
started/completed time
correlation
```

Compensation normally archives or marks created drafts rather than deleting historical records.

---

# 42. Customer authority by stage

| Lifecycle stage | Provisional authority |
|---|---|
| Anonymous channel identity | Source channel |
| Lead | Selected CRM authority |
| Sales opportunity | Selected CRM authority |
| Proposal/estimate | Selected Sales authority |
| Accepted commercial snapshot | WorkCore retained copy |
| Operational customer | WorkCore |
| Service site/premises | WorkCore |
| Service agreement | WorkCore |
| Work order | WorkCore |
| Appointment/dispatch | WorkCore |
| Completion evidence | WorkCore |
| Invoice-ready | WorkCore |
| Invoice balance | WorkCore Finance |
| Payment allocation | WorkCore Finance |
| CRM invoice/payment display | Projection |
| Review/rebooking | WorkCore with channel adapters |

---

# 43. AI assistant boundary

## CRM Assistant

May:

- Capture lead
- Search prospect
- Update sales activity
- Draft estimate/proposal
- Mark opportunity won with verified approval

## Operations Assistant

May:

- Resolve customer/site
- Create onboarding proposal
- Create service agreement
- Create work order
- Schedule appointment
- Review readiness
- Prepare invoice draft
- Request review/rebooking

## Rule

No AI assistant should perform the whole lifecycle through raw model writes.

It should invoke one governed action or saga command per approved transition.

---

# 44. Required MagicAI work

## Critical

1. Add a versioned contact-capture event.
2. Include explicit WorkCore company/workspace context.
3. Add consent and source identifiers.
4. Dispatch through outbox, not direct synchronous listeners.
5. Separate subscriber integrations from operational customer integrations.
6. Scope Chatbot/CRM capture after their extension packages are supplied.
7. Add idempotency and external mapping.
8. Stop swallowing integration failures silently.

## High

9. Add capture-channel registry.
10. Add provider-health and mapping diagnostics.
11. Add CRM bridge mode.
12. Add Sales acceptance event contract.
13. Add customer-channel consent adapter.
14. Add structured capture cards for human review.

---

# 45. Required WorkCore work

## Critical

1. Lock lead during conversion.
2. Reconcile existing customer before creation.
3. Add customer/contact update and merge actions.
4. Add communication preference and consent model.
5. Add sale-to-operations onboarding saga.
6. Add accepted-commercial snapshot.
7. Link opportunity won to a deliberate conversion policy.
8. Refactor recurring generation through canonical work-order and appointment services.
9. Emit standard creation events for recurring-generated records.
10. Connect work-order completion to Finance using one canonical event.
11. Register the Finance completion consumer.
12. Fix `tz_contacts` to `tz_customer_contacts`.
13. Add review-request automation after eligible completion.
14. Replace Sanctum middleware for MagicAI hosting.

## High

15. Add premises resolver/deduplication.
16. Add source mappings to service agreements.
17. Add customer lifecycle states.
18. Add contact roles and purposes.
19. Add primary-contact concurrency protection.
20. Add rebooking conversion for one-off and recurring service.
21. Add review/rebooking consent and suppression policies.
22. Add lifecycle conflict queue.
23. Add saga recovery UI.
24. Version lifecycle events before release.

---

# 46. Required tests

## Capture

- Duplicate channel event creates one lead/update.
- Event cannot choose an unauthorized company.
- Consent is retained.
- `+61` phone country code remains intact.
- Capture replay is idempotent.
- Failed external integration retries.

## Lead conversion

- Concurrent conversion creates one customer.
- Existing matching customer can be linked.
- Ambiguous match requires review.
- Converted lead returns same customer on replay.
- Cross-company customer cannot be linked.
- Conversion records external mapping and audit.

## Opportunity and accepted sale

- Opportunity won does not duplicate conversion.
- Won opportunity without accepted scope does not create work order automatically.
- Accepted document version is immutable.
- Replayed acceptance creates one onboarding saga.
- Money converts to minor units correctly.

## Onboarding

- Customer, contact and premises are company-scoped.
- Failure after customer creation can resume.
- Existing premises are reused only after verified match.
- Recurring sale creates one agreement.
- One-off sale creates one work-order draft.
- Portal invitation respects consent.

## Recurring service

- Generation is idempotent.
- Generated work order passes standard invariants.
- Generated appointment passes conflict policy.
- Creation events are emitted once.
- Generated records contain audit and causation.
- Rescheduled occurrence does not duplicate records.

## Completion and finance

- Completed work order emits canonical event.
- Finance consumes it once.
- Ineligible job produces review-required invoice state.
- Existing invoice is returned on replay.
- Invoice issue policy is respected.

## Reviews and retention

- Contact reference uses `tz_customer_contacts`.
- Review request is generated once per eligible job.
- Suppressed contact does not receive review request.
- Low review can create one recovery case.
- Rebooking request converts once.
- Recurring conversion records source request.
- Marketing opt-out does not block required service communication.

---

# 47. Decisions established by Scan 12

1. MagicAI subscriber registration is not operational customer capture.
2. Contact capture requires a versioned, company-scoped event.
3. WorkCore remains operational customer authority.
4. Lead conversion and operational onboarding remain separate actions.
5. Opportunity won does not automatically mean work may begin.
6. Accepted commercial terms start a durable onboarding saga.
7. Customer consent and communication preferences require a dedicated model.
8. Recurring generation must use canonical work-order and appointment invariants.
9. Work-order completion and Finance will use one canonical versioned event.
10. Review and rebooking become explicit post-service lifecycle stages.
11. External mappings and idempotency are mandatory.
12. Dual-master customer, invoice and payment records remain prohibited.
13. Lifecycle failures must be resumable rather than hidden.
14. AI assistants invoke governed lifecycle commands, not raw model writes.
15. Scan 10 remains blocked until the paid CRM package is supplied.

---

# 48. Next scan

The next available report is:

```text
13-magicai-ai-providers-and-model-routing.md
```

It should map:

- Engine registry
- Entity registry
- Provider credentials
- User BYO keys
- Administrator keys
- Provider availability
- Model capabilities
- Cost calculation
- Shared-credit charging
- Fallback routing
- Streaming
- Vision, speech, image and video providers
- WorkCore model policy
- Company-specific AI rules
- Device/local providers
- Privacy and data residency
- AI operation attribution

---

# Evidence files

## MagicAI

```text
app/Events/ContactCapturedEvent.php
app/Observer/UserObserver.php
app/Http/Controllers/Auth/AuthenticationController.php
app/Http/Controllers/Api/AuthController.php
app/Domains/Marketplace/MarketplaceServiceProvider.php
app/Services/Common/MenuService.php
app/Http/Middleware/CheckTemplateTypeAndPlan.php
```

## WorkCore CRM and lifecycle

```text
app/Domains/WorkCore/System/Modules/CRM/
app/Domains/WorkCore/System/Modules/Premises/
app/Domains/WorkCore/System/Modules/RecurringServices/
app/Domains/WorkCore/System/Modules/Operations/
app/Domains/WorkCore/System/Modules/Scheduling/
app/Domains/WorkCore/System/Modules/Reviews/
app/Domains/WorkCore/System/Modules/Finance/
app/Domains/WorkCore/System/Actions/BusinessActionDispatcher.php
app/Domains/WorkCore/System/Actions/Infrastructure/DatabaseDomainEventRecorder.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120005_create_tz_crm_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120009_complete_tz_crm_foundation.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120010_create_tz_premises_table.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120012_create_tz_work_order_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120013_create_tz_appointment_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120023_create_tz_reviews_and_rebooking_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120035_create_tz_recurring_service_tables.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120054_create_tz_sales_pipeline_tables.php
```

---

# Evidence limitations

The paid MagicAI CRM, Chatbot, Booking, channel and Sales extension packages were not available.

This report does not prove their:

- Contact-capture behaviour
- Consent handling
- CRM conversion
- Estimate acceptance
- Customer portals
- Channel identity
- Booking-to-job conversion
- Event dispatch
- Tenant isolation

WorkCore findings are based on static source inspection. The complete lifecycle was not executed against a database, queue worker, payment provider or live customer channel.
