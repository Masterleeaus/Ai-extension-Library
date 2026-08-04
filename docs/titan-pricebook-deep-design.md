---
title: Titan Pricebook Deep Product Design
status: Designed
created: 2026-08-02
updated: 2026-08-02
source_archives:
  - Extensions.zip
parent_portfolio: workcore/workcore-business-app-ideas.md
product_family: WorkCore and Titan Zero
recommended_build_order: 2
---

# Titan Pricebook

### Deep product design

**Authoritative service catalogues, cost models, commercial rules and pricing governance for field and home service businesses.**

Titan Pricebook turns the discount, checkout, catalogue, territory and document engines discovered in `Extensions.zip` into a complete standalone business application. It is not merely a coupon manager, quote line-item library or collection of service prices.

Its end-to-end outcome is:

> Define what a business sells, model what each service costs, resolve the correct price for a specific customer and job, protect required margins, govern exceptions, and learn from actual delivery results.

Titan Pricebook can operate independently through its API and pricing workspace, but its strongest form is a WorkCore-native extension. WorkCore remains the authority for customers, properties, workers, jobs, quotes, invoices, payments and actual operational activity. Titan Pricebook becomes the authority for service definitions, cost formulas, rate cards, price rules, packages, commercial eligibility, published versions and price-resolution evidence.

---

## 1. Product thesis

Most field-service businesses do not have an actual pricing system. They have fragments:

- a spreadsheet known only to the owner;
- remembered hourly rates;
- old quote templates;
- supplier costs copied months ago;
- discounts applied inconsistently;
- different prices used by different estimators;
- packages whose inclusions are unclear;
- branch managers overriding prices without knowing the margin;
- customer contracts stored as PDFs rather than executable rules;
- price increases that are never applied to recurring work;
- quotes that cannot explain how their figures were calculated.

This creates five predictable failures.

1. **Margin leakage:** labour, materials, travel, equipment, overhead or waste are omitted.
2. **Inconsistency:** similar customers receive materially different prices without a defensible reason.
3. **Slow quoting:** estimators reconstruct prices repeatedly instead of resolving them from governed rules.
4. **Uncontrolled discounting:** staff reduce prices without understanding the resulting contribution margin.
5. **No learning loop:** actual job costs do not improve future pricing.

A serious pricebook must therefore be more than a catalogue. It must be a **commercial decision system**.

### Product promise

**One source of truth for what you sell, what it costs, what you may charge, and why that price was produced.**

### Primary commercial outcomes

- faster and more consistent quoting;
- protection of gross margin and contribution margin;
- fewer omitted labour, material and travel costs;
- controlled branch and estimator discretion;
- reliable customer and contract pricing;
- governed promotions and service packages;
- repeatable price increases;
- clearer franchise and multi-location control;
- reliable pricing APIs for other Titan applications;
- comparison of estimated and actual service economics;
- early detection of services that are busy but unprofitable.

---

## 2. Application boundary

A pricebook becomes dangerous when it duplicates customers, jobs, invoices or accounting. Titan Pricebook must remain a bounded commercial-authority application.

### Titan Pricebook owns

- service catalogue structure;
- service definitions and variants;
- sellable units and measurement methods;
- labour, material, equipment, subcontractor and travel components;
- cost formulas and pricing formulas;
- rate cards;
- packages and optional add-ons;
- price floors and target margins;
- eligibility and commercial rules;
- branch, territory and channel overrides;
- contract and customer-group rates;
- discounts and promotions;
- taxes as pricing inputs and display policies;
- draft, approved and published pricebook versions;
- price-resolution requests and explanations;
- pricing exception requests and approvals;
- pricing test cases;
- estimated-versus-actual commercial analytics.

### WorkCore remains authoritative for

- customers and contacts;
- properties and service locations;
- staff, teams and employment cost records;
- jobs, appointments and actual labour activity;
- quotes and estimates as commercial documents;
- invoices, credits and payments;
- supplier bills and actual purchasing records;
- inventory quantities;
- general projects, tasks and workflows.

### Other specialist applications remain authoritative for

- **Titan Quotes:** customer-facing quote versions, acceptance and variations;
- **Titan Schedule:** available times, route capacity and dispatch;
- **Titan Supply:** supplier catalogues, stock, purchase orders and current part costs;
- **Titan Assets:** asset identity and service history;
- **Titan Configurator:** customer-selected visual finishes and configurations;
- **Titan Service Plans:** memberships, recurring entitlements and plan lifecycle;
- **Titan MarketLab:** experimental service offers before promotion into the governed catalogue.

### Core boundary rule

Titan Pricebook answers:

> Given this service configuration, customer context, location, date and commercial policy, what price is valid and how was it calculated?

It does not own the quote, booking, invoice or payment that subsequently uses that answer.

---

## 3. Target users and jobs to be done

### Owner-operator

Needs to:

- stop pricing from memory;
- know whether a service is profitable;
- update prices without rebuilding every quote template;
- create simple packages and add-ons;
- prevent accidental underquoting.

### Estimator or salesperson

Needs to:

- select a service and enter quantities;
- receive a valid price quickly;
- understand required assumptions;
- compare package options;
- request an exception when necessary;
- explain the price to the customer without exposing sensitive internal costs.

### Operations manager

Needs to:

- ensure prices reflect realistic labour and service duration;
- identify services whose actual delivery costs exceed assumptions;
- feed route, access, risk and complexity factors into pricing;
- prevent sales from promising commercially impossible work.

### Finance or commercial manager

Needs to:

- control target margins and floors;
- schedule price increases;
- govern discounts;
- compare estimated and actual performance;
- model the impact of labour, supplier and fuel changes;
- preserve an audit trail of every published change.

### Branch manager

Needs to:

- apply approved local overrides;
- adapt to local wages, suppliers, travel and competition;
- remain within central commercial guardrails;
- understand why an override was accepted or rejected.

### Franchise or network operator

Needs to:

- maintain a central master catalogue;
- publish mandatory components and minimum prices;
- allow controlled local variation;
- benchmark branches;
- roll out national price changes predictably;
- preserve historical versions for disputes and analysis.

