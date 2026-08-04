---
title: Titan Configurator Deep Product Design
status: Designed
created: 2026-08-02
updated: 2026-08-02
source_archives:
  - Extensions.zip
parent_portfolio: workcore/workcore-business-app-ideas.md
related_designs:
  - workcore/titan-inspect-deep-design.md
  - workcore/titan-pricebook-deep-design.md
  - workcore/titan-quotes-deep-design.md
product_family: WorkCore and Titan Zero
recommended_build_order: 5
---

# Titan Configurator

### Deep product design

**Visual service, material, finish and outcome configuration for field and home service businesses.**

Titan Configurator turns the masking, inpainting, coordinate-grounded vision, editable-canvas, image-generation, media-library and product-visualisation engines discovered in `Extensions.zip` into a complete standalone business application. It is not merely an AI image editor, paint-colour preview, product-photography tool or generic room visualiser.

Its end-to-end outcome is:

> Understand the customer’s real property or asset, identify the regions that may be changed, let the customer compare valid service and material options, resolve governed pricing, preserve the approved visual decision, and convert that decision into a quote and executable work instructions.

Titan Configurator can operate independently, but its strongest form is a WorkCore-native specialist application. WorkCore remains authoritative for customers, properties, jobs, schedules and operational delivery. Titan Inspect remains authoritative for inspection evidence and measured condition. Titan Pricebook remains authoritative for products, services, compatibility-linked prices and commercial rules. Titan Quotes remains authoritative for the customer-facing commercial offer and legally meaningful acceptance. Titan Configurator becomes authoritative for visual projects, source media, editable regions, configuration options, generated preview states, design comparisons, customer design selections and approved visual work instructions.

---

## 1. Product thesis

Many field and home service businesses sell an outcome that customers struggle to imagine.

A painter can describe a colour, but the customer wants to see it on their own walls. A roof-restoration company can show a tile sample, but the customer wants to compare the full roof in charcoal, terracotta or woodland grey. A landscaper can explain a planting plan, but the homeowner wants to understand spatial balance. A pressure-cleaning company may know the likely result, but the customer cannot easily distinguish cleaning from restoration or replacement. A flooring company can show a swatch without showing how it interacts with the room, light and furniture.

Existing visualisers usually fail in one of two ways:

1. they are attractive but disconnected from scope, price, compatibility and operational delivery; or
2. they are technically accurate catalogues that do not help the customer experience the proposed outcome.

Generic AI image generators create a third problem: they may produce persuasive images that quietly alter windows, furniture, proportions, rooflines, landscaping, texture or damage. A beautiful but inaccurate preview creates commercial and legal risk.

Titan Configurator must therefore be a **controlled visual decision system**, not an unconstrained image generator.

### Product promise

**Show the customer what can change, what will stay the same, what each option costs, and exactly what the team has been approved to deliver.**

### Primary commercial outcomes

- increase customer confidence before purchase;
- reduce indecision and repeated colour or material discussions;
- improve conversion into higher-value packages;
- prevent selection of incompatible products or finishes;
- reduce misunderstandings between sales and field teams;
- preserve an exact visual record of the chosen option;
- shorten quote preparation;
- create clearer material and scope handoffs;
- support remote sales without unnecessary site visits;
- reduce post-acceptance change requests;
- create reusable property and surface intelligence.

---

## 2. Application boundary

Titan Configurator must not duplicate quoting, inspection, product-master or job-management systems.

### Titan Configurator owns

- visual configuration projects;
- project-specific source images, plans and approved derivatives;
- spaces, views, surfaces and editable regions;
- masks, polygons, points and region lineage;
- configuration scenarios;
- visual option selections;
- preview-generation requests and results;
- side-by-side comparison sets;
- fidelity and uncertainty assessments;
- customer design comments;
- non-commercial design approval;
- immutable approved visual versions;
- visual work-instruction packages;
- proposed bill-of-material mappings;
- project-specific rendering and audit history.

### WorkCore remains authoritative for

- customers and contacts;
- properties and service locations;
- leads and opportunities;
- jobs, appointments and tasks;
- staff and contractors;
- actual job completion;
- customer communication history outside configuration sessions;
- invoices and payments.

### Titan Inspect remains authoritative for

- measured dimensions;
- existing-condition evidence;
- detected damage and defects;
- confirmed surface or asset classifications;
- inspection findings;
- site-access and hazard observations.

Configurator consumes these records and retains references. It does not alter inspection truth.

### Titan Pricebook remains authoritative for

- service and product definitions;
- material and finish options;
- compatibility rules that affect price;
- labour and material formulas;
- packages and add-ons;
- price resolutions;
- commercial floors and discounts.

### Titan Quotes remains authoritative for

- customer-facing commercial proposals;
- prices and terms presented for acceptance;
- quote versions;
- signatures, deposits and legal acceptance;
- variations.

