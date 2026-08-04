---
title: Titan Quotes Deep Product Design
status: Designed
created: 2026-08-02
updated: 2026-08-02
source_archives:
  - Extensions.zip
parent_portfolio: workcore/workcore-business-app-ideas.md
related_designs:
  - workcore/titan-inspect-deep-design.md
  - workcore/titan-pricebook-deep-design.md
product_family: WorkCore and Titan Zero
recommended_build_order: 3
---

# Titan Quotes

### Deep product design

**Complete enquiry-to-approval quoting, commercial proposal and job-conversion software for field and home service businesses.**

Titan Quotes turns the document, survey, pricing-rule, communications, booking, payment and public-sharing engines discovered in `Extensions.zip` into a complete standalone business application. It is not merely a PDF quote generator, price calculator, follow-up bot or digital-signature feature.

Its end-to-end outcome is:

> Capture what the customer needs, establish a defensible scope, calculate a governed commercial offer, present clear options, resolve questions and objections, secure acceptance and deposit, then create the correct operational work in WorkCore.

Titan Quotes can operate as a standalone application, but its strongest form is a WorkCore-native extension. Titan Quotes becomes the authority for quote workspaces, estimates, scope options, quote versions, customer-facing proposal documents, approvals, quote conversations, acceptance evidence and variations. WorkCore remains the authority for customers, properties, leads, jobs, appointments, staff, invoices, payments and operational delivery. Titan Pricebook remains the authority for price resolution. Titan Inspect remains the authority for inspection evidence and findings.

---

## 1. Product thesis

Most field-service quoting software handles only the visible middle of the process: enter line items, generate a PDF and email it.

The real quote lifecycle begins earlier and ends later:

1. an enquiry arrives through a phone call, web form, referral, WhatsApp message or site visit;
2. the business determines whether enough information exists to price the work;
3. missing scope is collected through questions, photographs, measurements or inspection;
4. internal costs and risk are evaluated;
5. a customer-facing offer is constructed without exposing internal commercial data;
6. staff review price, margin, exclusions and risk;
7. the customer opens, compares, questions, revises, accepts or declines the offer;
8. deposits, signatures and booking constraints are resolved;
9. the accepted commercial promise is converted into operational work;
10. actual job results reveal whether the quote was commercially correct.

Weak quoting systems lose value at every transition:

- enquiries are retyped from communication channels;
- scope remains vague or contradictory;
- old price lists are used;
- labour, travel or materials are omitted;
- internal estimates and customer quotes are confused;
- quote revisions overwrite history;
- customer questions live outside the quote record;
- follow-up is either forgotten or annoyingly indiscriminate;
- accepted scope does not cleanly become a job;
- variations are approved informally;
- estimated margin is never compared with actual delivery.

Titan Quotes must therefore be a **commercial agreement workflow**, not a document generator.

### Product promise

**Scope it clearly. Price it safely. Present it convincingly. Approve it properly. Turn it into work.**

### Primary commercial outcomes

- faster enquiry-to-quote time;
- fewer site visits for work that can be scoped remotely;
- fewer omitted tasks, materials and exclusions;
- consistent use of approved pricing;
- higher quote acceptance without uncontrolled discounting;
- clearer customer choices and fewer misunderstandings;
- less time spent chasing quotes manually;
- auditable approvals and revisions;
- faster deposits and scheduling;
- accurate conversion from accepted quote to job;
- stronger recovery of legitimate variations;
- measurable estimated-versus-actual margin;
- reusable quoting knowledge across estimators, branches and verticals.

---

## 2. Application boundary

Quoting touches CRM, inspections, pricing, scheduling, payments and operations. Without clear authority boundaries, Titan Quotes would duplicate WorkCore and create contradictory records.

### Titan Quotes owns

- quote workspaces;
- internal estimates and cost snapshots;
- scope sections and customer-facing scope items;
- quote options, packages and add-ons;
- quote templates and branded document layouts;
- quote versions and revision history;
- internal reviews and commercial approvals;
- customer-facing public quote sessions;
- quote-specific questions, comments and objections;
- follow-up and Quote Rescue state;
- decline reasons;
- acceptance evidence and signed terms snapshots;
- deposit requests as commercial requirements;
- quote-to-job conversion instructions;
- variations and change-order approval records;
- quote analytics and conversion attribution;
- quote-specific audit history.

### Titan Pricebook owns

- service definitions;
- cost formulas;
- rate cards;
- packages and governed add-ons;
- branch, territory and contract pricing;
- price floors and margin rules;
- promotion eligibility;
- deterministic price resolutions;
- published pricebook versions.

Titan Quotes stores an immutable snapshot of each price resolution used in a quote version. It does not silently recalculate an already issued quote when the pricebook changes.

### Titan Inspect owns

- inspection templates;
- evidence capture;
- measurements and observations;
- findings and severity;
- AI model assessments;
- human inspection reviews;
- inspection reports and certificates.

Titan Quotes may import approved findings or scope suggestions, but it does not alter source evidence.

### WorkCore remains authoritative for

- customers and contacts;
- properties and service locations;
- leads and opportunities;
- workers, teams and skills;
- jobs, work orders and appointments;
- tasks and projects;
- inventory quantities and reservations;
- invoices, credits and payments;
- actual labour, material and job-cost records;
- operational service history.

### Authority when Titan Quotes is installed

WorkCore may already contain a generic quote table or quote screen. When Titan Quotes is enabled, that generic capability becomes a projection and integration surface rather than a competing quote authoring authority.

The accepted commercial snapshot must be synchronised into WorkCore with:

- Titan quote ID;
- accepted quote-version ID;
- accepted total and tax;
- selected option and add-ons;
- deposit requirement and status;
- accepted scope summary;
- exclusions and assumptions;
- customer acceptance evidence reference;
- pricebook-version reference;
- inspection references;
- variation policy;
- job-creation instructions.

### Standalone mode

In standalone mode, Titan Quotes may maintain lightweight customer and property references, but they remain integration identities rather than a second CRM. Full customer management, dispatch, invoicing and job costing should remain optional connected capabilities.

---

## 3. Target users and jobs to be done

### Owner-operator

- capture an enquiry quickly;
- turn voice notes and photos into a structured scope;
- calculate a profitable price without rebuilding a spreadsheet;
- issue a professional quote from a phone;
- know which quotes need attention;
- secure acceptance and deposit;
- convert accepted work into the schedule.

### Estimator or salesperson

- collect complete scope information;
- reuse service and package templates;
- compare options and margins;
- request approval for exceptions;
- respond to customer questions without losing context;
- manage revisions without overwriting earlier commitments;
- track acceptance by service, price and customer segment.

### Field technician

- capture scope during a site visit;
- add photographs, measurements and annotations;
- identify additional work;
- request an office review;
- create a controlled variation after acceptance;
- avoid promising unsupported prices or dates.

### Sales manager

- define quote workflows and approval thresholds;
- review low-margin, high-risk or unusual quotes;
- monitor conversion and response speed;
- identify estimators needing support;
- understand why quotes are won or lost;
- prevent uncontrolled discounting.

### Operations manager

- confirm accepted work can be fulfilled;
- identify required skills, duration, equipment and materials;
- ensure the accepted promise converts accurately into jobs;
- compare quoted assumptions with delivery reality;
- expose operational conditions that should change future quoting.

### Customer

- understand exactly what is included and excluded;
- compare meaningful options;
- ask questions against specific sections;
- request a change;
- choose extras and appointment preferences;
- accept and sign without creating an account;
- pay a deposit securely;
- retain an accessible copy of the agreed scope.

### Franchise or network operator

- publish approved quote templates;
- govern pricing and commercial language;
- allow controlled local adaptation;
- compare conversion and margin by location;
- audit exceptions and discount behaviour;
- maintain brand and legal consistency.

---

## 4. Quote modes and field-service use cases

Titan Quotes must support distinct commercial document types rather than treating every offer as the same object.

### 4.1 Preliminary estimate

A non-binding range based on incomplete information.

Use when:

