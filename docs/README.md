# WorkCore Product Documentation

This folder stores engine-first product research, complete business-application concepts and deeper product designs for WorkCore and Titan Zero.

## Current documents

| Document | Status | Source | Updated |
|---|---|---|---|
| [Document Corpus Index](./DOCUMENT-INDEX.md) | Active full-text index | Every Markdown document on `main` | 2026-08-03 |
| [Conversation Engine Corpus Map](./conversation-engine-corpus-map.md) | Evidence-backed synthesis | Full `main` document corpus | 2026-08-03 |
| [WorkCore Field and Home Service Business App Portfolio](./workcore-business-app-ideas.md) | Designed | `Extensions.zip`, `Marketing.zip` | 2026-08-02 |
| [Titan Inspect Deep Product Design](./titan-inspect-deep-design.md) | Designed and code-grounded | `Extensions.zip` | 2026-08-02 |
| [Titan Pricebook Deep Product Design](./titan-pricebook-deep-design.md) | Designed and code-grounded | `Extensions.zip` | 2026-08-02 |
| [Titan Quotes Deep Product Design](./titan-quotes-deep-design.md) | Designed and code-grounded | `Extensions.zip` | 2026-08-02 |
| [Titan Academy Deep Product Design](./titan-academy-deep-design.md) | Designed and code-grounded | `Extensions.zip` | 2026-08-02 |
| [Titan Configurator Deep Product Design](./titan-configurator-deep-design.md) | Designed and code-grounded | `Extensions.zip` | 2026-08-02 |
| [Business App Idea Generation Process](./business-app-idea-generation-process.md) | Active process | Process Documentation AI workflow | 2026-08-02 |

Machine-readable corpus metadata is maintained in [`document-index.json`](./document-index.json).

## Scope rules

- Inspect extension code rather than relying on product labels.
- Exclude AIChatPro, AI Agent, Chatbot/PWA and TitanZeroChatbot unless explicitly requested.
- Treat isolated capabilities as features or modules until they form a complete application.
- Every complete app must define its central records, end-to-end workflow, roles, WorkCore integration, security requirements, analytics and AUD pricing.
- Reuse shared engines rather than copying extension implementations into multiple products.
- Keep WorkCore as the operational authority for customers, properties, jobs, staff, schedules, invoices, payments and general tasks.
- Specialist applications own only their bounded domains.
- When Titan Quotes is installed, it owns quote workspaces, immutable versions, approvals and acceptance while WorkCore stores the accepted commercial projection linked to operational work.
- Titan Academy owns SOPs, learning, assessments, competency decisions and skills passports; WorkCore owns people, jobs and assignments, while Titan Credential Vault will own external licences and statutory documents.
- Titan Configurator owns source-linked visual projects, editable regions, scenarios, generated states and design decisions; Titan Inspect owns condition evidence, Titan Pricebook owns price resolution, Titan Quotes owns commercial acceptance and WorkCore owns operational delivery.

## Planned deeper design files

- `titan-property-twin-deep-design.md`
- `shared-engine-architecture.md`
- `titan-conversation-engine-deep-design.md`

## Index maintenance

- Add every new Markdown document to `DOCUMENT-INDEX.md` and `document-index.json`.
- Use repository paths, not scan numbers, as stable identifiers.
- Update topic mappings when authority, implementation status or primary outcome changes.
- Preserve historical scans and use reconciliation documents to supersede outdated status statements.
- Re-run a full corpus read after a major product, integration or shared-engine addition.
- Record cross-document conflicts explicitly instead of silently selecting one version.

## Status vocabulary

- **Seed** — promising concept not yet deeply developed.
- **Designed** — complete application design exists.
- **Validated** — supported by code evidence and commercial rationale.
- **Prioritised** — accepted into the product roadmap.
- **In development** — implementation has begun.
- **Released** — usable production version exists.