A design approval inside Configurator means “this is the preferred visual option.” It is not a binding purchase until Titan Quotes records acceptance.

### Titan Supply remains authoritative for

- supplier products;
- stock;
- purchase orders;
- sourcing;
- actual material availability.

Configurator may propose a bill of materials but must not promise stock that Supply has not confirmed.

---

## 3. Target verticals

### Strong first verticals

- residential and commercial painting;
- roof restoration and roof coating;
- flooring and floor refinishing;
- landscaping and garden redesign;
- pressure cleaning and surface restoration;
- exterior cleaning and facade treatment;
- resurfacing and protective coatings;
- outdoor lighting;
- property-sale preparation;
- cabinetry and benchtop refinishing;
- fencing and exterior finishes;
- pool surrounds and outdoor areas.

### Later verticals

- solar layout and visual placement;
- accessibility modifications;
- signage and branding installation;
- commercial cleaning presentation standards;
- furniture and equipment placement;
- restoration option selection;
- maintenance-package comparison.

The initial product should focus on two-dimensional property images and controlled surface transformations. Full architectural design, structural engineering and dimensionally exact CAD are out of scope.

---

## 4. User roles

| Role | Responsibility |
|---|---|
| Configurator Owner | Product configuration, provider policy and governance |
| Sales Consultant | Creates projects and guides customers through options |
| Designer | Refines regions, produces visual states and comparisons |
| Estimator | Requests Pricebook resolution and checks commercial impact |
| Technical Reviewer | Confirms product, surface and application compatibility |
| Customer | Reviews, comments and selects preferred design |
| Project Manager | Converts approved visual state into work instructions |
| Field Supervisor | Uses approved visuals and raises site differences |
| Material Manager | Reviews proposed bill of materials |
| Branch Manager | Controls local catalogues and approval thresholds |
| Network Administrator | Publishes master products, templates and brand rules |
| Auditor | Reads versions, approvals, generation provenance and access history |

Separation of duties can require a designer, technical reviewer and commercial approver to be different users for high-value or high-risk projects.

---

## 5. Configuration modes

### 5.1 Colour and finish preview

Replace paint, coating, stain or roof-colour appearance while preserving geometry and non-target objects.

### 5.2 Material substitution

Preview flooring, pavers, cladding, benchtops, mulch, gravel, turf or similar material families.

### 5.3 Service-outcome preview

Show a bounded representation of cleaning, restoration, resurfacing, decluttering or property-preparation outcomes.

### 5.4 Package comparison

Compare essential, recommended and premium service packages visually and commercially.

### 5.5 Spatial option plan

Create editable diagrams for garden zones, lighting positions, treatment areas, work stages or equipment placement.

### 5.6 Customer-guided remote configuration

Let a customer upload property photos, complete capture guidance and review options without a site visit.

### 5.7 Technician-assisted configuration

A technician captures accurate images and measurements during an inspection, then a sales consultant prepares visual options later.

### 5.8 Variation visualisation

After an accepted quote, create a new visual state for a proposed change. The change becomes a Titan Quotes variation rather than overwriting the accepted design.

---

## 6. Core domain model

### 6.1 Project records

- `ConfigurationProject`
- `ProjectParticipant`
- `ProjectContextReference`
- `ProjectStatus`
- `ProjectPolicySnapshot`

### 6.2 Source and spatial records

- `SourceAsset`
- `SourceAssetDerivative`
- `View`
- `Space`
- `Surface`
- `EditableRegion`
- `RegionGeometry`
- `RegionRevision`
- `RegionDetection`
- `CalibrationReference`
- `Occlusion`

### 6.3 Catalogue and option records

- `OptionCatalogue`
- `OptionFamily`
- `VisualOption`
- `MaterialReference`
- `FinishReference`
- `ColourReference`
- `ServiceOutcomeReference`
- `CompatibilityRuleReference`
- `OptionAvailabilitySnapshot`

### 6.4 Configuration records

- `ConfigurationScenario`
- `ScenarioSelection`
- `VisualState`
- `GenerationRequest`
- `GenerationResult`
- `FidelityAssessment`
- `ComparisonSet`
- `CustomerComment`
- `DesignDecision`
- `ApprovedConfigurationVersion`

### 6.5 Handoff records

- `PricingContext`
- `PriceResolutionReference`
- `QuoteHandoff`
- `MaterialProposal`
- `WorkInstructionPackage`
- `FieldDifference`
- `VariationHandoff`

Every generated result must retain its source image, exact region geometry, selected option, provider, model, prompt template, seed where available and generation time.

---

## 7. State models

### 7.1 Project lifecycle

```text
Draft
→ Source capture
→ Region preparation
→ Option configuration
→ Customer review
→ Technical review
→ Design selected
→ Sent to quote
→ Commercially accepted
→ Released to operations
→ Completed
→ Archived
```

A project can be cancelled or placed on hold from most pre-acceptance states.