- the customer wants an early indication;
- final measurements are unavailable;
- hidden conditions may materially change price;
- a site inspection is still required.

Required controls:

- explicit estimate status;
- assumptions and uncertainty;
- range or allowance display;
- validity period;
- conversion path to inspected fixed quote.

### 4.2 Fixed-price quote

A defined scope at a defined price subject to stated assumptions and exclusions.

Best for:

- cleaning packages;
- painting;
- landscaping;
- pest treatments;
- maintenance work;
- straightforward repairs;
- installation work with established scope.

### 4.3 Time-and-material estimate

An estimated labour and material basis with rates, caps or approval thresholds.

Best for:

- fault finding;
- emergency work;
- uncertain repairs;
- restoration;
- investigative maintenance.

### 4.4 Optioned proposal

A customer chooses between structured alternatives.

Examples:

- essential, recommended and complete;
- repair, restore or replace;
- one-off, quarterly or annual plan;
- standard, premium and compliance-grade service.

### 4.5 Recurring-service proposal

Defines initial work plus a recurring plan, frequency, inclusions, price review terms and cancellation rules.

It should hand accepted recurring terms to Titan Service Plans rather than implementing subscription operations inside Titan Quotes.

### 4.6 Commercial proposal

A longer-form offer containing:

- executive summary;
- service methodology;
- site or portfolio scope;
- staffing assumptions;
- mobilisation plan;
- service levels;
- reporting commitments;
- pricing schedules;
- exclusions;
- implementation timeline.

This mode reuses Canvas, CreativeSuite and AiPresentation document engines while maintaining structured commercial data beneath the presentation.

### 4.7 Variation or change order

A post-acceptance commercial amendment linked to the original quote and WorkCore job.

It must identify:

- original accepted scope;
- reason for change;
- additional or removed work;
- evidence;
- price and time effect;
- revised completion expectations;
- customer approval.

### 4.8 Emergency authorisation

A restricted approval for urgent work up to a capped amount or time limit, followed by a full variation or quote.

This requires explicit authority and must never be inferred from a casual message.

---

## 5. Core domain model

### QuoteWorkspace

The persistent commercial workspace for one customer need or opportunity.

Key fields:

- company and branch;
- WorkCore lead/opportunity reference;
- customer and property references;
- source channel;
- assigned estimator and team;
- service category;
- desired outcome;
- urgency;
- inspection requirement;
- status;
- primary currency and tax context;
- linked conversations and evidence;
- created and last-activity timestamps.

A workspace may contain several internal estimates and quote versions, but only one current customer offer per issue path unless an explicit alternative-offer strategy is used.

### EnquirySnapshot

An immutable capture of the information available at intake:

- original message or call summary;
- submitted form fields;
- source campaign;
- uploaded media;
- requested dates;
- stated budget;
- customer concerns;
- consent and communication preferences.

### ScopeSection

A logical grouping such as:

- property-wide assumptions;
- room or zone;
- asset;
- service phase;
- optional package;
- exclusion group.

### ScopeItem

A structured unit of proposed work.

Fields include:

- service-definition reference;
- description;
- quantity and unit;
- location or asset;
- inclusions;
- exclusions;
- assumptions;
- evidence references;
- duration estimate;
- required skill and equipment hints;
- customer visibility;
- option membership;
- source and confidence.

### InternalEstimate

The internal commercial model used to prepare an offer.

It contains:

- pricebook context;
- component quantities;
- internal cost;
- overhead allocation;
- risk allowances;
- margin and contribution;
- discounts or exceptions;
- approval requirements;
- estimator notes;
- resolution snapshots.

InternalEstimate data is never directly exposed to the customer unless the business explicitly chooses transparent breakdowns.

### QuoteOption

A selectable commercial alternative.

Fields:

- title and positioning;
- included scope items;
- optional add-ons;
- total and tax;
- expected duration;
- warranty or service terms;
- recommended flag;
- comparison highlights;
- availability constraints.

### QuoteAddOn

An optional scope or service selected alongside an option.

Add-ons must have pricebook-backed eligibility and cannot create incompatible combinations.

### QuoteVersion

An immutable issued or reviewable commercial snapshot.

It contains:

- version number;
- status;
- source estimate;
- rendered document model;
- customer-visible scope;
- selected price-resolution snapshots;
- option and add-on definitions;
- terms snapshot;
- validity period;
- required deposit;
- booking conditions;
- approval evidence;
- content hash;
- issue timestamp;
- superseded-by reference.

### QuoteDocument

A presentation representation of a QuoteVersion.

Formats may include:

- responsive web proposal;
- PDF;
- Tiptap rich document;
- Konva visual scope board;
- commercial presentation;
- accessible plain-text summary.

The document does not replace structured quote records.

### QuoteApproval

An internal decision record for:

- margin exception;
- discount;
- unusual terms;
- high contract value;
- non-standard warranty;
- extended validity;
- deposit waiver;
- operational capacity exception;
- legal or compliance wording.

### QuoteInteraction

A customer or staff interaction linked to the quote:

- open;
- page or section view;
- question;
- comment;
- revision request;
- option selection;
- add-on selection;
- call;
- WhatsApp or email message;
- reminder;
- decline;
- acceptance attempt;
- deposit attempt.

### QuoteObjection

A structured interpretation of a customer concern:

- price;
- timing;
- scope;
- trust;
- competitor;
- payment;
- internal approval delay;
- no longer required;
- unable to contact;
- other.

AI may suggest classification, but staff or explicit customer responses confirm it when decisions depend on the category.

### PublicQuoteSession

A secure, scoped customer session containing:

- opaque token hash;
- quote-version reference;
- allowed actions;
- expiry;
- customer identity challenge policy;
- revocation status;
- access history;
- rate limits.

### AcceptanceRecord

The evidence that a customer accepted a specific QuoteVersion.

It includes:

- accepted version hash;
- selected option and add-ons;
- signer name and role;
- customer/account identity;
- signature method;
- timestamp;
- IP and user-agent evidence where lawful;
- OTP or email verification when required;
- accepted terms snapshot;
- consent statements;
- deposit requirement and result;
- acceptance certificate hash.

### DeclineRecord

Captures:

- customer-selected reason;
- free-text explanation;
- selected competitor or alternative where voluntarily supplied;
- whether future contact is permitted;
- whether the opportunity may be revisited later.

### QuoteConversion

An idempotent record of the transition to operational systems:

- WorkCore quote projection ID;
- job/work-order IDs;
- task IDs;
- schedule request ID;
- inventory reservation requests;
- invoice or deposit invoice ID;
- service-plan proposal ID;
- conversion status and errors.

### Variation

A post-acceptance quote child with its own versions, approvals and acceptance evidence.

### QuoteTemplate

A reusable structure defining:

- document sections;
- content blocks;
- scope layouts;
- option style;
- mandatory terms;
- signature and deposit policy;
- vertical and branch applicability;
- language and branding.

---

## 6. State models

Titan Quotes needs explicit state machines. A single `status` column with arbitrary values will produce invalid transitions and confusing automation.

### 6.1 Workspace state

```text
new_enquiry
→ qualifying
→ awaiting_customer_information
→ inspection_required
→ scoping
→ estimating
→ internal_review
→ ready_to_issue
→ active_quote
→ accepted | declined | expired | withdrawn
→ converted
```

A workspace may loop between `scoping`, `estimating` and `internal_review`.

### 6.2 Quote-version state

```text
draft
→ pending_approval
→ approved
→ issued
→ viewed
→ under_discussion
→ accepted | declined | expired | withdrawn | superseded
```

Rules:

- only approved versions may be issued;
- issued versions are immutable;
- revisions create a new version;
- superseded links remain readable but cannot be accepted unless policy explicitly permits;
- acceptance is idempotent;
- a version cannot be both accepted and superseded.

### 6.3 Approval state

```text
not_required | pending → approved | rejected | changes_requested | expired
```

### 6.4 Deposit state

```text
not_required | pending → authorised | paid | failed | cancelled | refunded
```

Titan Quotes records commercial requirement and payment references. ZeroPay or another payment system owns actual money movement.

### 6.5 Conversion state