### Integration developer

Needs to:

- request prices through stable APIs;
- receive deterministic explanations;
- pin a quote to a specific published version;
- replay historical resolutions;
- subscribe to catalogue and pricing events.

---

## 4. Pricing modes and field-service use cases

Titan Pricebook must support several pricing methods within one governed engine.

### Fixed service price

Examples:

- standard call-out;
- smoke-alarm inspection;
- single-room carpet cleaning;
- basic pool service;
- standard lock replacement.

### Unit pricing

Examples:

- per square metre;
- per room;
- per window;
- per solar panel;
- per appliance;
- per kilometre;
- per bin;
- per tree;
- per workstation.

### Time-based pricing

Examples:

- hourly labour;
- after-hours support;
- specialist technician time;
- waiting time;
- equipment hire duration.

### Formula pricing

Examples:

- base call-out + labour hours + material allowance;
- property size multiplied by complexity factor;
- number of bedrooms plus bathrooms plus optional extras;
- travel zone plus service package plus disposal volume;
- asset class plus age plus access difficulty;
- minimum price or calculated amount, whichever is greater.

### Tiered pricing

Examples:

- first 100 square metres at one rate, remaining area at another;
- first hour plus lower subsequent-hour rate;
- volume discounts for multi-property work;
- portfolio rates based on monthly job volume.

### Package pricing

Examples:

- Essential, Recommended and Complete;
- bronze, silver and gold maintenance plans;
- move-out clean with optional carpets and windows;
- HVAC service with filter, coil and duct options;
- painting package by surface preparation level.

### Contract pricing

Examples:

- negotiated commercial-cleaning rates;
- property-manager portfolio schedules;
- insurer or facilities-management rate cards;
- franchise national-account pricing;
- public-sector contract schedules.

### Dynamic operational adjustments

These must be constrained rather than opaque surge pricing:

- after-hours multiplier;
- emergency priority fee;
- remote travel zone;
- difficult access;
- hazardous or regulated conditions;
- short-notice booking;
- weekend or public-holiday labour;
- capacity-sensitive incentives within approved ranges.

### Promotional pricing

Examples:

- first service discount;
- seasonal package;
- route-density offer;
- customer reactivation offer;
- multi-service bundle;
- limited territory launch;
- recurring-plan conversion incentive.

Every method resolves through the same rule, versioning and explanation framework.

---

## 5. Core domain model

The initial schema should avoid premature complexity while preserving clear bounded entities.

### 5.1 Catalogue

Represents one commercial catalogue owned by a company or network.

Key fields:

- `id`;
- `company_id`;
- `name`;
- `code`;
- `currency`;
- `tax_region`;
- `status`;
- `parent_catalogue_id` for inherited network catalogues;
- `default_rate_card_id`;
- `current_published_version_id`;
- `created_by`;
- timestamps.

A company may have separate catalogues for residential, commercial, insurance, franchise or international operations, but one catalogue should be preferred until separation is commercially necessary.

### 5.2 Service definition

The durable identity of a service.

Key fields:

- `id`;
- `catalogue_id`;
- `service_code`;
- `name`;
- `customer_description`;
- `internal_description`;
- `category_id`;
- `measurement_method`;
- `default_unit`;
- `active`;
- `workcore_service_reference`;
- `tax_category`;
- `requires_inspection`;
- `requires_asset_context`;
- `requires_property_context`.

A service definition should not be overwritten when prices change. Pricing belongs to versioned service offers.

### 5.3 Service offer

A versioned, sellable representation of a service.

It defines:

- included scope;
- exclusions;
- assumptions;
- quantity inputs;
- required components;
- default duration;
- price formula;
- minimum price;
- target margin;
- price floor;
- customer-facing wording;
- eligibility;
- effective period.

### 5.4 Component

Reusable cost or price element.

Component types:

- labour;
- material;
- equipment;
- subcontractor;
- travel;
- disposal;
- compliance;
- overhead;
- risk allowance;
- tax;
- fee;
- credit.

Key fields:

- component code;
- cost source;
- sell-rate source;
- unit;
- quantity formula;
- waste factor;
- rounding rule;
- minimum quantity;
- maximum quantity;
- whether customer-visible;
- effective dates.

### 5.5 Rate card

A grouped set of rates for a context.

Examples:

- Melbourne residential rates;
- Sydney commercial rates;
- branch-specific labour rates;
- insurer contract rates;
- after-hours rates;
- subcontractor network rates.

A rate card can inherit from a parent and override only selected rates.

### 5.6 Price rule

An explicit condition-and-action record.

Conditions may reference:

- company or branch;
- territory or postcode;
- customer segment;
- contract;
- property type;
- asset type;
- service;
- quantity range;
- date or daypart;
- booking lead time;
- channel;
- service plan;
- payment method;
- job urgency;
- access or risk attribute;
- promotion code;
- estimator role.

Actions may:

- select a rate card;
- add or replace a component;
- apply a multiplier;
- set a fixed amount;
- enforce a minimum;
- cap a discount;
- require approval;
- block sale;
- change customer wording;
- mark a service unavailable.

### 5.7 Package

A sellable grouping of services, components or entitlements.

Supports:

- required inclusions;
- optional add-ons;
- mutually exclusive choices;
- package-level discounts;
- package-specific margin floors;
- visual selections from Titan Configurator;
- recurring conversion into Titan Service Plans.

### 5.8 Contract schedule

Stores executable commercial terms for a customer, group or external contract.

Includes:

- contract reference;
- customer or segment;
- included rate card;
- permitted services;
- effective dates;
- indexation method;
- minimum volumes;
- service-level fees;
- call-out rules;
- approval authority;
- document evidence reference.

### 5.9 Promotion

A governed incentive, not a free-floating coupon.

Includes:

- purpose;
- eligible services;
- eligible customers;
- territories;
- channels;
- start and end dates;
- usage limits;
- customer-use limits;
- stackability;
- discount method;
- maximum commercial cost;
- required margin after discount;
- approval status;
- stop conditions.