### 7.2 Visual state lifecycle

```text
Requested
→ Queued
→ Processing
→ Generated
→ Quality review
→ Approved for comparison
→ Rejected
→ Superseded
```

### 7.3 Design decision lifecycle

```text
Not selected
→ Shortlisted
→ Customer preferred
→ Technically approved
→ Sent to quote
→ Commercially accepted
→ Superseded by variation
```

### 7.4 Region lifecycle

```text
Detected
→ Needs refinement
→ Refined
→ Technically classified
→ Locked for generation
→ Superseded
```

Regions used by an approved configuration version are immutable. Later refinements create new region revisions.

---

## 8. Project creation

A project can begin from:

- a WorkCore customer and property;
- a Titan Quotes enquiry;
- a Titan Inspect result;
- a WorkCore lead;
- a standalone customer session;
- a technician’s site visit;
- a property portfolio campaign.

### Required project context

- tenant and branch;
- customer and property reference where available;
- service category;
- intended decision;
- source-capture method;
- privacy classification;
- technical reviewer policy;
- provider and AI policy;
- retention policy;
- linked quote or inspection references.

### Project templates

Templates can define:

- required views;
- capture guidance;
- expected surfaces;
- allowed option families;
- excluded transformations;
- technical approval requirements;
- default comparison layout;
- quote handoff mapping;
- visual disclaimers.

Examples include exterior paint, roof coating, floor finish, garden package and driveway restoration.

---

## 9. Source capture

### Supported sources

- phone or tablet photographs;
- professional photography;
- drone photographs where permitted;
- floor plans;
- inspection diagrams;
- video frames;
- customer-uploaded images;
- WorkCore attachments;
- Titan Inspect evidence;
- historical property images.

### Guided capture

The application can instruct the user to:

- stand at a suggested position;
- capture the full surface;
- avoid extreme wide-angle distortion;
- include a colour or scale reference;
- remove temporary obstructions where practical;
- capture alternate angles;
- avoid direct glare;
- photograph damaged or uncertain areas separately.

### Deterministic quality checks

Before AI analysis:

- file signature and malware validation;
- resolution and aspect-ratio checks;
- blur and exposure;
- duplicate detection;
- severe perspective distortion;
- heavy occlusion;
- missing required view;
- colour-reference detection where configured;
- metadata retention policy.

Low-quality media is not silently accepted. The system asks for a replacement or marks the preview as lower assurance.

---

## 10. Spatial grounding engine

`CreativeSuiteAnnotations` provides a valuable seed: images are normalised to a resolution-independent `0..1000` coordinate grid before the model returns bounding boxes. Titan Configurator generalises this beyond text.

### Detectable entities

- walls;
- ceilings;
- roofs and roof planes;
- doors and frames;
- windows;
- floors;
- paths and driveways;
- fences;
- garden beds;
- lawns;
- benchtops;
- cabinets;
- pools and surrounds;
- fixtures and assets;
- damage and exclusions;
- shadows and uncertain boundaries.

### Geometry types

- point;
- rectangle;
- polygon;
- freehand mask;
- line;
- path;
- grouped regions;
- negative region or exclusion mask.

### Detection policy

The vision model proposes regions. It does not automatically make them authoritative.

Each detection records:

- class;
- geometry;
- confidence;
- model;
- source dimensions;
- normalisation transform;
- reviewer status.

### Manual refinement

Users can:

- add or remove mask areas;
- snap to edges;
- feather boundaries;
- exclude windows, fittings or furniture;
- split a region;
- merge regions;
- label uncertain edges;
- lock approved geometry.

The final region is a governed project record, not an opaque image-editor layer.

---

## 11. Surface and asset classification

A visual region becomes commercially useful only when the system understands what it is.

### Classification attributes

- surface or asset type;
- current material;
- current finish;
- apparent condition;
- substrate;
- exposure;
- indoor or outdoor;
- estimated age where relevant;
- preparation requirement;
- known incompatibilities;
- confidence;
- evidence source.

Titan Inspect classifications take precedence when confirmed by an inspection. Visual-only classification remains provisional until reviewed if it affects safety, warranty or price.

### Examples

A wall may be classified as painted plaster, but the selected coating still requires confirmation of preparation and moisture condition.

A roof may visually resemble concrete tile, but the system must not promise a coating system until the material and condition are confirmed.

A floor may be shown with a timber finish, but the visual preview cannot prove the substrate is suitable.

---

## 12. Option catalogue

The option catalogue is consumed from Titan Pricebook and related product sources.

### Option types

- colours;
- finishes;
- materials;
- service levels;
- textures;
- coating systems;
- planting families;
- lighting styles;
- cleaning or restoration outcomes;
- optional features;
- package combinations.

### Option metadata

- name and code;
- supplier or manufacturer;
- visual swatch;
- reference images;
- expected appearance range;
- applicable surface types;
- exclusions;
- preparation dependencies;
- warranty references;
- environmental attributes;
- availability reference;
- Pricebook service or product reference;
- retirement date;
- replacement option.