```text
not_started → queued → partially_created → completed | failed | compensation_required
```

Partial creation must be recoverable without duplicating jobs, tasks or invoices.

### 6.6 Variation state

```text
draft → pending_internal_approval → issued → accepted | declined | expired
→ applied_to_job
```

---

## 7. Enquiry capture and qualification

Titan Quotes should accept enquiries without forcing every channel to become a separate workflow.

### Intake sources

- WorkCore lead or customer record;
- manual staff entry;
- customer portal;
- public quote-request form;
- website form;
- email connector;
- WhatsApp through MarketingBot-derived communications infrastructure;
- phone transcript or structured intake from PhoneCallAgent;
- social-message handoff;
- referral link;
- imported opportunity;
- repeat-service recommendation from WorkCore or Titan Assets.

### Intake principles

1. Preserve the original message and source.
2. Resolve the customer identity without silently merging uncertain contacts.
3. Link the correct property or create a provisional location reference.
4. Capture communication consent separately from operational contact authority.
5. Avoid requiring complete scope before saving the enquiry.
6. Record which facts came from the customer, staff, inspection or AI inference.

### Qualification questions

Question sets can be driven by:

- service type;
- property type;
- location;
- asset type;
- customer segment;
- risk;
- quote mode;
- evidence already available.

Examples:

- number and type of rooms;
- surface or material;
- dimensions;
- current condition;
- access restrictions;
- parking and travel conditions;
- pets or occupants;
- urgency;
- utilities availability;
- preferred times;
- photographs or video;
- known hazards;
- whether a site inspection is possible.

### Guided self-survey

The `OnboardingPro` survey concept can seed a guided customer intake, but its current model is far too simple: one globally active survey and a score per user. Titan Quotes needs versioned, tenant-scoped, branching questionnaires with structured answers, evidence, validation and quote-workspace linkage.

### Call intake

The PhoneCallAgent engine provides useful foundations:

- inbound and outbound call handling;
- transcripts;
- training from text, files and URLs;
- call history and tags;
- booking-provider integration.

For Titan Quotes, it should produce a structured intake proposal, not directly create or alter commercial records. Staff review the captured customer, property, requested service, urgency and appointment preferences before committing them.

### Qualification outcomes

- ready for remote scope;
- customer information required;
- inspection required;
- not a supported service;
- outside territory;
- capacity unavailable;
- refer to another provider;
- unsafe or emergency escalation;
- duplicate opportunity;
- customer not proceeding.

---

## 8. Scope Builder

The Scope Builder is the centre of Titan Quotes. A quote cannot be trusted if the scope remains free text without structure.

### Scope inputs

- service templates;
- customer answers;
- staff notes;
- voice transcription;
- photographs and videos;
- measurements;
- Titan Inspect findings;
- Titan Property Twin zones and assets;
- prior WorkCore job history;
- asset-service recommendations;
- imported tender or request documents;
- pricebook service definitions.

### Scope structure

A scope should support:

- property-wide assumptions;
- zones, rooms or areas;
- assets and components;
- tasks and service operations;
- quantities and units;
- sequence or phases;
- required evidence;
- customer responsibilities;
- exclusions;
- provisional allowances;
- optional work;
- dependencies;
- access requirements;
- disposal or environmental requirements;
- warranty boundaries.

### Scope provenance

Every structured item must record its source:

- customer supplied;
- staff observed;
- inspection confirmed;
- imported from template;
- imported from prior job;
- AI suggested;
- manually edited.

AI suggestions remain visually distinct until a human accepts them.

### Missing-information detection

Titan Quotes can identify incomplete scope using deterministic rules first:

- required quantity missing;
- required area or asset absent;
- conflicting access information;
- service requires inspection but none linked;
- evidence requirement not satisfied;
- material selection unresolved;
- tax context missing;
- customer identity uncertain;
- appointment constraints absent where price depends on timing.

AI may then suggest additional questions from the notes and evidence.

### Scope comparison

When a revision is requested, the system should show:

- added work;
- removed work;
- changed quantities;
- changed assumptions;
- changed exclusions;
- price effect;
- duration effect;
- approval effect.

---

## 9. Internal estimate workspace

An estimate is an internal commercial model. A quote is a customer-facing offer. They must remain separate.

### Estimate components

- labour quantities and rates;
- material quantities, waste and cost;
- equipment charges and cost;
- subcontractor allowance;
- travel and mobilisation;
- disposal;
- overhead allocation;
- risk contingency;
- discounts and promotions;
- tax;
- expected gross margin;
- expected contribution;
- cash-flow and deposit requirements;
- schedule assumptions.

### Cost snapshots

When an estimate is submitted for approval or used to issue a quote, Titan Quotes stores:

- pricebook version;
- price-resolution IDs;
- supplier-cost references where applicable;
- labour-rate references;
- formula inputs;
- resolved outputs;
- exception decisions;
- timestamp and actor.

This allows later comparison with actual delivery without rewriting history.

### Risk allowances

Risk should be explicit rather than hidden in arbitrary markups.

Examples:

- uncertain hidden damage;
- difficult access;
- weather exposure;
- customer-provided measurements;
- contamination severity;
- after-hours work;
- uncertain material condition;
- subcontractor availability;
- disposal uncertainty.

Each allowance should state whether it is:

- included in fixed price;
- provisional;
- subject to variation;
- excluded;
- converted into an option.

### Estimate review

Review triggers may include:

- margin below threshold;
- total above estimator authority;
- discount above authority;
- non-standard terms;
- unusual risk;
- no inspection for high-value work;
- customer contract exception;
- manual price override;
- pricing engine warning;
- capacity conflict;
- unsupported service combination.

---

## 10. Titan Pricebook integration

Titan Quotes must consume Titan Pricebook rather than recreate pricing logic.

### Price-resolution request

A request includes:

- company and branch;
- customer and contract references;
- property and territory;
- service and variant;
- quantities and units;
- schedule context;
- option or package context;
- customer eligibility;
- tax context;
- requested promotion;
- estimator authority;
- resolution timestamp.

### Price-resolution response

Titan Pricebook returns:

- published pricebook version;
- resolved components;
- internal cost and selling-price information according to permission;
- margin and contribution;
- applied rules;
- rejected promotions and reasons;
- floor and approval status;
- internal explanation;
- customer-safe explanation where configured;
- resolution hash and ID.

### Snapshot rule

Issued QuoteVersions never depend on live recalculation. They store the accepted price-resolution snapshot.

A revision may choose to:

- preserve prior rates;
- use current pricebook rates;
- selectively preserve agreed lines;
- require a manager decision.

The choice must be explicit and audited.

### Pricebook unavailable

If Titan Pricebook is temporarily unavailable:

- users may continue editing scope;
- previously resolved draft prices may be displayed as stale;
- new prices cannot be represented as current;
- an issued quote cannot be sent unless policy permits a verified offline resolution cache;
- every offline resolution must be revalidated before issue.

---

## 11. Options, packages and add-ons

Options are a major conversion feature, but only when they represent meaningful choices rather than manipulative price anchoring.

### Option patterns

- essential, recommended and complete;
- repair versus replacement;
- one-off versus recurring;
- standard versus premium finish;
- immediate remediation versus staged work;
- customer-supplied versus business-supplied materials;
- weekday versus after-hours service;
- basic report versus compliance evidence package.

### Option rules

- every option must be internally complete;
- excluded items must be clear;
- recommended labels require a documented rationale;
- prices must be independently valid;
- add-ons must obey compatibility rules;
- customer selections cannot produce an operationally impossible combination;
- margin rules apply to each selectable configuration.

### Dynamic selection

The customer portal can update totals as options and add-ons change, but it must not silently change the issued QuoteVersion.

A valid model is:

- QuoteVersion defines the full permitted selection space;
- customer selections create a ConfigurationSnapshot;
- final acceptance binds the selected configuration to the version hash.

### Configuration integration

Titan Configurator may provide visual finish, colour or material selections. Titan Quotes records the approved configuration reference and commercial effect without owning image-generation or material-rendering logic.

---

