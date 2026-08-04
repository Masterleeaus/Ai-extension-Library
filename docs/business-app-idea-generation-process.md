# Business App Idea Generation Process

## Objective

Create and maintain a code-grounded library of complete WorkCore business applications for field and home service businesses.

## Inputs

- Uploaded extension archives
- Existing WorkCore and Titan Zero architecture
- Current product portfolio documents
- Commercial constraints and pricing direction
- Explicit exclusions supplied for the research pass

## Roles

| Role | Responsibility |
|---|---|
| Research analyst | Inspect archives and identify technical evidence |
| Technical architect | Extract reusable engines and integration boundaries |
| Product designer | Convert engines into complete applications |
| Commercial reviewer | Test positioning, value measurement and pricing |
| Documentation maintainer | Create Markdown files and maintain the repository index |

# Procedure

## 1. Inspect the archive

1. Unpack and inventory all extension folders.
2. Exclude AIChatPro, AI Agent, Chatbot/PWA and TitanZeroChatbot unless explicitly included.
3. Count controllers, services, models, migrations, jobs, routes, tests and provider integrations.
4. Trace important workflows through controllers, services, persistence and queues.
5. Record confirmed security, tenancy, reliability and coupling risks.

## 2. Extract engines

Identify reusable technical primitives such as:

- coordinate-grounded computer vision;
- multi-model consensus;
- evidence and provenance;
- spatial and rich-document editing;
- commercial rules and pricing;
- geospatial discovery;
- communications and telephony;
- media editing and rendering;
- migration and data mapping;
- workflow automation and approval;
- subscription, prepaid and checkout;
- public sharing and moderation.

For each engine, record:

- donor extension and files;
- current behaviour;
- reusable domain-independent behaviour;
- host coupling;
- required hardening;
- likely WorkCore integration points.

## 3. Separate capability levels

Classify every concept as one of:

1. **Infrastructure** — shared platform capability.
2. **Feature** — a valuable action inside a larger product.
3. **Module** — a coherent product area that still depends on a parent app.
4. **Complete application** — owns durable records and an end-to-end outcome.

Do not promote a concept to application status merely because it is commercially attractive.

## 4. Test complete-app status

A complete application must answer all of the following:

- What durable records does it own?
- Who uses it and what roles exist?
- What begins the workflow?
- What completed outcome ends the workflow?
- What approvals, exceptions and failures occur?
- What reports and analytics are required?
- What WorkCore records does it read or create?
- Why can it be sold independently?
- Why is it not better treated as a feature of another app?

## 5. Design the application

Every application design must contain:

1. Positioning and customer promise.
2. Target verticals and user roles.
3. Central records and data ownership.
4. End-to-end workflow.
5. Major modules and differentiated features.
6. Donor engines and estimated reuse.
7. Required new domain engineering.
8. WorkCore integration and domain events.
9. Security, privacy and tenant controls.
10. Offline, queue and failure behaviour.
11. Analytics and value measurement.
12. AUD pricing and usage policy.
13. Build priority, dependencies and strategic moat.

## 6. Check for duplication

Before adding a new concept:

1. Search existing files under `workcore/`.
2. Compare central records and primary outcome.
3. Merge the concept into an existing application when it is only a feature.
4. Create a separate app only when the system of record and customer outcome are materially distinct.
5. Update feature-to-app maps when a concept moves.

## 7. Apply the quality gate

The concept passes only when:

- it is grounded in code evidence;
- assumptions are clearly labelled;
- required new work is not hidden;
- pricing reflects customer value and operational cost;
- security and tenancy requirements are explicit;
- WorkCore remains authoritative for shared operational records;
- it has a defensible data, workflow, network or operational moat;
- it does not depend on vanity AI features for its value.

## 8. Create the Markdown file

Use this structure:

```text
---
title: Product name
status: Seed | Designed | Validated | Prioritised | In development | Released
created: YYYY-MM-DD
updated: YYYY-MM-DD
source_archives:
  - archive.zip
---

# Product name
## Executive conclusion
## Positioning
## Target users and verticals
## Central records
## End-to-end workflow
## Modules
## Engine provenance
## WorkCore integration
## Security and governance
## Analytics
## Pricing
## Build sequence
## Risks and unresolved questions
```

Use lowercase kebab-case filenames, for example:

- `titan-inspect-deep-design.md`
- `titan-quotes-deep-design.md`
- `property-service-app-ideas.md`
- `shared-engine-architecture.md`

## 9. Commit and index

1. Save the file under `workcore/`.
2. Use a descriptive commit message.
3. Update `workcore/README.md` with the document title, relative link, status, source and date.
4. Update an existing file rather than creating a near-duplicate.
5. Preserve earlier reasoning through version history instead of deleting it without explanation.

# Recommended output sequence

For a large archive, produce documents in this order:

1. Engine inventory and evidence map.
2. Complete app portfolio.
3. Ranked opportunity matrix.
4. Shared engine architecture.
5. Individual deep product designs.
6. Cross-app authority and event map.
7. Build roadmap and commercial packaging.

# Completion criteria

A research pass is complete when:

- relevant extension code has been inspected;
- hidden engines have been identified;
- feature ideas have been assigned to complete parent applications;
- new applications meet the complete-app test;
- pricing and integration are documented;
- Markdown files are committed to the repository;
- the WorkCore index is current.