### Compatibility rules

Configurator evaluates deterministic compatibility before generating a preview.

Examples:

- finish allowed only on selected substrate;
- product unavailable in a territory;
- dark roof colour requires technical review;
- service outcome cannot be previewed on severely damaged material;
- premium package requires an inspection;
- customer contract excludes certain materials;
- selected product is no longer supplied.

The system must not generate persuasive previews for options that the business cannot validly sell.

---

## 13. Visual transformation engine

`AdvancedImage` already routes editing requests across OpenAI, Freepik, Clipdrop, Novita, Fal and Nano Banana-style providers. It supports cleanup, relighting, style transfer, masks and selected-region editing. Titan Configurator converts this provider wrapper into a governed visual transformation service.

### Provider abstraction

Each provider adapter declares:

- supported operations;
- input limits;
- mask support;
- deterministic seed support;
- expected latency;
- cost;
- data-retention terms;
- geographic processing constraints;
- quality profile;
- retry policy.

### Operation types

- colour replacement;
- texture or material substitution;
- surface cleanup;
- controlled restoration preview;
- lighting normalisation;
- object removal only when explicitly allowed;
- landscaping insertion within approved regions;
- visual package composition;
- perspective-preserving inpainting.

### Prompt and mask contract

A generation request includes:

- immutable source asset;
- region revision;
- selected option;
- keep-unchanged constraints;
- prohibited modifications;
- expected fidelity level;
- provider policy;
- output dimensions;
- project disclaimer profile.

### Preserve constraints

The prompt and post-generation checks should protect:

- building geometry;
- windows and doors;
- permanent fixtures;
- property boundaries;
- structural elements;
- customer belongings unless intentionally removed;
- damage unless the selected service explicitly addresses it;
- camera viewpoint.

---

## 14. Generation orchestration

Visual generation is asynchronous and potentially expensive.

### Durable job lifecycle

```text
Accepted request
→ cost reserved
→ queued
→ provider submitted
→ provider processing
→ result retrieved
→ validation
→ project review
→ cost finalised
```

### Required reliability controls

- idempotency key;
- tenant and project scope;
- provider request ID;
- bounded retries;
- dead-letter state;
- webhook signature verification;
- polling backoff;
- timeout;
- failed-credit restoration;
- duplicate-result detection;
- cancellation where supported;
- cost telemetry.

The `CreativeSuiteAITemplate` donor job currently uses one try and cache-based status. Titan Configurator requires persistent job records and recovery after worker or cache failure.

### Provider fallback

Fallback is allowed only when:

- the selected operation is compatible;
- data policy permits the alternate provider;
- the customer has not restricted provider use;
- cost remains within policy;
- the result is clearly marked with its provider lineage.

---

## 15. Fidelity and assurance

A visual preview is not a guarantee of exact physical appearance.

### Fidelity dimensions

- geometry preservation;
- region containment;
- colour approximation;
- texture plausibility;
- material scale;
- lighting consistency;
- object preservation;
- boundary quality;
- source-photo quality;
- product compatibility confidence.

### Assurance bands

| Band | Meaning | Default action |
|---|---|---|
| High | Strong source, reviewed region, compatible option and preserved geometry | May be shown as a primary visual preview |
| Medium | Useful concept with visible uncertainty | Show with explicit limitations |
| Low | Major source, geometry or transformation uncertainty | Internal review only or request better evidence |

### Automated comparison

The system may compare source and output outside the selected mask to detect unintended change. Material differences outside allowed regions trigger rejection or human review.

### Disclaimers

Disclaimers must be specific, not generic legal noise.

Examples:

- colours vary with lighting, screen and physical product batch;
- the preview assumes the surface is suitable after inspection;
- planting size represents an approximate mature or installation state;
- cleaning results depend on material condition and permanent staining;
- the image is a design aid and the quote defines the contracted work.

---

## 16. Visual State Studio

Visual State Studio is the main workspace.

### Workspace elements

- source and generated views;
- layer and region panel;
- option catalogue;
- scenario panel;
- before-and-after slider;
- side-by-side comparison;
- cost summary from Pricebook;
- comments;
- fidelity indicators;
- version history;
- approval status.

### Editing rules

Users can:

- change options;
- adjust regions;
- regenerate a selected view;
- copy a scenario;
- lock a region across scenarios;
- create packages;
- add technical notes;
- mark a preview unsuitable;
- compare generation history.

Users cannot overwrite an approved visual version. They must create a revision.

---

## 17. Editable spatial documents

`CreativeSuiteAITemplate` generates structured Konva JSON and can use a generated or uploaded reference image. Titan Configurator should use this engine for diagrams and presentation, not as the sole source of configuration truth.

### Spatial document uses