## 12. Visual and branded quote composition

Titan Quotes needs structured commercial records beneath a flexible presentation layer.

### Document blocks

- cover and customer summary;
- service outcome;
- property or site details;
- visual scope;
- option comparison;
- line-item summary;
- timeline and appointment assumptions;
- photographs and annotations;
- inclusions;
- exclusions;
- customer responsibilities;
- warranty;
- payment schedule;
- terms;
- acceptance panel.

### Donor engines

`Canvas` provides a Tiptap content-persistence concept, but its controller finds messages by raw ID without proving ownership. Titan Quotes must use quote-scoped authorisation, versioned document models and sanitised rich content.

`CreativeSuite` provides editable design documents and media handling. It can seed branded visual quote layouts, but pricing and structured scope cannot live only inside visual JSON.

`CreativeSuiteAITemplate` can generate editable Konva JSON. In Titan Quotes it can support:

- annotated room or property diagrams;
- selectable work areas;
- visual scope maps;
- before/after concept panels;
- package comparison boards.

`CreativeSuiteAnnotations` can support coordinate-grounded evidence imported from Titan Inspect.

`AiPresentation` can generate an optional presentation for high-value commercial proposals. It should remain a derivative output, not the authoritative quote.

### Accessibility

Every visual quote requires:

- semantic web representation;
- readable plain-text equivalent;
- keyboard navigation;
- contrast and text-size compliance;
- PDF tagging where feasible;
- accessible acceptance controls.

---

## 13. Internal collaboration and approvals

### Roles

- estimator;
- salesperson;
- field scoper;
- pricing approver;
- operations reviewer;
- legal/compliance reviewer;
- branch manager;
- finance reviewer;
- network template administrator.

### Comment types

- general note;
- scope question;
- pricing concern;
- operational constraint;
- legal-term issue;
- customer-visible suggestion;
- required change;
- approval decision.

### Approval policies

Approval may depend on:

- total value;
- margin;
- contribution;
- discount;
- deposit waiver;
- payment terms;
- contract duration;
- warranty;
- branch;
- customer risk;
- service type;
- estimator role;
- unusual exclusions;
- required capacity.

### Separation of duties

For high-value or regulated work:

- the estimator cannot approve their own commercial exception;
- the approver cannot alter an issued version without creating a revision;
- acceptance evidence cannot be deleted by sales staff;
- deposit status cannot be manually marked paid without finance authority or payment evidence.

### Approval expiry

Approval may expire when:

- scope changes materially;
- price changes;
- validity date changes;
- terms change;
- the pricebook version changes and policy requires refresh;
- required deposit changes;
- customer or property changes.

---

## 14. Versioning and revision management

### Immutable issue rule

Once a quote is issued:

- its scope, price, options, terms and validity are immutable;
- corrections require a new QuoteVersion;
- previous versions remain available to authorised users;
- customer links clearly show when a version is superseded;
- audit history shows who requested and approved the revision.

### Revision reasons

- customer scope change;
- new inspection evidence;
- estimator correction;
- price validity expired;
- appointment or access change;
- unavailable material;
- alternative package;
- commercial negotiation;
- compliance requirement;
- internal risk review.

### Version comparison

The interface should show:

- scope additions and removals;
- quantity changes;
- option changes;
- price and tax changes;
- margin changes;
- deposit changes;
- terms changes;
- validity changes;
- changed evidence references;
- changed approvals.

### Renewal and reissue

An expired quote can be renewed only by creating a new version. The system determines whether pricing must be refreshed through Titan Pricebook.

---

## 15. Secure customer quote portal

The portal is a customer decision workspace, not merely a PDF viewer.

### Customer capabilities

- view the current quote;
- compare options;
- select permitted add-ons;
- inspect photographs and diagrams;
- ask a question against a specific section;
- request a revision;
- choose appointment preferences;
- download PDF and accessible summary;
- accept or decline;
- sign;
- pay a deposit;
- return later without creating a full account;
- manage communication preferences.

### Identity and access

Access policy can vary by risk:

- opaque signed link for low-value residential work;
- email challenge or OTP;
- authenticated customer portal;
- named authorised signatory for commercial accounts;
- multi-party approval for strata, property or corporate customers.

### Public-link redesign

`ChatShare` demonstrates a simple public-link pattern, but it cannot be reused as-is. Its current model stores raw URLs and chat/message IDs, has no user/company ownership fields, and validates existence of the URL before loading records by raw IDs.

Titan Quotes requires:

- random high-entropy tokens;
- token hashes at rest;
- quote-version and company scope;
- explicit allowed actions;
- expiry and revocation;
- access logs;
- rate limiting;
- optional identity challenge;
- no sequential record identifiers in the public route;
- safe caching headers;
- redaction of internal data.

### Portal concurrency

When staff issue a revision while a customer has the portal open:

- the existing session is notified;
- acceptance of a superseded version is blocked by default;
- the customer is redirected to the current version;
- selected options may be preserved only if still compatible.

---

## 16. Quote conversations and objection handling

Quote-specific communication should remain attached to the commercial record.

### Channels

- portal comments;
- email;
- WhatsApp;
- SMS where connected;
- phone-call transcripts;
- internal notes;
- customer-service handoff.

### Communications model

The MarketingBot engine provides useful patterns for contacts, conversation history, channels and message analytics. Titan Quotes should extract those patterns into a shared communications service rather than duplicate a separate quote inbox.

Every external message must record:

- channel;
- sender and recipient identity;
- quote/workspace reference;
- template or free-text source;
- consent basis;
- delivery state;
- content or encrypted/redacted reference;
- attachments;
- staff owner;
- escalation state.

### AI assistance

AI may:

- summarise the conversation;
- classify likely objection;
- find the relevant quote section;
- draft a grounded response from approved company knowledge;
- suggest questions for the estimator;
- detect sentiment or escalation language;
- recommend a human callback.

AI must not:

- change price;
- alter terms;
- promise dates;
- approve a discount;
- concede liability;
- issue a revision;
- mark a quote accepted.

### High-risk escalation

Immediate human review when messages indicate:

- legal or insurance action;
- injury or safety issue;
- discrimination or harassment;
- property damage dispute;
- refund demand;
- aggressive or threatening language;
- uncertainty about the authorised signatory;
- material contradiction in scope;
- request outside approved services.

---

## 17. Quote Rescue

Quote Rescue is a major selling feature inside Titan Quotes, not a standalone application.

Its purpose is to identify the real reason an otherwise valid quote has stalled and take a proportionate, governed action.

### Signals

- quote not opened;
- quote opened but not reviewed fully;
- repeated opens;
- option selected but not accepted;
- deposit started but abandoned;
- customer asked a question;
- revision requested;
- expiry approaching;
- accepted appointment no longer available;
- customer stopped responding;
- explicit price, timing or scope objection;
- internal decision maker not yet involved;
- commercial customer awaiting approval.

### Rescue actions

- resend or confirm correct recipient;
- provide a concise summary;
- answer a documented question;
- offer a callback;
- provide available appointment choices;
- clarify an inclusion or exclusion;
- generate a smaller or alternative option for staff approval;
- request missing information;
- extend validity with approval;
- remind about deposit;
- stop contact;
- mark as nurture or future follow-up.

### Rescue guardrails

- consent and channel preference;
- quiet hours;
- contact-frequency caps;
- no fabricated scarcity;
- no automatic discount unless a published, eligible promotion applies;
- no hidden price discrimination;
- no contact after opt-out or explicit decline;
- no AI representation that it is a human estimator;
- all price and term changes require a new version;
- rescue stops immediately on acceptance, decline, withdrawal or dispute escalation.

### Follow-up policy examples

#### Residential fixed quote

- issue confirmation;
- one reminder after configured inactivity;
- expiry reminder;
- optional final close message.

#### High-value commercial proposal

- salesperson task rather than automated pressure;
- stakeholder and approval-stage tracking;
- scheduled human follow-up;
- no casual discount suggestions.

### Measuring Quote Rescue