### 5.10 Pricebook version

An immutable snapshot of all published commercial definitions required to reproduce a price.

Includes:

- semantic version or sequential number;
- source draft;
- effective date;
- publishing actor;
- approval evidence;
- change summary;
- checksum;
- parent version;
- rollback target;
- affected branches and contracts.

### 5.11 Price-resolution request

The context sent by Quotes, Booking, Service Plans or another application.

Typical inputs:

- company and branch;
- customer and contract references;
- property and territory;
- selected service and package;
- quantities and measurements;
- date and time;
- urgency;
- asset details;
- route or access attributes;
- promotion code;
- requested version.

### 5.12 Price-resolution result

An immutable explanation of the resolved price.

Contains:

- version used;
- currency;
- component breakdown;
- internal cost estimate;
- customer subtotal;
- discounts;
- tax;
- final price;
- target and resolved margin;
- floor status;
- applied rules in order;
- rejected rules and reasons;
- warnings;
- required approvals;
- expiry or revalidation date;
- deterministic calculation hash.

### 5.13 Pricing exception

Represents a request to deviate from the normal result.

Includes:

- requested price or adjustment;
- business reason;
- customer context;
- original resolution;
- margin impact;
- authority required;
- approver decision;
- expiry;
- one-time or reusable scope.

### 5.14 Price test case

A saved pricing scenario with expected results.

Examples:

- standard residential customer in Zone A;
- contract customer after-hours;
- package with two add-ons;
- promotion at its usage limit;
- quantity crossing a pricing tier;
- branch override conflicting with national floor.

Test cases are mandatory before publishing high-impact changes.

---

## 6. State models

### Catalogue lifecycle

```text
Draft
  → Under review
  → Approved
  → Published
  → Superseded
  → Archived
```

Only published versions may be used for ordinary customer pricing. Draft preview is allowed inside authorised simulation tools.

### Change-set lifecycle

```text
Draft
  → Validation failed
  → Ready for review
  → Under review
  → Changes requested
  → Approved
  → Scheduled
  → Published
  → Rolled back
```

### Promotion lifecycle

```text
Draft
  → Approved
  → Scheduled
  → Active
  → Paused
  → Exhausted
  → Expired
  → Cancelled
```

### Pricing exception lifecycle

```text
Requested
  → Awaiting approval
  → Approved
  → Rejected
  → Used
  → Expired
  → Revoked
```

### Contract schedule lifecycle

```text
Draft
  → Legal/commercial review
  → Approved
  → Active
  → Pending indexation
  → Superseded
  → Expired
```

State transitions must be events with actor, timestamp, reason and previous state. Direct status mutation is not acceptable for governed pricing records.

---

## 7. Catalogue Studio

Catalogue Studio is the primary administrative workspace.

### Service tree

Users can organise services by:

- category;
- vertical;
- property type;
- asset family;
- branch availability;
- residential or commercial use;
- active, draft or retired status.

The interface should support bulk selection without encouraging spreadsheet-like ungoverned editing.

### Service editor

Each service screen contains:

1. identity and classification;
2. customer-facing name and description;
3. scope inclusions;
4. exclusions and assumptions;
5. measurement inputs;
6. components and formulas;
7. standard duration;
8. target margin and floor;
9. packages and add-ons;
10. applicable territories and branches;
11. contract and promotion interactions;
12. effective version history;
13. pricing tests;
14. usage analytics.

### Formula builder

The formula builder should support both guided and expert modes.

Guided mode provides blocks such as:

- fixed amount;
- quantity multiplied by rate;
- base amount plus units;
- minimum or calculated amount;
- tiered quantity;
- percentage allowance;
- conditional component;
- lookup from rate card;
- rounding.

Expert mode uses a constrained expression language—not arbitrary PHP, JavaScript or SQL.

Example:

```text
max(
  service.minimum_price,
  labour.hours * rate("cleaner_standard")
  + area_sqm * rate("chemical_per_sqm")
  + travel.zone_charge
  + disposal.volume_m3 * rate("disposal_per_m3")
)
```

The expression engine must be:

- deterministic;
- side-effect free;
- versioned;
- type checked;
- bounded in execution time;
- unable to access secrets or arbitrary database records.

### Bulk changes

Permitted bulk operations include:

- increase selected rates by percentage;
- replace a component rate;
- apply effective dates;
- change target margins;
- assign a territory;
- retire a service;
- duplicate a package;
- import supplier-cost updates into draft.

Every bulk operation produces a reviewable change set before publication.

### Import and export

Supported inputs:

- CSV and spreadsheet templates;
- prior WorkCore service lists;
- supplier price files;
- contract rate schedules;
- migrated systems through Titan Switch.

Imports must map into a draft and show rejected rows, duplicates, unit mismatches and potentially destructive changes.

---

## 8. Cost and formula engine

The pricebook must distinguish cost, price and margin.

### Cost model

Estimated service cost may include:

- loaded labour cost;
- payroll on-costs;
- materials;
- equipment depreciation or hire;
- subcontractor cost;
- vehicle and travel cost;
- disposal;
- insurance or compliance allocation;
- payment cost;
- sales commission;
- warranty allowance;
- rework allowance;
- branch overhead allocation.

Not every company will model every element. The app should expose a maturity scale rather than requiring false precision.

### Cost-source hierarchy

Component costs may come from:

1. a contract-specific rate;
2. branch or territory rate card;
3. supplier catalogue from Titan Supply;
4. employment-cost data from WorkCore;
5. manual governed rate;
6. fallback default.

The source and timestamp must be retained in each price resolution.

### Margin measures

Support at least:

- gross margin;
- gross markup;
- contribution margin;
- contribution per labour hour;
- contribution per route hour.

The interface must clearly distinguish margin from markup. Mixing them is a common and costly field-service error.

### Floors and guardrails

A service can define:

- absolute minimum price;
- minimum gross margin percentage;
- minimum contribution amount;
- minimum contribution per labour hour;
- maximum discount;
- maximum estimator discretion;
- approval threshold.