- annotated project overview;
- room or surface map;
- numbered work zones;
- landscaping layout;
- lighting position plan;
- finish schedule;
- comparison board;
- customer-selection board;
- field work instruction.

### Safety model

- validate JSON schema;
- whitelist node types;
- limit external URLs;
- store structured business data separately;
- preserve source references;
- render server-side previews;
- sanitise text;
- version documents;
- enforce project ownership.

The current Creative Suite controllers update, show, duplicate, rename and delete documents by raw ID without visible ownership enforcement. Those controllers must not be reused unchanged.

---

## 18. Scenario and package builder

A scenario is a coherent set of selections across one or more regions.

### Example painting scenarios

- Essential: walls only, standard preparation, one colour family;
- Recommended: walls and trims, enhanced preparation, washable finish;
- Complete: walls, ceilings and trims, premium coating, minor repairs.

### Example landscaping scenarios

- Refresh: mulch, pruning and limited replacement planting;
- Rebalance: new planting zones and edging;
- Transform: full planting plan, lighting and surface changes.

### Package construction

Packages can include:

- visual state;
- scope reference;
- Pricebook service bundle;
- add-ons;
- exclusions;
- estimated duration;
- material family;
- warranty or maintenance reference.

Pricebook resolves price. Configurator only presents the returned result and reference.

---

## 19. Titan Pricebook integration

### Price request context

- service and option references;
- surface classifications;
- measured or estimated quantities;
- branch and territory;
- customer contract;
- package;
- preparation level;
- access factors;
- selected add-ons;
- date and operational context.

### Price response

Configurator stores:

- resolution ID;
- Pricebook version;
- customer-safe amount;
- tax presentation;
- package comparison;
- expiry;
- internal warnings;
- required approvals.

It does not reproduce the formula engine.

### Quantity uncertainty

When quantity is visually estimated rather than inspected, the price state must be labelled preliminary. A fixed commercial quote may require Titan Inspect confirmation.

---

## 20. Customer review portal

Customers can access a scoped project session without creating a full account.

### Portal capabilities

- view approved-for-customer scenarios;
- compare before and after;
- switch views;
- inspect package inclusions;
- see indicative or governed price;
- comment on exact regions;
- shortlist options;
- select a preferred design;
- request revision;
- upload an additional photo;
- hand off to Titan Quotes.

### Portal security

- hashed token;
- expiry;
- revocation;
- project and action scope;
- optional recipient verification;
- rate limits;
- audit trail;
- no sequential identifiers;
- no internal notes or provider prompts.

### Design approval

The portal clearly states:

> Selecting this design confirms your preferred visual option. The final scope, price and terms are accepted through the formal quote.

---

## 21. Comments and decisions

Comments can be attached to:

- project;
- view;
- surface;
- region;
- scenario;
- visual state;
- package.

### Comment types

- customer preference;
- question;
- technical concern;
- commercial concern;
- revision request;
- internal note;
- field clarification.

### Decision record

A design decision records:

- selected scenario;
- selected visual-state versions;
- customer identity;
- date;
- comments resolved or outstanding;
- technical reviewer;
- linked price resolution;
- quote handoff status.

---

## 22. Titan Quotes handoff

Configurator sends a structured package to Titan Quotes.

### Handoff contents

- customer and property reference;
- project and approved design version;
- selected scenario;
- structured scope mapping;
- Pricebook resolution references;
- option and product references;
- visual comparison assets;
- customer comments;
- technical approvals;
- disclaimers;
- proposed bill of materials;
- expiry and assumptions.

Titan Quotes may:

- create a new quote workspace;
- add the design as an option;
- include approved visuals in the proposal;
- require commercial approvals;
- collect legal acceptance and deposit.

If price or scope changes, Quotes requests a new handoff or records a variation. It does not silently edit the approved Configurator version.

---

## 23. Operational work instructions

After commercial acceptance, Configurator generates a field package.

### Package contents

- accepted visual state;
- source/after comparison;
- region map;
- selected product and finish codes;
- colour schedule;
- work zones;
- exclusions and preserve areas;
- preparation notes;
- sequence notes;
- customer-specific decisions;
- linked inspection evidence;
- linked quote version.

### Field differences

If the technician finds that the actual site differs from the configured state, they can record:

- new photograph;
- changed surface;
- hidden damage;
- colour mismatch concern;
- access issue;
- unavailable material;
- customer-requested change.

The system may trigger Titan Inspect, Titan Quotes variation or Titan Supply review. Field staff must not overwrite the accepted design.

---

## 24. Bill-of-material proposal

Configurator can estimate a material proposal from:

- selected options;
- inspected quantities;
- coverage rates from Pricebook;
- waste factors;
- package requirements;
- accessories and preparation products.

### Proposal status

- conceptual;
- inspection-based;
- estimator-reviewed;
- supplier-validated;
- released to purchasing.

Titan Supply remains responsible for actual SKU selection, stock, supplier and purchase order.