- rescued accepted value;
- gross margin of rescued quotes;
- median time to acceptance;
- customer opt-out rate;
- complaint rate;
- acceptance by action;
- false-positive or unnecessary follow-up rate;
- percentage requiring human intervention.

---

## 18. Acceptance, signature and deposit

### Acceptance requirements

A valid acceptance binds:

- quote-version hash;
- selected option;
- selected add-ons;
- total and tax;
- terms version;
- customer identity;
- signer authority;
- acceptance timestamp;
- required consent statements.

### Signature methods

- typed name plus confirmation;
- drawn signature;
- authenticated account acceptance;
- email OTP;
- SMS OTP;
- commercial signatory workflow;
- multi-party approval where required.

The business chooses a policy based on quote value, customer type and risk. Titan Quotes should not claim a particular signature method is legally sufficient for every jurisdiction or contract type.

### Deposit policy

Deposit requirements may be:

- fixed amount;
- percentage;
- milestone schedule;
- material procurement amount;
- waived by approved customer contract;
- not required.

### Payment integration

`CheckoutRegistration` supplies useful Stripe and PayPal payment-intent and checkout concepts, but it is designed around SaaS registration and subscription plans. Titan Quotes should use a shared payment orchestration interface with:

- ZeroPay;
- Stripe;
- PayPal;
- bank transfer;
- PayID;
- customer account terms;
- external payment link.

Titan Quotes records:

- required amount;
- provider reference;
- payment state;
- receipt reference;
- whether booking may proceed.

The payment platform owns:

- transaction execution;
- refunds;
- settlement;
- payment-method security;
- reconciliation.

### Acceptance without immediate deposit

Policy may permit:

- accepted, awaiting deposit;
- provisional schedule hold;
- confirmed job only after payment;
- credit-account acceptance;
- manager override.

These states must remain explicit.

---

## 19. Scheduling and capacity integration

Titan Quotes should not become a dispatch calendar, but the customer offer must respect operational reality.

### Before issue

Titan Quotes may request from WorkCore or Titan Schedule:

- indicative availability;
- branch capacity;
- required skill availability;
- service duration constraints;
- territory support;
- seasonal or after-hours availability;
- equipment constraints.

### Customer appointment choices

The quote can present:

- non-binding preferred dates;
- reservable appointment windows;
- appointment after deposit;
- inspection date;
- mobilisation period for commercial work.

### Booking lock

A customer selecting a time should create a temporary reservation with expiry. Acceptance and payment then confirm or release it.

### Availability changes

If availability changes before acceptance:

- price remains governed by the issued version unless timing affects price;
- the customer is shown updated options;
- any material timing or price change creates a revision;
- the system does not promise a slot merely because it was previously displayed.

### PhoneCallAgent booking integration

The donor booking provider interface supports Calendly and Cal.com. Titan Quotes should replace external-calendar-specific authority with WorkCore/Titan Schedule as the primary source, while retaining pluggable external adapters for businesses that have not adopted Titan Schedule.

---

## 20. Quote-to-WorkCore conversion

Acceptance is not complete until the commercial promise becomes reliable operational work.

### Conversion outputs

Depending on quote type, Titan Quotes may request WorkCore to create:

- one or more jobs/work orders;
- phases or visits;
- tasks and checklists;
- property and access instructions;
- customer communication requirements;
- required skills;
- estimated labour duration;
- material requirements;
- equipment requirements;
- subcontractor requirements;
- inspection or quality checkpoints;
- invoice or deposit schedule;
- service-plan proposal;
- warranty record;
- linked evidence pack.

### Mapping policy

Every scope item should define how it maps to operations:

- job line;
- task;
- checklist item;
- material requirement;
- schedule duration;
- evidence requirement;
- quality checkpoint;
- customer deliverable.

### Idempotency

Conversion requests use stable idempotency keys derived from:

- company;
- accepted quote version;
- selected configuration;
- conversion attempt type.

Retries must not duplicate jobs or invoices.

### Partial failure

If job creation succeeds but invoice creation fails:

- the conversion is `partially_created`;
- created identifiers are preserved;
- retries target only missing outputs;
- staff receive a clear remediation task;
- the quote remains accepted;
- no duplicate customer communication is sent.

### Reconciliation

A periodic reconciliation checks:

- accepted quotes without completed conversion;
- WorkCore jobs missing quote links;
- deposit requirements inconsistent with payment state;
- accepted totals inconsistent with WorkCore projections;
- variations not applied to operational records.

---

## 21. Variations and change orders

A full quotes app must own the commercial variation lifecycle.

### Variation sources

- customer requests additional work;
- hidden conditions discovered;
- Titan Inspect finding;
- technician evidence;
- material substitution;
- access delay;
- quantity increase;
- regulation or safety requirement;
- scope removal;
- schedule change with commercial effect.

### Variation workflow

```text
potential change detected
→ evidence captured
→ classify inside or outside accepted scope
→ calculate commercial and time effect
→ internal approval if required
→ issue variation
→ customer accepts or declines
→ update WorkCore job and invoice plan
```

### Scope comparison

The customer sees:

- relevant original scope;
- reason for change;
- added, removed or altered work;
- supporting evidence;
- price change;
- tax;
- time impact;
- revised completion expectation;
- action required.

### Variation Sentinel

The earlier Variation Sentinel concept belongs here as an optional AI-assisted feature.

It may compare job evidence and technician notes against the accepted quote to flag possible out-of-scope work. It must not autonomously accuse the customer, stop safe emergency work, or issue a variation without human review.

### Emergency work

Where immediate action is required to prevent harm or damage:

- capture evidence;
- follow approved emergency-authority limits;
- record who authorised the work;
- issue formal variation as soon as practical;
- distinguish safety action from discretionary additional work.

---

## 22. Expiration, withdrawal and reopening

### Validity

Each issued version defines:

- issue date;
- valid-until date;
- pricing validity;
- appointment-hold validity;
- material-price assumptions;
- promotion expiry;
- conditions requiring refresh.

### Expiration

On expiry:

- acceptance is blocked unless grace policy permits;
- active booking holds are released;
- deposit attempts are disabled;
- customer is offered a request-for-renewal action;
- staff are notified according to opportunity value.

### Withdrawal

A quote may be withdrawn because:

- pricing error;
- service no longer available;
- unsafe or unsupported scope;
- capacity constraint;
- customer-credit issue;
- duplicate or fraudulent request;
- legal/compliance concern.

Withdrawal requires a reason and preserves history.

### Reopening

A declined or expired opportunity can be reopened as a new version or new workspace according to policy. Historical decline and pricing remain visible.

---

## 23. Offline and mobile behaviour

Field estimators need mobile and offline support, but commercial issue and acceptance require stronger consistency than ordinary note capture.

### Available offline

- open assigned quote workspaces;
- customer/property summary cache;
- draft scope editing;
- photographs, voice notes and measurements;
- approved service-template cache;
- previous price-resolution display marked with freshness;
- internal notes;
- draft estimate inputs;
- inspection linkage cache.

### Restricted offline

By default, offline users cannot:

- issue a final quote;
- approve a commercial exception;
- accept a customer signature on behalf of the customer;
- mark a deposit paid;
- confirm a live appointment;
- use an unverified current price.

### Offline price resolution

Titan Pricebook Pro or Network may provide a signed offline resolver bundle containing:

- published version;
- formulas;
- permitted services;
- territory/branch rules;
- expiry;
- signature.

Offline calculations remain drafts until server revalidation unless company policy allows low-risk issuance.

### Conflict handling

- append-only evidence merges automatically;
- scope-line edits use field-level conflict detection;
- issued versions never merge;
- server approval decisions take precedence;
- conflicting drafts create a review screen rather than last-write-wins loss;
- deleted drafts use tombstones.

### Device security

- encrypted local storage;
- device registration;
- remote session revocation;
- minimal cached customer data;
- automatic expiry;
- biometric/PIN support where available;
- no provider secrets on field devices.

---

## 24. WorkCore and portfolio integration contract

### Inputs consumed from WorkCore

- customer and contact references;
- property and site references;
- lead/opportunity context;
- service history;
- staff and branch identity;
- schedule/capacity hints;
- actual job costs after delivery;
- invoice and payment status;
- communication preferences;
- tax and company settings.