A price below the hard floor cannot be silently produced. The engine returns a blocked or approval-required result.

### Rounding

Configurable strategies:

- no rounding;
- nearest cent;
- nearest dollar;
- nearest five or ten dollars;
- psychological endings such as `.95` only when explicitly configured;
- component-level versus final-total rounding.

The unrounded value remains in the resolution record.

### Tax

Tax logic must be delegated to a tax profile and accounting integration where possible. Titan Pricebook determines whether displayed and resolved prices are tax-inclusive or tax-exclusive and retains the tax category used. It should not attempt to replace jurisdiction-specific accounting software.

---

## 9. Price-resolution engine

This is the application’s central runtime.

### Resolution sequence

```text
Validate context
→ select published pricebook version
→ identify service offer and package
→ resolve contract and customer eligibility
→ resolve branch and territory rate cards
→ calculate quantities and components
→ apply operational rules
→ apply package adjustments
→ evaluate approved promotion eligibility
→ enforce floors and authority limits
→ calculate tax presentation
→ produce deterministic explanation
```

### Explicit precedence

A recommended default from highest to lowest authority:

1. legal or mandatory block;
2. active customer contract;
3. network hard floor;
4. branch mandatory override;
5. service-specific rule;
6. package rule;
7. customer-segment rule;
8. territory rule;
9. general rate card;
10. approved promotion;
11. estimator exception.

This order must be configurable only within safe categories. A promotion must never outrank a contractual obligation or hard margin floor.

### Conflict detection

The engine must detect:

- two rules attempting incompatible fixed prices;
- circular inherited rate cards;
- overlapping effective periods;
- promotions that exceed allowed discounts;
- branch overrides below network floors;
- package prices lower than component cost;
- missing required rate values;
- unit incompatibilities;
- unresolved tax treatment;
- contracts referencing retired services.

A publication must fail when a material conflict remains unresolved.

### Explainability

Every result provides two explanations.

#### Internal explanation

Shows:

- cost and sell components;
- applied rate sources;
- rules and precedence;
- margin calculation;
- rejected conditions;
- floors and warnings;
- approval rationale.

#### Customer-safe explanation

Shows only approved customer-facing information:

- service scope;
- quantities;
- packages and add-ons;
- legitimate fees;
- discounts;
- tax;
- total.

It must not expose payroll costs, supplier margins or confidential contract logic.

### Replayability

A historical price must be reproducible from:

- version ID;
- resolution input;
- rate-source snapshots;
- rules used;
- formula version;
- calculation engine version.

This is essential for quote disputes, contract audits and regression testing.

---

## 10. Packages, bundles and configuration

Packages are more than presentation labels.

### Good, better and best

A package comparison can define:

- required services;
- differing preparation levels;
- warranty levels;
- response times;
- evidence or reporting levels;
- recurring maintenance;
- included add-ons;
- price and margin.

### Add-ons

Add-ons can be:

- optional;
- recommended under specified conditions;
- required when another selection is made;
- mutually exclusive;
- quantity based;
- visual selections from Titan Configurator.

### Bundle economics

The engine calculates whether the package discount is funded by:

- reduced setup time;
- shared travel;
- lower acquisition cost;
- operational efficiency;
- deliberate promotional spend.

A bundle should not be allowed merely because its final total looks attractive.

### Quote integration

Titan Quotes receives:

- package names;
- customer descriptions;
- inclusions and exclusions;
- line-item or summary presentation;
- selection constraints;
- price-resolution references;
- revalidation conditions.

Quote acceptance pins the selected configuration to the resolution and pricebook version used.

---

## 11. Discounts and promotions

The existing `DiscountManager` extension provides useful seeds:

- percentage discount representation;
- AND/OR eligibility;
- user-type, plan and payment-gateway criteria;
- total usage limits;
- once-per-user limits;
- scheduled start and end dates;
- coupon association;
- strikethrough presentation;
- duplication and activation workflows.

Titan Pricebook must substantially redesign this engine.

### Required promotion methods

- percentage discount;
- fixed amount;
- fixed promotional price;
- free component or add-on;
- bundle adjustment;
- credit toward future service;
- first-service incentive;
- quantity threshold;
- capped discount;
- tiered discount.

### Eligibility

Promotions may depend on:

- customer status;
- customer segment;
- prior job history;
- property or asset;
- service and package;
- territory;
- channel;
- lead source;
- schedule window;
- payment method;
- service plan;
- branch capacity;
- promotion code;
- campaign reference.

### Promotion budget

Each promotion can enforce:

- total discount budget;
- usage count;
- per-customer cap;
- per-property cap;
- per-branch cap;
- minimum post-discount margin;
- stop after booked revenue or capacity threshold;
- manual pause.

### Stacking policy

Explicit options:

- never stack;
- stack only with named promotions;
- choose the best customer outcome;
- choose the lowest commercial cost;
- apply in declared sequence;
- require approval.

The current donor code returns the first applicable discount ordered by amount. Titan Pricebook must instead evaluate all candidates and return a documented decision.

### Truthful scarcity

Promotional urgency must be based on actual dates, usage, route availability or capacity. The app must not manufacture false countdowns or availability claims.

---

## 12. Contract and customer pricing

Commercial field service often requires negotiated schedules that generic pricebooks handle poorly.

### Contract support

- customer-specific or group-specific rates;
- fixed schedules;
- cost-plus formulas;
- index-linked adjustments;
- annual increase dates;
- minimum monthly spend;
- volume tiers;
- emergency and after-hours rates;
- included call-outs;
- service credits;
- capped increases;
- grandfathered services;
- approval requirements.

### Contract import

A contract schedule may be entered manually or extracted from a document, but extracted terms remain draft until a human confirms them. AI may locate prices, dates and indexation clauses; it may not activate commercial terms autonomously.

### Indexation

Supported methods:

- fixed percentage;
- manual reviewed increase;
- external index reference supplied by an authorised integration;
- labour/material blended formula;
- contract-specific cap and floor.

Scheduled indexation generates an impact report and approval task before publication.