---

## 25. Remote customer configuration

A customer can begin without a site visit.

### Guided flow

1. Select service type.
2. Capture required property views.
3. Complete surface and preference questions.
4. Upload inspiration or product references.
5. Receive image-quality feedback.
6. Review proposed editable regions.
7. Compare allowed options.
8. Request professional review.
9. Select preferred design.
10. Continue to Titan Quotes.

### Remote limitations

The flow must identify when remote configuration is insufficient because:

- dimensions are required;
- the substrate is uncertain;
- damage is visible;
- access affects price;
- a regulated inspection is required;
- product compatibility cannot be confirmed;
- source images are inadequate.

---

## 26. Collaboration and approvals

### Approval types

- source quality approved;
- region approved;
- technical compatibility approved;
- visual fidelity approved;
- customer selection recorded;
- operational work instruction approved.

### Approval policies

Policies vary by:

- service;
- project value;
- material risk;
- branch;
- customer contract;
- remote versus inspected scope;
- AI assurance band.

No single approval should imply technical, commercial and legal approval simultaneously.

---

## 27. Versioning and audit

### Immutable version package

An approved version includes hashes for:

- source assets;
- region geometry;
- selected options;
- generated results;
- comparison layout;
- technical notes;
- Pricebook references;
- disclaimers;
- decision record.

### Audit events

- project created;
- source uploaded;
- region detected or edited;
- option selected;
- generation requested;
- provider result received;
- result rejected;
- technical approval;
- customer access;
- comment;
- design selection;
- quote handoff;
- work instruction release;
- export;
- deletion or retention action.

---

## 28. Privacy and property-image governance

Property imagery can reveal personal and security-sensitive information.

### Required controls

- private storage by default;
- tenant and project scope;
- explicit customer upload notice;
- face and licence-plate redaction;
- optional removal of personal photographs and documents;
- restricted access to alarm, key or security details;
- configurable retention;
- regional provider policy;
- no provider training use unless contractually permitted and explicitly enabled;
- export and deletion audit;
- legal hold support.

### Synthetic and edited image labelling

Generated previews must be labelled as edited or synthetic design visualisations. Original source evidence remains separately accessible to authorised staff.

---

## 29. AI assurance and human control

### Allowed AI functions

- propose regions;
- classify surfaces provisionally;
- generate controlled previews;
- compare source and output;
- suggest missing views;
- draft customer-safe option descriptions;
- produce visual boards;
- organise comments.

### Restricted AI functions

AI may not independently:

- confirm structural suitability;
- guarantee colour accuracy;
- certify substrate compatibility;
- set final quantities;
- alter price;
- issue a quote;
- accept on behalf of a customer;
- release materials for purchase;
- erase evidence of damage;
- publish a preview without configured review.

### Model provenance

Every operation records:

- provider;
- model;
- operation version;
- source and region IDs;
- input parameters;
- output;
- cost;
- reviewer decision.

---

## 30. Donor-engine map

| Donor extension | Reusable engine | Titan Configurator use | Required redesign |
|---|---|---|---|
| CreativeSuiteAnnotations | Normalised 0–1000 coordinates, structured vision and masks | Surface and editable-region grounding | Generalise beyond OCR, persist detections and add review/calibration |
| AdvancedImage | Multi-provider selected-region editing, cleanup, relighting and style operations | Controlled transformations | Durable jobs, tenant scope, provider policy, provenance and fidelity checks |
| CreativeSuiteAITemplate | AI-generated Konva JSON and reference-image analysis | Spatial boards and work instructions | Persistent state, schema validation, source linkage and retries |
| CreativeSuite | Konva editor, documents, layers, previews and templates | Visual State Studio and comparison boards | Ownership policies, immutable versions and structured domain separation |
| Canvas | Tiptap rich editing | Notes, option descriptions and instruction documents | Ownership enforcement and sanitisation |
| AIRealtimeImage | Rapid image iteration and status models | Fast concept previews | Provider-policy controls and project lineage |
| AIImagePro | Provider jobs, media library and generation management | Shared visual asset pipeline | Consolidate duplicate orchestration and secure shares |
| AIPhotoshoot | Product libraries, backgrounds, templates, replacement and async generation | Material and product presentation patterns | Reframe from ecommerce photography to project configuration |
| ProductPhotography | Background and product transformation | Simple supplier/product visual assets | Merge into shared media engine |
| FashionStudio | Reusable models, wardrobes, poses, backgrounds and try-on state | Reusable option/catalogue pattern | Remove fashion assumptions; retain option-state architecture |
| ContentManager | Media-selection pattern | Project asset library | Add real private persistence and retention |
| NanoBanana, FluxPro, SeeDreamV4, Midjourney | Provider-specific generation adapters | Optional provider adapters | Centralise secrets, webhooks, cost and retry policy |

---

## 31. Confirmed donor-code findings