### Commands sent to WorkCore

- create/update lead projection;
- create accepted quote projection;
- create job/work order;
- create tasks and phases;
- request schedule reservation;
- request inventory/material reservation;
- create deposit or invoice schedule;
- attach accepted document and evidence references;
- apply accepted variation;
- create follow-up task;
- request service-plan creation.

### Events published by Titan Quotes

- `quote.workspace.created`
- `quote.scope.ready`
- `quote.approval.requested`
- `quote.version.approved`
- `quote.version.issued`
- `quote.version.viewed`
- `quote.question.received`
- `quote.revision.requested`
- `quote.option.selected`
- `quote.accepted`
- `quote.declined`
- `quote.expired`
- `quote.deposit.paid`
- `quote.conversion.completed`
- `quote.variation.issued`
- `quote.variation.accepted`

### Events consumed

- `inspection.completed`
- `inspection.finding.confirmed`
- `pricebook.version.published`
- `pricebook.resolution.invalidated`
- `customer.updated`
- `property.updated`
- `capacity.changed`
- `payment.received`
- `job.costs.finalised`
- `job.completed`

### Integration principles

- immutable external IDs;
- versioned schemas;
- idempotent commands;
- outbox/inbox delivery;
- replayable events;
- no direct cross-module table writes;
- explicit compensation for partial failure;
- tenant context on every message.

---

## 25. Permissions and governance

### Core permissions

- view quote workspace;
- create enquiry;
- edit scope;
- calculate estimate;
- view internal cost;
- view margin;
- apply permitted promotion;
- request exception;
- approve exception;
- issue quote;
- withdraw quote;
- create revision;
- communicate with customer;
- accept on behalf of customer;
- configure templates;
- configure follow-up policy;
- manage public links;
- manage variations;
- export commercial data;
- view analytics;
- administer network templates.

### Data segregation

Visibility may be limited by:

- company;
- branch;
- territory;
- team;
- assigned estimator;
- customer-account team;
- quote value;
- service category;
- legal entity.

### Sensitive fields

Internal cost, margin, approval notes, risk ratings and AI confidence must never appear in customer documents or public APIs unless explicitly transformed into safe content.

### Network governance

A network may define:

- locked mandatory terms;
- approved templates;
- required deposit rules;
- minimum margins;
- permitted local sections;
- branch approval authority;
- service availability;
- branding inheritance;
- audit sampling.

---

## 26. Security and privacy requirements

### Tenant isolation

Every quote-domain record must carry company/tenant context. Authorisation must verify ownership through policy scopes, not raw record IDs or user-supplied company fields.

### Confirmed donor-code risks to avoid

#### PhoneCallAgent update ownership gap

`PhoneCallAgentUpdateRequest::authorize()` returns `true`, and the controller updates an agent found by raw ID without an ownership condition. Quote-linked call features must never inherit this pattern.

#### Canvas ownership gap

Canvas stores content against a message found by raw ID and does not verify the current user owns or may edit that message.

#### ChatShare public-link weakness

Share records contain raw URLs and record IDs without user/company scope, explicit expiry policy, revocation controls or action permissions.

#### MarketingBot credential and webhook concerns

The prior deep scan identified plaintext/ordinary JSON provider credentials, incomplete webhook verification and sensitive payload logging. Titan Quotes must use a shared secure communications service with encrypted secrets, verified signatures and redacted logs.

#### DiscountManager commercial weaknesses

Global discount records, URL-derived plan/gateway context and weak rule structures cannot be used for quote pricing. Titan Quotes consumes the redesigned Titan Pricebook engine.

### Required controls

- tenant-scoped policies;
- encryption at rest for sensitive fields;
- managed secret vault for provider credentials;
- signature verification for communication and payment webhooks;
- CSRF protection;
- content sanitisation;
- malware and file-signature checks;
- secure object storage;
- scoped signed media access;
- rate limiting;
- session and token revocation;
- audit logs for all commercial transitions;
- field-level redaction in logs and analytics;
- data retention and deletion policies;
- export controls;
- separation of internal and customer-visible content.

### Signature evidence

Acceptance evidence is append-only. Corrections create explanatory records; they do not rewrite the original event.

### Privacy

Customer view analytics should be proportionate. The system may record that a quote was opened and which sections were interacted with when disclosed and lawful, but should not build invasive behavioural profiles unrelated to the transaction.

---

## 27. Reliability and orchestration

### Durable jobs

Use queued, retryable jobs for:

- PDF and document rendering;
- image derivative generation;
- email/WhatsApp/SMS delivery;
- webhook processing;
- price refresh;
- AI scope assistance;
- call transcript processing;
- reminder scheduling;
- payment-status reconciliation;
- WorkCore conversion;
- analytics aggregation.

### No `sleep()` workflows

Delays must be represented as scheduled jobs or durable workflow timers, not blocking processes.

### Idempotency

Required for:

- issue action;
- reminder sends;
- acceptance;
- deposit creation;
- payment webhook handling;
- job conversion;
- variation application;
- public-link creation.

### Delivery tracking

Every outbound message records:

- queued;
- submitted;
- delivered;
- opened/read where provider supports it and policy permits;
- failed;
- bounced;
- suppressed.

### Failure visibility

Users receive actionable states such as:

- quote issued but email failed;
- customer accepted but job conversion partially failed;
- deposit paid but payment webhook delayed;
- PDF rendering failed while web quote remains valid;
- price resolution expired before issue;
- appointment hold expired before acceptance.

### Reconciliation jobs

- issued quotes without active access sessions;
- accepted quotes without acceptance certificate;
- acceptance without conversion;
- conversion without WorkCore links;
- paid deposit not reflected in quote;
- expired reservations still held;
- sent messages without final provider state.

---

## 28. AI-assisted quoting architecture

AI can reduce administrative work, but the commercial agreement must remain explainable and governed.

### Suitable AI tasks

- structure enquiry notes into proposed fields;
- summarise phone calls and messages;
- propose scope items from approved evidence;
- identify missing information;
- draft customer-friendly descriptions;
- simplify technical wording;
- compare quote versions;
- classify objections;
- recommend a follow-up task;
- draft a grounded response;
- flag contradictory scope or terms;
- predict acceptance probability for prioritisation;
- identify unusual variance from similar historical work.

### Prohibited autonomous actions

AI must not independently:

- set or change price;
- apply an unauthorised discount;
- remove an exclusion;
- promise appointment availability;
- determine legal sufficiency of terms;
- accept on behalf of a customer;
- approve a margin exception;
- issue a variation;
- mark a payment complete;
- create a WorkCore job without governed acceptance.

### Provenance

Every AI output records:

- model and provider;
- prompt/template version;
- source records;
- generation timestamp;
- user who accepted or edited it;
- confidence or uncertainty where meaningful;
- whether it became part of an issued quote.

### BYO AI

Businesses may supply their own supported AI keys. Titan-managed AI uses transparent credits with spending caps. The base quoting workflow remains fully usable without AI.

---

## 29. Analytics and commercial intelligence

### Funnel metrics

- enquiries received;
- qualified opportunities;
- inspections required;
- quotes created;
- quotes issued;
- quotes viewed;
- questions received;
- revisions requested;
- quotes accepted;
- deposits paid;
- jobs converted;
- quotes declined or expired.

### Time metrics

- enquiry-to-first-response;
- enquiry-to-scope-ready;
- scope-to-issue;
- issue-to-open;
- open-to-accept;
- acceptance-to-deposit;
- acceptance-to-job creation.

### Conversion dimensions

- service;
- estimator;
- branch;
- territory;
- customer type;
- quote value;
- option strategy;
- inspection method;
- source channel;
- response time;
- number of revisions;
- discount level;
- payment terms.

### Commercial metrics

- quoted value;
- accepted value;
- average accepted margin;
- discount value;
- margin exception value;
- deposit collection;
- variation value;
- recovered value from Quote Rescue;
- estimated-versus-actual labour;
- estimated-versus-actual materials;
- quoted-versus-actual gross margin;
- services that win frequently but underperform financially.