### Customer exceptions

One-time negotiated prices should not silently become permanent. The user must choose:

- this quote only;
- this property only;
- this service only until a date;
- customer contract amendment;
- reusable approved exception.

---

## 13. Branch, territory and network governance

### Inheritance model

```text
Network master
  → country or region
    → company
      → branch
        → temporary approved override
```

Child catalogues inherit unless a field is explicitly overridable.

### Governance classifications

Each commercial field is classified as:

- mandatory and locked;
- locally overridable within range;
- locally editable with approval;
- locally owned;
- informational.

### Territory pricing

Titan Maps Intelligence can supply:

- postcode and service-area membership;
- travel zones;
- branch catchments;
- route distances;
- remote-area classifications;
- geographic overlap.

Pricebook consumes this context but does not own territory discovery or map-provider credentials.

### Branch simulation

Before publishing a master change, the network can preview:

- affected services;
- expected price movement by branch;
- contract conflicts;
- floor breaches;
- likely customer-plan increases;
- estimated revenue and margin impact.

---

## 14. Versioning, review and publication

Pricing is operational configuration and must be treated like controlled software.

### Draft change sets

Every edit belongs to a change set containing:

- author;
- business reason;
- affected records;
- before/after values;
- effective date;
- risk classification;
- required reviewers;
- test results;
- impact simulation.

### Approval rules

Examples:

- ordinary description changes: one reviewer;
- labour or material rate change: pricing manager;
- floor reduction: finance approval;
- national contract change: commercial and legal approval;
- promotion exceeding budget: executive approval;
- tax-treatment change: finance-only authority.

The author should not approve their own high-impact change.

### Publication

Publication must:

1. lock the approved draft;
2. run all validation and test cases;
3. calculate an immutable checksum;
4. produce a complete version snapshot;
5. schedule or activate the version;
6. emit domain events;
7. invalidate dependent caches;
8. notify affected applications and branches;
9. preserve the previous version for replay and rollback.

### Rollback

Rollback publishes a new version based on a previous known-good snapshot. Historical versions are never edited or deleted merely because they were wrong.

### Effective dating

The engine distinguishes:

- publication date;
- effective date;
- quote validity date;
- service delivery date;
- contract indexation date.

A quote may retain an older version until expiry while new quotes use the current version.

---

## 15. Quote-time and booking-time APIs

### Price preview

Used while an estimator configures scope.

Characteristics:

- may change as inputs change;
- not yet commercially reserved;
- includes validation messages;
- can use draft versions for authorised simulation.

### Price resolve

Creates an immutable result against a published version.

Required inputs are schema validated. Unknown or missing measurements produce explicit errors rather than guessed zero values.

### Price reserve

Optional short-lived reservation used when:

- customer is completing online booking;
- inventory or route-dependent price is time-sensitive;
- a promotion has limited usage;
- the quote needs a stable amount during checkout.

Reservation does not create a booking or quote. It protects the commercial result for a defined period.

### Price revalidate

Checks whether a previous resolution remains valid after:

- quote amendment;
- expiry;
- property or quantity change;
- delivery-date change;
- promotion exhaustion;
- contract change.

### Exception request

Applications submit the desired deviation and context. Titan Pricebook returns:

- automatic rejection;
- automatic approval within delegated authority;
- approval task;
- approved exception token.

### Events

Recommended events:

- `PricebookVersionPublished`;
- `PricebookVersionRolledBack`;
- `ServiceOfferChanged`;
- `ContractRateActivated`;
- `PromotionActivated`;
- `PromotionPaused`;
- `PriceResolved`;
- `PriceFloorBreached`;
- `PricingExceptionRequested`;
- `PricingExceptionApproved`;
- `PricingExceptionRejected`;
- `ActualMarginVarianceDetected`.

---

## 16. Actual-cost and margin feedback

The pricebook should learn from operations without mutating published pricing automatically.

### WorkCore feedback

After job completion, WorkCore can provide:

- actual labour time;
- worker mix;
- actual materials;
- equipment use;
- travel time and distance;
- subcontractor cost;
- disposal cost;
- rework and warranty activity;
- invoice and credit outcomes.

### Variance analysis

Compare:

- estimated versus actual duration;
- estimated versus actual material use;
- expected versus actual gross margin;
- contribution per labour hour;
- variance by estimator;
- variance by worker or team;
- variance by property type;
- variance by branch, territory and service;
- variance by package and optional add-on.

### Recommendations

The app may recommend:

- increase duration assumption;
- revise material quantity;
- add an access-risk input;
- split a service into variants;
- raise a price floor;
- change a package inclusion;
- investigate delivery performance rather than changing price.

Recommendations remain reviewable drafts. A model must not publish prices autonomously.

### Data quality

Pricebook must show whether actual cost data is complete enough to support a recommendation. Missing timesheets or supplier costs must reduce confidence rather than being treated as zero.

---

## 17. Offline and edge behaviour

Pricing authoring and publication require an online authoritative connection. Field users, however, need resilient access.

### Offline read cache

Titan Quotes and field applications may cache:

- published service catalogue subset;
- customer-safe descriptions;
- standard packages;
- approved rate data required for local resolution;
- formula and rule bytecode;
- version and expiry metadata.

### Offline constraints

- cache is encrypted per tenant and device;
- only published versions are cached;
- hard expiry is enforced;
- high-risk contract or promotion logic may require online resolution;
- exceptions cannot be approved offline;
- offline results are marked and revalidated when connectivity returns;
- stale cache cannot silently price after its permitted window.

### Deterministic parity

The local resolver and server resolver must pass the same shared pricing test suite. Differences block release.

---

## 18. WorkCore and portfolio integration contract

### WorkCore

Consumes published services and price resolutions. Supplies customer, property, employee-cost and actual-job context through explicit contracts.

### Titan Quotes

- browses services and packages;
- requests previews and final resolutions;
- attaches customer-safe explanations;
- requests exceptions;
- pins quote versions;
- sends accepted configuration to WorkCore.

### Titan Schedule

Provides:

- delivery date and daypart;
- route zone;
- urgent or after-hours status;
- capacity-derived approved incentives.

Pricebook never invents availability.

### Titan Service Plans

Consumes:

- recurring service prices;
- membership packages;
- included entitlements;
- renewal and indexation rules;
- upgrade and downgrade pricing.

### Titan Configurator

Provides selected finishes, materials, quantities and options. Pricebook resolves their commercial effects.

### Titan Supply

Provides governed current costs, preferred suppliers and part compatibility. Pricebook snapshots costs used in each resolution.

### Titan Assets

Provides asset class, model, age and service attributes where these are legitimate pricing inputs.

### Titan Inspect

Produces quantities, conditions, access factors and findings that can be mapped to price inputs. Human-confirmed evidence should be distinguishable from AI suggestions.

### Titan MarketLab

Creates experimental offers in a sandbox catalogue. Only validated and approved offers are promoted into the production pricebook.

---

## 19. Permissions and governance

Recommended roles:

### Catalogue viewer

- view published customer-safe catalogue;
- run authorised previews.

### Estimator

- resolve prices;
- configure packages;
- request exceptions;
- cannot edit cost or policy.

### Service designer

- draft service definitions and packages;
- cannot publish.

### Pricing analyst

- maintain rates, formulas and simulations;
- inspect internal cost and margin.

### Branch pricing manager

- manage permitted local overrides;
- approve within delegated limits.

### Finance approver

- approve floors, margins, tax treatment and major promotions.

### Commercial administrator

- govern contract schedules and network policies.

### Publisher

- publish approved versions;
- cannot approve their own high-risk changes unless emergency policy explicitly permits it.

### Auditor

- read all versions, decisions and logs;
- cannot change commercial records.

Permissions must be company and branch scoped. The donor `DiscountManager` uses an admin check and global model queries; that model is insufficient for multi-tenant WorkCore use.

---

## 20. Security and privacy requirements

### Tenant isolation

Every catalogue, service, rule, promotion, version, resolution and exception must carry company ownership or resolve through an authorised company context. Raw ID lookups without tenant scope are prohibited.

### Commercial confidentiality

Internal costs, margins, supplier rates and contract terms require separate permissions from customer-facing prices.

### Integrity

- published versions are immutable;
- calculations receive hashes;
- audit logs are append-only;
- high-impact changes record before and after values;
- API clients are scoped;
- exception tokens are signed, single-purpose and expiring.

### Expression safety

The formula language must not permit arbitrary code execution, database access, file access or network calls.

### Payment boundary

`CheckoutRegistration` provides useful Stripe/PayPal and subscription/prepaid checkout patterns. Titan Pricebook must not store raw card data or become a payment processor. It supplies governed amounts and references to WorkCore or a payment application.

### Privacy minimisation

Price resolutions should use customer segment and contract references where possible rather than duplicating full personal records.

---

## 21. Reliability and orchestration

### Idempotency

Price resolution, publication and exception APIs require idempotency keys.

### Transactions

Version publication is atomic. A partially published catalogue is unacceptable.

### Caching

Cache keys include:

- company;
- branch;
- version;
- service;
- relevant context fingerprint.

Publication invalidates affected keys through events.

### Failure behaviour

- missing mandatory rate: fail closed with a clear error;
- nonessential analytics unavailable: price may resolve and queue analytics;
- territory provider unavailable: use previously resolved territory or require review, depending on policy;
- supplier cost stale: warn or block according to service policy;
- tax integration unavailable: do not guess tax treatment;
- promotion counter contention: use atomic reservation.

### Observability

Track:

- resolution latency;
- cache hit rate;
- calculation failures;
- missing inputs;
- floor breaches;
- exception rates;
- promotion reservation conflicts;
- version publication duration;
- offline/server parity failures.

---

## 22. Product analytics and commercial intelligence

### Catalogue analytics

- active and unused services;
- duplicate or overlapping services;
- services lacking cost models;
- services lacking recent actual-cost evidence;
- upcoming contract and rate expiries;
- branches on old versions.

### Pricing analytics

- average resolved price;
- margin distribution;
- floor breaches;
- exception frequency;
- discount cost;
- package selection;
- price movement over time;
- service and branch profitability;
- estimator discretion patterns.

### Conversion integration

With Titan Quotes:

- acceptance rate by price range;
- acceptance by package;
- acceptance after exception;
- discount versus conversion relationship;
- price increase impact.

Correlation must not be presented as proof that lowering price caused conversion.

### Operational feedback

With WorkCore:

- expected versus actual margin;
- contribution per route hour;
- services with high revenue but poor profit;
- recurring underestimation of duration or materials;
- branch delivery variance.

### Promotion analytics

- customers eligible;
- reservations;
- uses;
- booked revenue;
- completed revenue;
- discount cost;
- post-discount margin;
- cancellation and refund effects;
- incremental performance where a valid comparison exists.

---

## 23. Pricing and packaging

Pricing should reflect commercial value while remaining accessible to owner-operators and consistent with Titan Zero’s BYO-provider approach.

### Core — AUD $0/month

For evaluation and basic owner-operator use.

Includes:

- one company and branch;
- one published pricebook;
- up to 50 active services;
- fixed and unit pricing;
- basic cost and margin fields;
- one standard rate card;
- PDF/CSV export;
- manual version publication;
- WorkCore service sync;
- BYO AI for optional description assistance;
- 250 price resolutions per month.

### Solo — AUD $39/month

For individual trades and small service businesses.

Includes:

- up to 250 active services;
- formula pricing;
- reusable labour, material and travel components;
- packages and add-ons;
- target margin and hard floors;
- scheduled effective dates;
- basic promotion rules;
- Titan Quotes integration;
- 5,000 price resolutions per month;
- two years of version history.

### Team — AUD $99/month

For multi-worker and multi-branch businesses.

Includes:

- up to 1,000 active services;
- up to five branches;
- inherited branch rate cards;
- contract customer pricing;
- tiered and conditional formulas;
- approval workflows;
- pricing exceptions;
- promotion budgets and stacking rules;
- actual-cost feedback;
- bulk updates and imports;
- API and webhooks;
- 25,000 price resolutions per month;
- five years of history.