The donor code contains useful concepts but must not be merged directly.

### Ownership and tenant risks

- `CreativeSuiteDocumentRequest::authorize()` returns `true`.
- Creative Suite update, show, duplicate, rename and delete actions retrieve documents by raw ID without visible user or tenant ownership enforcement.
- Canvas retrieves chat messages by raw `message_id` and writes associated Tiptap content without verifying ownership.

### Reliability risks

- Creative Suite AI template generation uses a cache entry as task state, has a single try and can lose durable workflow history.
- Multiple extensions implement overlapping polling, webhook and image-job logic.
- Provider failures need idempotent retries and credit restoration.

### Data and security risks

- provider keys require encrypted tenant-scoped storage;
- webhook signatures must be verified;
- public or community image patterns must not be reused for private property projects;
- arbitrary Konva/Tiptap payloads require schema validation and sanitisation.

### Product-integrity risks

- generic editing tools do not preserve commercial source, option and approval lineage;
- generated images can change non-target regions;
- prompt-driven results need deterministic compatibility checks before generation;
- outputs must not be treated as physically exact without assurance controls.

---

## 32. Integrations

### WorkCore

Consumes customers, properties, leads, jobs and staff. Publishes approved design references, work-instruction packages and field-difference events.

### Titan Inspect

Consumes source evidence, measurements, confirmed surfaces and findings. May request a new inspection when confidence is insufficient.

### Titan Pricebook

Consumes option catalogues, compatibility-linked commercial rules and price resolutions.

### Titan Quotes

Receives selected scenarios, visuals, scope mappings and price references. Returns commercial acceptance and variation state.

### Titan Supply

Receives proposed materials. Returns availability or substitution information.

### Titan Assets

Provides asset identity and installed-product context.

### Titan Property Twin

Can retain approved property spaces, surfaces and historical configuration states.

### Titan Handover

Receives final approved visuals, finish schedules and care instructions.

---

## 33. API and event model

### Example commands

- `CreateConfigurationProject`
- `AttachSourceAsset`
- `RequestRegionDetection`
- `RefineEditableRegion`
- `CreateScenario`
- `SelectVisualOption`
- `RequestPreviewGeneration`
- `ApproveVisualState`
- `RequestPriceResolution`
- `RecordCustomerDesignSelection`
- `SendConfigurationToQuote`
- `ReleaseWorkInstructionPackage`
- `RecordFieldDifference`

### Example events

- `ConfigurationProjectCreated`
- `SourceAssetAccepted`
- `EditableRegionApproved`
- `PreviewGenerated`
- `PreviewRejected`
- `ScenarioPriced`
- `CustomerDesignSelected`
- `ConfigurationSentToQuote`
- `ConfigurationCommerciallyAccepted`
- `WorkInstructionsReleased`
- `FieldDifferenceReported`

External commands require idempotency keys.

---

## 34. Analytics

### Commercial analytics

- project-to-quote conversion;
- quote acceptance by visualised versus non-visualised proposal;
- package mix;
- add-on adoption;
- average order value;
- revision count;
- time to design selection;
- abandonment stage.

### Operational analytics

- generation time;
- provider success rate;
- cost per approved visual;
- region-refinement time;
- technical-review workload;
- field differences;
- material substitutions;
- variation rate after design approval.

### Quality analytics

- previews rejected for non-target changes;
- source quality failures;
- fidelity band distribution;
- customer colour or material disputes;
- provider/model performance by operation;
- approved configuration versus delivered outcome review.

Analytics must not claim that visualisation alone caused a sale without suitable attribution controls.

---

## 35. Pricing

Pricing should support BYO AI keys and transparent managed usage.

| Plan | AUD/month | Included scope |
|---|---:|---|
| Core | $0 | 3 active projects, manual regions, 10 previews, BYO AI |
| Solo | $59 | 30 projects, 150 previews, customer portal, Pricebook and Quotes handoff |
| Team | $149 | 150 projects, 800 previews, collaboration, approvals and 3 branches |
| Pro | $399 | 750 projects, 4,000 previews, advanced catalogues, APIs and 15 branches |
| Network | $999 | 3,000 projects, 15,000 previews, central governance, white label and 50 locations |

### Managed usage

- generation credits based on actual provider operation and image size;
- no markup on BYO provider keys;
- failed provider jobs automatically restore credits when no usable result is delivered;
- optional premium fidelity review credits;
- additional storage priced transparently;
- private single-tenant deployment from $499/month additional.

### Vertical packs

Optional once-off or subscription packs can include:

- painter configuration templates;
- roof colours and finish schedules;
- flooring materials;
- landscaping visual packages;
- pressure-cleaning outcome templates;
- resurfacing and coating systems.

---

## 36. Delivery plan

### Phase 0 — Trust foundation

- tenant-scoped projects;
- private source assets;
- immutable versions;
- durable provider jobs;
- audit events;
- secure portal sessions;
- WorkCore, Inspect, Pricebook and Quotes contracts.