### Customer-experience metrics

- clarity questions by section;
- portal abandonment point;
- opt-out rate;
- complaint rate after follow-up;
- revision frequency;
- decline reasons;
- acceptance device/accessibility issues.

### Responsible use

Acceptance prediction must prioritise work, not manipulate vulnerable customers or enable unjustified pricing differences. Protected characteristics and proxies must not influence price or follow-up intensity.

---

## 30. Pricing and packaging

Pricing should reflect quote volume, automation and locations rather than punishing collaboration with excessive per-user fees.

### Core — AUD $0/month

For owner-operators validating Titan Zero.

Includes:

- one business and branch;
- 5 issued quotes per month;
- unlimited drafts;
- one quote template;
- manual scope and pricing entry;
- PDF and web quote;
- digital acceptance;
- manual WorkCore job conversion;
- BYO AI key;
- 90-day history.

### Solo — AUD $29/month

For individual trades and small service businesses.

Includes:

- 75 issued quotes per month;
- up to 3 internal users;
- Titan Pricebook integration;
- reusable scope templates;
- quote options and add-ons;
- customer portal;
- revisions and version history;
- deposits through BYO providers;
- email delivery and reminders;
- automatic WorkCore conversion;
- one-year history.

### Team — AUD $79/month

For established small teams.

Includes:

- 400 issued quotes per month;
- up to 15 internal users;
- 3 branches;
- Titan Inspect integration;
- visual quotes and annotated scope;
- internal approvals;
- margin and discount controls;
- automated Quote Rescue by email;
- WhatsApp integration through BYO provider;
- variation management;
- advanced analytics;
- 3-year history.

### Automation — AUD $179/month

For multi-crew businesses with structured sales operations.

Includes:

- 2,000 issued quotes per month;
- unlimited internal users in one company;
- 10 branches;
- multichannel follow-up;
- advanced approval policies;
- call intake integration;
- AI scope and objection assistance;
- acceptance prediction;
- customer appointment selection;
- API and webhooks;
- commercial proposal mode;
- advanced estimated-versus-actual reporting;
- 7-year configurable history.

### Network — AUD $499/month

For franchise groups and multi-location operators.

Includes:

- 10,000 issued quotes per month;
- 10 locations included;
- AUD $39 per additional location;
- central templates and mandatory terms;
- local controlled overrides;
- network pricing and Pricebook governance;
- branch benchmarking;
- white-labelled portals;
- SSO and advanced permissions;
- configurable retention;
- private deployment options;
- priority support.

### Usage and provider costs

- BYO AI keys: no model markup;
- BYO email, SMS, WhatsApp, telephony and payment accounts: no communication markup;
- Titan-managed providers: transparent prepaid usage with spending limits;
- no percentage fee on ordinary accepted quotes or jobs;
- Titan's planned marketplace fee applies only when an independent provider transaction is actually brokered.

### Optional add-ons

| Add-on | Indicative price |
|---|---:|
| Additional 1,000 issued quotes | $29/month |
| Dedicated customer portal domain | $15/month |
| AI phone intake | $49/month plus minutes |
| Long-term evidence retention | $19/month |
| Advanced commercial proposal pack | $39/month below Automation |
| Private single-tenant deployment | From $499/month |
| Template migration/setup | $299–$1,499 once-off |

---

## 31. Product surfaces

### Revenue workspace

Shows:

- new enquiries;
- quotes waiting for scope;
- approvals required;
- active quotes;
- customer questions;
- stalled quotes;
- quotes near expiry;
- accepted value;
- pending deposits;
- conversion failures.

### Quote builder

A responsive split workspace:

- scope and evidence;
- internal estimate;
- options and add-ons;
- document preview;
- approvals and warnings;
- customer interaction timeline.

### Mobile field capture

- large touch targets;
- voice-first notes;
- photos and measurements;
- offline drafts;
- quick service templates;
- clear sync state;
- no accidental issue action.

### Approval queue

- value and margin;
- requested exception;
- changed fields;
- supporting evidence;
- prior similar quotes;
- approve, reject or request changes.

### Customer portal

- clean, branded and accessible;
- option comparison;
- visual scope;
- questions;
- selection;
- signature;
- deposit;
- appointment preference.

### Analytics

- conversion funnel;
- estimator and branch performance;
- decline reasons;
- margin quality;
- Quote Rescue results;
- actual delivery feedback.

### Settings

- templates;
- terms;
- approval rules;
- signature policy;
- deposit policy;
- communication channels;
- follow-up policy;
- public-link security;
- integrations;
- retention;
- AI/provider controls.

---

## 32. Donor-engine map and required remediation

| Extension | Reusable engine | Titan Quotes use | Required remediation |
|---|---|---|---|
| DiscountManager | Conditional eligibility, schedules, limits, promo presentation | Follow-up promotions and eligible commercial incentives through Titan Pricebook | Replace global records, float money, CSV criteria, URL context and first-match logic |
| CheckoutRegistration | Stripe/PayPal intent and checkout patterns | Deposit and acceptance payment handoff | Decouple SaaS registration, use quote-scoped idempotency and payment authority |
| Canvas | Tiptap rich-content persistence | Proposal narratives, terms and document blocks | Add tenant/quote authorisation, sanitisation, immutable versions and collaboration |
| CreativeSuite | Editable visual documents and media | Branded proposals and scope visuals | Keep commercial truth structured outside visual JSON |
| CreativeSuiteAITemplate | Generated Konva JSON | Editable visual scope maps and proposal layouts | Validate schema, isolate jobs, retain human approval |
| CreativeSuiteAnnotations | Coordinate-grounded vision | Import annotated evidence from Titan Inspect | Keep inspection authority outside Quotes |
| AiPresentation | Presentation generation and provider job tracking | Optional commercial proposal deck | Treat as derivative; add cost, failure and retention controls |
| ChatShare | Simple public-link interaction pattern | Customer quote portal seed | Complete security rewrite: hashed tokens, scope, expiry, revocation and audit |
| OnboardingPro | Survey/introduction pattern | Guided quote request and customer self-scope | Replace global single-survey model with tenant-scoped branching forms |
| PhoneCallAgent | Calls, transcripts, training and booking adapters | Phone enquiry intake and human follow-up | Fix ownership gaps, encrypt keys, verify webhooks, restrict commercial authority |
| MarketingBot | Contact lists, conversations, WhatsApp and message history | Quote-specific communications and Quote Rescue | Extract shared communications core, verify signatures, encrypt credentials and redact logs |
| Xero | Token/integration shell | Optional accepted quote/accounting projection | Existing extension is too thin; use a dedicated accounting adapter contract |
| Hubspot | CRM integration shell | Opportunity and acceptance sync | Enforce idempotent mappings and field ownership |
| AiPresentation/CreativeSuite | Branded outputs | High-value proposals | Never substitute generated output for structured scope and price records |

### Estimated donor-code reuse

- direct reusable production code: approximately 15–25%;
- reusable concepts and UI patterns: approximately 45–60%;
- new domain, security, versioning and integration code required: substantial.

The archive is valuable as an engine donor, but no existing extension is close to a safe complete quoting application.

---

## 33. Delivery roadmap

### Phase 0 — Domain and trust foundation

Build:

- tenant-scoped quote domain;
- explicit state machines;
- immutable QuoteVersions;
- policy/permission framework;
- audit events;
- secure public-session service;
- outbox/inbox integration;
- private object storage;
- Pricebook and WorkCore contracts;
- idempotency framework.

Exit condition: a quote can be created, versioned, approved, issued and safely accepted in a test environment with complete audit evidence.

### Phase 1 — Complete non-AI saleable app

Build:

- enquiry capture;
- customer/property linking;
- Scope Builder;
- internal estimates;
- Titan Pricebook resolution;
- options and add-ons;
- templates and web/PDF quotes;
- customer portal;
- questions and revision requests;
- signature and deposits;
- WorkCore conversion;
- basic analytics.

This phase is commercially complete without AI.

### Phase 2 — Team governance and variations