### Pro — AUD $249/month

For mature commercial operators.

Includes:

- up to 5,000 active services;
- up to 20 branches;
- advanced contract schedules and indexation;
- network floors and delegated authority;
- pricing test suites and impact simulation;
- offline resolver packages;
- advanced margin and variance analytics;
- supplier and employment-cost connectors;
- sandbox catalogues for experiments;
- 150,000 price resolutions per month;
- seven years of history.

### Network — AUD $699/month

For franchises, provider networks and large multi-location groups.

Includes:

- up to 25,000 active services and variants;
- 50 included locations;
- central master catalogues;
- regional inheritance;
- mandatory and locally overridable fields;
- national-account contracts;
- branch benchmarking;
- central publication and rollback;
- white-labelled catalogue and quote APIs;
- dedicated integration controls;
- 500,000 price resolutions per month;
- extended audit retention.

Additional locations: approximately **AUD $19–$39 per location/month**, depending on resolution volume and governance requirements.

### Usage principles

- BYO AI incurs no model markup.
- Normal price resolutions should be included generously and cost almost nothing operationally.
- No percentage of ordinary quoted or booked revenue.
- Marketplace fees apply only when Titan genuinely intermediates an external supplier or provider transaction.
- Private single-tenant deployment may begin around **AUD $499/month** plus infrastructure.

---

## 24. Product surfaces

### Commercial overview

Shows:

- current published version;
- upcoming scheduled changes;
- margin health;
- unresolved conflicts;
- exception requests;
- stale costs;
- services requiring review;
- branch adoption.

### Catalogue Studio

Service tree, editor, formulas, components, packages and test cases.

### Rate Cards

Labour, material, equipment, travel, subcontractor and overhead rates with inheritance and effective dates.

### Rules

Visual condition/action builder with precedence, simulation and conflict warnings.

### Contracts

Customer and group schedules, indexation, expiry and affected services.

### Promotions

Eligibility, budgets, stackability, stop conditions and performance.

### Version Control

Change sets, reviews, tests, simulations, approvals, publication and rollback.

### Price Simulator

Allows authorised users to enter customer, property, service, quantity, date and branch context and inspect the exact result.

### Exceptions

Approval queue with margin impact, reason, authority and usage status.

### Intelligence

Estimated-versus-actual economics, conversion context and recommended drafts.

### Developer and Integration

API keys, webhooks, schemas, event history and resolution diagnostics.

---

## 25. Donor-engine map and required remediation

### DiscountManager

#### Reusable concepts

- conditional eligibility;
- AND/OR rule concept;
- usage limits;
- once-per-user controls;
- scheduling;
- promotion presentation;
- coupon association;
- duplication workflow.

#### Confirmed limitations requiring redesign

- global models without company scope;
- admin-only authorisation rather than granular roles;
- multi-value criteria stored as comma-separated strings;
- money represented using floating point;
- only percentage discounts implemented, with fixed discounts commented out;
- discount selection ordered primarily by amount and returning the first applicable coupon;
- plan and payment gateway inferred from request URL;
- `applyDiscountCoupon()` initially filters to `scheduled = false`, preventing scheduled records from reaching its later date validation path;
- no explicit stacking, conflict, margin-floor or budget model;
- no immutable commercial versioning;
- no deterministic explanation record.

The code should be treated as a behavioural donor, not retained as the Pricebook domain model.

### CheckoutRegistration

#### Reusable concepts

- Stripe and PayPal checkout paths;
- subscription and prepaid checkout distinction;
- tax-aware price display;
- plan selection;
- registration/checkout sequencing;
- payment-gateway abstraction seeds.

#### Required boundary changes

- separate price resolution from payment execution;
- avoid global default-gateway coupling;
- do not create pricing authority from checkout URL parameters;
- ensure registration and payment retries are idempotent;
- retain payment provider references without storing sensitive card data;
- delegate final invoice and payment authority to WorkCore or the payment domain.

### TitanMapsIntelligence

Reuse:

- company-scoped architecture;
- territory analysis and coordinates;
- provider contracts;
- audit and permission abstractions;
- domain-event patterns;
- signed/private export concepts;
- explicit WorkCore integration contracts.

This extension provides a much stronger architectural pattern than DiscountManager and should influence Pricebook tenancy and integration design.

### CreativeSuite and Canvas

Reuse:

- structured customer-facing catalogue content;
- rich service descriptions;
- visual package cards;
- editable commercial documentation.

Do not couple visual document storage to pricing authority.

### Migration

Reuse the driver and mapping concepts for importing pricebooks, contracts and service lists from previous systems. Imported data must land in draft versions.

---

## 26. Delivery roadmap

### Phase 0 — Commercial foundation

- company-scoped models and policies;
- service definitions;
- components and rate cards;
- constrained formula engine;
- immutable versions;
- price-resolution API;
- audit events;
- WorkCore service references;
- money and unit value objects.

### Phase 1 — Complete non-AI product

- Catalogue Studio;
- fixed, unit and formula pricing;
- packages and add-ons;
- margins and floors;
- draft/review/publish workflow;
- quote integration;
- simulator;
- CSV import/export;
- Core, Solo and Team packaging.

This phase is already a complete saleable application.

### Phase 2 — Commercial governance

- contract schedules;
- branch and territory overrides;
- promotions and budgets;
- pricing exceptions;
- test cases and impact simulation;
- scheduled publication;
- rollback;
- API/webhook administration.

### Phase 3 — Operational intelligence

- actual-cost feedback;
- margin variance;
- supplier-cost integration;
- employment-cost integration;
- recommendation drafts;
- stale-data assurance;
- advanced Pro analytics.

### Phase 4 — Network and ecosystem

- master/child catalogue inheritance;
- franchise controls;
- national-account rates;
- offline resolver;
- high-volume API;
- MarketLab sandbox promotion;
- Configurator, Assets, Supply and Service Plans integrations.