### Phase 1 — Complete controlled visual product

- project templates;
- source capture;
- manual regions and masks;
- option catalogue;
- selected-region transformation;
- scenarios and comparisons;
- customer portal;
- Pricebook pricing;
- design selection;
- Quotes handoff;
- work-instruction output.

This phase is saleable without automated region detection.

### Phase 2 — Vision assistance

- region proposals;
- surface classification;
- source-quality guidance;
- non-target change detection;
- fidelity assessment;
- remote customer capture.

### Phase 3 — Advanced vertical workflows

- material textures;
- landscaping spatial layouts;
- bill-of-material proposals;
- Product Twin history;
- variation visualisation;
- supplier option sync.

### Phase 4 — Network

- master option catalogues;
- manufacturer and franchise governance;
- white label;
- multi-location analytics;
- SSO;
- advanced APIs.

---

## 37. Testing strategy

### Unit tests

- project and version states;
- region geometry validation;
- compatibility rules;
- approval policies;
- token expiry;
- design-selection semantics;
- quote handoff mapping;
- credit restoration.

### Integration tests

- Titan Inspect evidence;
- Pricebook resolution;
- Quotes handoff;
- WorkCore property context;
- Supply material proposal;
- provider webhook and polling;
- private storage.

### Security tests

- cross-tenant access;
- raw-ID access;
- malicious image and document upload;
- Konva/Tiptap script injection;
- public-token guessing;
- revoked link access;
- provider credential leakage;
- unauthorised export.

### Visual evaluation

- region containment;
- geometry preservation;
- non-target change;
- colour approximation;
- texture scale;
- repeated-generation stability;
- provider comparison;
- low-quality source handling.

### End-to-end tests

- customer remote paint configuration to accepted quote;
- inspected roof configuration to work instruction;
- material option becomes unavailable before acceptance;
- approved design changed through variation;
- failed generation restores credits;
- field difference triggers review rather than silent overwrite.

---

## 38. Release gates

Titan Configurator must not enter production until:

- tenant isolation passes;
- private property images cannot be accessed through raw IDs;
- source and approved visual versions are immutable;
- public sessions are hashed, scoped, expiring and revocable;
- generated outputs preserve source lineage;
- compatibility rules run before previews are offered;
- customer design selection is clearly separated from quote acceptance;
- Pricebook remains the price authority;
- failed generation jobs recover safely;
- provider credentials are encrypted;
- webhook signatures are verified;
- non-target change checks and human review exist;
- accessibility and mobile portal checks pass;
- the product is useful with manual regions before AI automation is enabled.

---

## 39. Success metrics

### Commercial

- quote conversion uplift;
- average order value uplift;
- premium package adoption;
- reduced sales-cycle time;
- fewer lost opportunities due to indecision;
- revenue influenced by visual configuration.

### Operational

- reduced manual mock-up time;
- reduced site revisit for selections;
- reduced field clarification;
- fewer post-acceptance changes;
- faster quote handoff;
- lower visual-production cost.

### Trust and quality

- customer disputes about selected finish;
- non-target change rejection rate;
- preview-to-delivered discrepancy;
- source quality recapture rate;
- technical approval turnaround;
- customer understanding of visual limitations.

---

## 40. Strategic role in the Titan portfolio

Titan Configurator sits between evidence, pricing and commercial agreement.

```text
WorkCore customer and property
→ Titan Inspect confirms condition and quantity
→ Titan Configurator creates valid visual options
→ Titan Pricebook resolves governed prices
→ customer selects preferred design
→ Titan Quotes secures commercial acceptance
→ WorkCore receives executable work
→ Titan Handover preserves the delivered finish and care information
```

Its long-term moat is the growing relationship between:

- real property and surface types;
- compatible products and services;
- customer visual preferences;
- price and package decisions;
- approved visual states;
- actual delivered outcomes;
- later maintenance and replacement work.

---

## 41. Recommended implementation order within the app

1. Security, project and asset foundations.
2. Manual regions and masks.
3. Controlled selected-region generation.
4. Option catalogues and compatibility.
5. Scenarios and comparison portal.
6. Pricebook and Quotes handoff.
7. Work instructions and material proposals.
8. Automated region detection.
9. Fidelity and non-target-change evaluation.
10. Network and manufacturer governance.

---

## 42. Final recommendation

Build Titan Configurator as a complete visual decision and handoff application, with **controlled property-specific previews connected to real scope, governed pricing and field instructions** as the differentiator.

Do not lead with:

> Generate AI makeovers of your property.

Lead with:

> **Let customers see valid options on their own property, choose with confidence, and give your team an exact visual record of what was sold.**

The first release should support manual region preparation, because trustworthy human-refined masks are more commercially valuable than fast but unreliable automatic segmentation. Automated vision should then reduce preparation time without replacing technical review or commercial authority.