Add:

- advanced approvals;
- branch policies;
- Quote Rescue email workflows;
- WhatsApp integration;
- variation/change-order lifecycle;
- appointment holds;
- commercial proposal mode;
- estimated-versus-actual margin reporting;
- offline field drafts.

### Phase 3 — Assisted intelligence

Add:

- enquiry structuring;
- missing-information suggestions;
- voice-note scope extraction;
- objection classification;
- grounded response drafting;
- acceptance prioritisation;
- scope/version comparison;
- unusual margin-variance detection.

### Phase 4 — Network and ecosystem

Add:

- master/branch template inheritance;
- multi-location governance;
- advanced contract signatories;
- SSO;
- white label;
- Titan Configurator integration;
- Titan Service Plans conversion;
- accounting and CRM adapter marketplace;
- network benchmarks.

---

## 34. Testing and validation strategy

### Unit tests

- state transitions;
- quote-version immutability;
- option compatibility;
- approval triggers;
- signature-policy evaluation;
- deposit-policy calculation;
- expiration and renewal;
- follow-up stop conditions;
- permission scopes;
- redaction rules.

### Contract tests

- Titan Pricebook requests and snapshots;
- Titan Inspect findings import;
- WorkCore customer/property references;
- WorkCore job conversion;
- payment provider intents and webhooks;
- communications delivery adapters;
- accounting/CRM projections.

### Integration tests

- enquiry to issued quote;
- inspection to scope;
- price resolution to optioned proposal;
- approval to issue;
- portal question to internal task;
- acceptance and deposit to WorkCore job;
- variation to updated job/invoice plan;
- failed conversion recovery;
- expired quote renewal.

### Security tests

- cross-tenant access attempts;
- insecure direct object references;
- public-token enumeration;
- token expiry and revocation;
- signature webhook forgery;
- rich-content XSS;
- malicious file upload;
- replayed acceptance requests;
- duplicate payment webhooks;
- privilege escalation;
- log leakage.

### Property-based tests

- totals equal selected option and add-ons;
- accepted version never changes;
- every issued quote has an approved or not-required decision;
- every conversion is idempotent;
- no customer-visible response contains internal cost or margin;
- contact stops after opt-out, decline or acceptance;
- stale price resolution cannot issue without policy.

### Offline tests

- draft capture without network;
- encrypted local storage;
- media upload resume;
- conflicting scope edits;
- stale price cache;
- device revocation;
- server revalidation before issue.

### User validation

Test with:

- solo cleaner;
- small plumbing/electrical team;
- painting estimator;
- landscaping business;
- property-maintenance coordinator;
- commercial cleaning bid team;
- franchise operator;
- customers using phone and desktop;
- customers requiring accessibility support.

---

## 35. Release acceptance criteria

Titan Quotes is not production-ready until:

1. every quote record is tenant scoped;
2. every issued version is immutable and content hashed;
3. customer acceptance binds a specific version and configuration;
4. public links are scoped, expiring, revocable and audited;
5. quote pricing is backed by a Pricebook snapshot or explicitly authorised manual basis;
6. internal cost and margin cannot leak to customer surfaces;
7. approval policy cannot be bypassed through API or offline flows;
8. acceptance and conversion are idempotent;
9. partial WorkCore conversion is recoverable;
10. deposit state reconciles with the payment authority;
11. follow-up honours consent, quiet hours, frequency limits and stop states;
12. all communication webhooks are verified;
13. provider credentials are encrypted and access controlled;
14. offline drafts are encrypted and revalidated before issue;
15. variations preserve the original accepted scope;
16. audit history covers issue, view, revision, approval, acceptance and conversion;
17. automated tests cover critical state, pricing, security and conversion paths;
18. the complete workflow functions with AI disabled.

---

## 36. Key product risks and responses

### Risk: duplicating WorkCore CRM and jobs

**Response:** keep customer/property/job authority in WorkCore and quote-specific state in Titan Quotes.

### Risk: duplicating Titan Pricebook

**Response:** all governed pricing resolves through Pricebook and is stored as immutable snapshots.

### Risk: beautiful documents hiding weak scope

**Response:** structured scope and commercial records remain authoritative beneath every presentation.

### Risk: AI hallucinating scope or price

**Response:** AI only proposes content; deterministic pricing and human approval govern commercial decisions.

### Risk: quote follow-up becoming spam

**Response:** consent, frequency, quiet-hour, stop-state and complaint controls are mandatory.

### Risk: digital signatures being oversold legally

**Response:** configurable evidence policies and explicit legal review for relevant jurisdictions and contract types.

### Risk: customer selecting impossible option combinations

**Response:** version-defined configuration rules and Pricebook compatibility validation.

### Risk: accepted promise not matching WorkCore job

**Response:** explicit scope-to-operation mappings, idempotent conversion and reconciliation.

### Risk: offline pricing drift

**Response:** signed Pricebook caches, freshness labels and server revalidation.

### Risk: uncontrolled discounts

**Response:** Pricebook eligibility, hard floors, delegated authority and immutable approval evidence.

### Risk: too many features in first release

**Response:** Phase 1 focuses on enquiry, scope, governed price, issue, acceptance, deposit and conversion. AI, advanced automation and network governance follow.

---

## 37. Strategic role in the WorkCore portfolio

Titan Quotes is the commercial bridge between understanding work and delivering work.

```text
Customer need
→ Titan Inspect confirms evidence and scope
→ Titan Pricebook resolves governed price
→ Titan Quotes creates and secures the commercial agreement
→ WorkCore schedules and delivers the job
→ actual cost returns to Pricebook and Quotes analytics
```

### Relationships

- **Titan Inspect:** supplies approved observations, measurements, evidence and findings.
- **Titan Pricebook:** supplies services, costs, commercial rules and price resolutions.
- **Titan Schedule:** supplies availability and receives accepted work.
- **Titan Service Plans:** receives accepted recurring-service proposals.
- **Titan Configurator:** supplies approved visual and material selections.
- **Titan Supply:** supplies material availability and procurement estimates.
- **Titan Assets:** supplies asset identity and maintenance context.
- **Titan Quality:** receives accepted evidence and completion requirements.
- **Titan Customer Hub:** provides shared customer communication channels.
- **ZeroPay/Titan Money:** executes deposits and later invoice payments.
- **WorkCore:** remains the operational record and job-delivery authority.

### Data moat

Over time Titan Quotes can build a defensible commercial dataset containing:

- which scope descriptions reduce questions;
- which options customers select;
- which inspections improve acceptance;
- which assumptions cause variations;
- which quote patterns produce profitable jobs;
- which services are consistently under-estimated;
- how follow-up affects conversion without harming trust;
- where branch or estimator practice differs.

This dataset should improve templates and pricing recommendations while maintaining tenant privacy and avoiding cross-business data leakage.

---

## 38. Final recommendation

Build Titan Quotes immediately after Titan Inspect and Titan Pricebook.

The first production release should focus on the complete commercial spine:

1. enquiry capture;
2. structured scope;
3. governed estimate and price resolution;
4. clear options and document presentation;
5. approval and immutable issue;
6. secure customer portal;
7. revision, acceptance and deposit;
8. idempotent WorkCore conversion;
9. controlled variations;
10. conversion and margin analytics.

Do not begin with autonomous follow-up or elaborate AI generation. Those features are valuable only after scope, price, versioning, acceptance and WorkCore conversion are reliable.

The strongest market position is:

> **Titan Quotes turns enquiries, inspections and governed pricing into clear customer agreements—and turns accepted agreements into correct operational work.**

Its key selling features are:

- Scope Builder;
- Titan Inspect evidence integration;
- Titan Pricebook margin protection;
- good/better/best options;
- visual and accessible proposals;
- Quote Rescue;
- secure revisions and acceptance;
- deposits and appointment selection;
- variation control;
- exact WorkCore job conversion;
- estimated-versus-actual margin learning.

Titan Quotes should be sold independently, bundled with Titan Inspect and Pricebook as a **Quote-to-Job Suite**, and included in higher Titan Zero operating-system plans.