---

## 27. Testing and validation strategy

### Unit tests

- money arithmetic;
- units and conversions;
- formula parsing and evaluation;
- rounding;
- margin and markup calculations;
- rule condition evaluation;
- precedence;
- tier boundaries;
- effective dates;
- stacking;
- contract selection;
- floor enforcement.

### Property-based tests

Generate large combinations to verify invariants such as:

- final price never falls below a hard floor without approval;
- disabled rules never apply;
- expired promotions never apply;
- changing irrelevant context does not alter price;
- replay produces the same result;
- component totals reconcile to final totals;
- percentage discounts remain within configured caps.

### Golden test cases

Every published pricebook maintains representative expected scenarios. Publication runs these against the candidate version and reports changed outcomes.

### Integration tests

- WorkCore customer/property context;
- Titan Quotes preview and resolution;
- branch and territory lookup;
- supplier cost snapshots;
- contract import;
- promotion reservations;
- exception approval;
- version events and cache invalidation.

### Security tests

- cross-tenant access;
- internal-cost leakage;
- formula-language escape attempts;
- raw ID enumeration;
- unauthorised publication;
- exception-token replay;
- API scope enforcement;
- audit-log tampering.

### Performance tests

Targets should include:

- common cached price resolution under 150 ms server-side;
- uncached ordinary resolution under 500 ms;
- bulk simulation of 10,000 representative scenarios within an operationally acceptable batch window;
- atomic publication under concurrent quote traffic;
- high-volume promotion reservation without oversubscription.

### Offline parity tests

The server and local resolver execute the same signed test package and must produce identical money, rule and explanation hashes.

---

## 28. Release acceptance criteria

### Foundation

- every commercial record is company scoped;
- money never uses binary floating point;
- published versions are immutable;
- calculations are reproducible;
- formula execution is sandboxed and deterministic;
- no raw checkout URL determines authoritative pricing context.

### Complete application

- a business can create a service catalogue;
- model labour, material, equipment, travel and overhead;
- create packages and add-ons;
- set margin floors;
- test a pricing scenario;
- submit and approve a change;
- publish a version;
- resolve a price through Titan Quotes;
- explain the price internally and safely to a customer;
- reproduce the result later.

### Governance

- contracts, branch overrides and promotions obey declared precedence;
- conflicts block publication;
- exceptions require appropriate authority;
- author and approver separation is enforced for high-impact changes;
- rollback preserves all history.

### Operations

- completed WorkCore jobs can be compared with pricing assumptions;
- incomplete actual-cost data is visible;
- recommendations never publish automatically;
- critical failures are observable and retryable.

### Commercial

- Core and Solo plans deliver a useful independent product;
- Team supports real multi-worker businesses;
- Pro supports commercial contracts and governance;
- Network supports central/local control without duplicating tenant data.

---

## 29. Key product risks and responses

### Risk: excessive configuration complexity

**Response:** guided templates by vertical, progressive disclosure and a maturity path from fixed prices to component costing.

### Risk: businesses enter inaccurate costs and trust false margins

**Response:** data freshness indicators, completeness scores and clear distinction between known, estimated and missing values.

### Risk: pricebook becomes a second accounting system

**Response:** retain commercial modelling only; delegate invoices, payments, bills and financial ledgers to WorkCore and accounting integrations.

### Risk: dynamic pricing damages customer trust

**Response:** limit adjustments to transparent operational factors and approved incentives; prohibit opaque individual willingness-to-pay pricing.

### Risk: AI changes prices unpredictably

**Response:** AI may suggest draft changes and classify evidence but cannot execute formulas outside the deterministic engine or publish a version.

### Risk: branches bypass governance

**Response:** delegated ranges, signed versions, exception tokens, adoption reporting and mandatory floors.

### Risk: integrations send incomplete context

**Response:** schema-required inputs, warnings, explicit unresolved states and no defaulting missing quantities to zero.

### Risk: historical quotes change when the catalogue changes

**Response:** every resolution and accepted quote pins an immutable version and rate-source snapshot.

---

## 30. Strategic role in the WorkCore portfolio

Titan Pricebook is the shared commercial foundation for multiple apps.

### Titan Quotes

Uses governed services, packages, prices, explanations and exceptions.

### Titan Schedule

Provides legitimate operational modifiers and consumes service durations.

### Titan Service Plans

Uses recurring prices, entitlements and indexation.

### Titan Configurator

Maps visual selections to governed options and costs.

### Titan Supply

Provides supplier costs and procurement context.

### Titan Assets

Provides asset-specific service inputs.

### Titan Inspect

Provides measured scope and confirmed conditions.

### Titan MarketLab

Experiments outside production, then promotes validated offers into governed drafts.

### Titan Franchise Control

Uses network inheritance, floors, branch overrides and version adoption.

Without Titan Pricebook, each application will recreate its own pricing rules and the portfolio will drift. With it, WorkCore gains a coherent commercial language across quoting, scheduling, recurring service, procurement, visual sales and franchise governance.

---

## 31. Final recommendation

Build Titan Pricebook immediately after Titan Inspect.

Titan Inspect establishes trusted scope and evidence. Titan Pricebook turns that trusted scope into a governed commercial result. Together they create the foundation for Titan Quotes:

```text
Inspection evidence
→ confirmed quantities and conditions
→ governed price resolution
→ customer-facing quote
→ accepted WorkCore job
→ actual-cost feedback
→ improved pricing draft
```

The first release should remain disciplined:

1. service catalogue;
2. reusable cost components;
3. fixed, unit and constrained formula pricing;
4. margin floors;
5. packages and add-ons;
6. immutable versioning;
7. draft-review-publish workflow;
8. deterministic price-resolution API;
9. Titan Quotes and WorkCore integration;
10. pricing simulator and test cases.

Do not begin with AI price optimisation, marketplace transactions or complex dynamic pricing. The initial moat is more fundamental:

> **A field-service business can finally prove which commercial rules produced a price, protect its margin, update those rules safely and learn whether the work was actually profitable.**
