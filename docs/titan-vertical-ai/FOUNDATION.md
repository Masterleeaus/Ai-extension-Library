# Titan Vertical AI Context Engine Foundation

## Purpose

The Titan Vertical AI Context Engine connects three existing authorities:

1. the canonical WorkCore persisted wizard runtime;
2. the Titan Interaction Engine context and AI capabilities; and
3. the Titan vertical-overlay system that controls terminology, theme tokens, navigation, workspaces, questions, forms, checklists, widgets and AI instructions.

It lets Titan Zero begin with a neutral business setup and become progressively more specific as a user answers onboarding questions. The result is a reviewed draft of the business experience and a governed activation plan. It is not a second settings database, a second wizard runtime or an AI-controlled write path.

## Source material

The uploaded donor extensions, wizard catalogues, scan packs and integrated MagicAI/WorkCore source archive are recorded in:

- `docs/titan-vertical-ai/SOURCE-ASSETS.md`
- `docs/titan-vertical-ai/source-assets.json`

Large binaries remain unlisted MiniUp project assets. Git stores hashes, file paths and reassembly instructions rather than duplicate archives.

## Non-negotiable authority boundaries

| Concern | Authoritative owner | Context-engine responsibility |
|---|---|---|
| Authentication, tenant selection, plans, extension lifecycle and provider credentials | MagicAI | Consume resolved actor, company and entitlements; never store credentials |
| Wizard definitions, immutable run snapshot, answers, provenance, approvals and run status | WorkCore Wizards | Read through an adapter, compose context and write proposals beside—not instead of—canonical state |
| Company, catalogue, territories, availability, workforce, finance, notifications and compliance records | Canonical WorkCore domains | Compile approved activation actions against existing domain actions |
| Generic context composition, local guidance, provider-neutral AI invocation and proposal validation | Titan Interaction Engine | Own reusable DTOs, composition, proposal contracts and AI bridge |
| Vertical family, subtype and capability pack definitions | Titan vertical registry | Resolve data-only overlays through stable schemas |
| Theme rendering, menu rendering and role workspaces | Titan platform shell plus bounded adapters | Produce typed patches and previews; never replace the shell or permissions |
| Live payments, outbound messages, external connections and destructive changes | Their existing domain authorities | Produce explicit secure tasks or approval-gated commands only |

No domain may have two independent write authorities. A compatibility donor may import or project data, but it may not become a second source of truth.

## Existing systems that remain in place

### WorkCore persisted wizard runtime

The WorkCore wizard module remains the canonical runtime for company-scoped definitions, runs, answers, branching, approvals, audit events and completion. Its current `WizardRuntime` is extended through adapters and events; it is not replaced by `UniversalWizardEngine`.

### Titan Interaction Engine

`packages/titan-interaction-engine` supplies generic context providers, AI service contracts, local intelligence, policies, command boundaries and offline behaviour. New vertical intelligence belongs here when it is provider-neutral and database-neutral.

### Universal Wizard Engine

The Universal Wizard Engine remains useful for lightweight interaction templates and offline commands. It is not the authority for WorkCore company-launch runs. Integration occurs through shared contracts and adapters rather than duplicated sessions.

### Titan vertical overlays

Vertical packs are declarative. They may contribute:

- terminology;
- semantic theme tokens;
- stable navigation configuration;
- role workspace metadata;
- question overlays;
- forms and checklists;
- widgets;
- AI instructions and allowed proposal paths;
- action mappings;
- public-content presets;
- compliance/evidence rules.

They may not replace authentication, permissions, the platform shell, offline runtime, canonical routes or operational safety states.

## Resolved-context model

The effective context is composed in this exact order:

```text
platform base
→ country and regulatory layer
→ primary vertical family
→ business subtype
→ selected capability and commerce overlays
→ canonical tenant answers and branding
→ actor role and device
→ accessibility preferences
→ operational state
```

Operational state is last and strongest. Safety, compliance, permission denial, offline, conflict, failure and disabled states cannot be erased by branding, tenant preference or AI output.

### Layer contract

Every context layer has:

```json
{
  "id": "vertical.cleaning",
  "kind": "subtype",
  "version": "1.0.0",
  "precedence": 300,
  "source": "vertical-pack:cleaning@1.0.0",
  "values": {},
  "constraints": {},
  "generated_at": "ISO-8601"
}
```

Allowed `kind` values are:

- `platform_base`
- `country`
- `vertical_family`
- `subtype`
- `capability`
- `tenant`
- `role_device`
- `accessibility`
- `operational_state`

Layer IDs are unique within one composition. Versions are immutable semantic versions. Precedence is validated against the kind; callers cannot assign an arbitrary priority to bypass boundaries.

### Snapshot contract

The generic immutable PHP DTO is named `VerticalContextSnapshot`. A resolved snapshot has this stable top-level shape:

```json
{
  "schema_version": 1,
  "context_id": "ctx_...",
  "company_id": 42,
  "wizard": {
    "run_id": "run_...",
    "definition_key": "company-launch-onboarding",
    "definition_version": 3,
    "answer_revision": 18,
    "status": "in_progress"
  },
  "actor": {
    "id": 7,
    "type": "user",
    "roles": ["owner"],
    "device": "desktop"
  },
  "locale": {
    "country": "AU",
    "language": "en-AU",
    "timezone": "Australia/Sydney",
    "currency": "AUD"
  },
  "layers": [],
  "resolved": {
    "terminology": {},
    "theme": {},
    "navigation": {},
    "workspaces": {},
    "capabilities": {},
    "questions": {},
    "forms": {},
    "checklists": {},
    "widgets": {},
    "ai": {},
    "activation": {}
  },
  "sources": {},
  "approvals": {},
  "unanswered": [],
  "constraints": {},
  "context_hash": "sha256",
  "generated_at": "ISO-8601"
}
```

`context_hash` is computed from canonical JSON that excludes volatile timestamps. Identical effective inputs therefore produce identical hashes.

### Value provenance

Each resolved leaf has metadata in the `sources` map, keyed by JSON Pointer:

```json
{
  "/resolved/terminology/entity.customer.singular": {
    "source": "wizard-answer:business.customer_types",
    "source_type": "user_entered",
    "confidence": 1.0,
    "confirmed": true,
    "risk": "low",
    "revision": 18
  }
}
```

Accepted source types are:

- `system_default`
- `vertical_pack`
- `existing_company_data`
- `imported`
- `user_entered`
- `ai_extracted`
- `ai_suggested`

The server assigns source type and confidence. Browser payloads cannot assert AI provenance or confirmation.

## Generic vertical base

Every company starts with `generic-business`, even when no vertical pack matches. The base supplies neutral semantic keys and safe defaults.

### Neutral terminology

```json
{
  "entity.customer.singular": "Customer",
  "entity.customer.plural": "Customers",
  "entity.work.singular": "Work item",
  "entity.work.plural": "Work items",
  "entity.location.singular": "Location",
  "entity.location.plural": "Locations",
  "entity.worker.singular": "Team member",
  "entity.worker.plural": "Team members",
  "entity.asset.singular": "Asset",
  "entity.asset.plural": "Assets",
  "action.schedule": "Schedule",
  "action.complete": "Complete"
}
```

Global text replacement is prohibited. UI copy resolves semantic keys. Technical route names, namespaces, table names, configuration keys and migration history are never renamed for display language.

### Neutral theme

The base uses semantic tokens only:

- brand and accent references;
- surface and text roles;
- density;
- typography families and weights;
- component variants;
- success, warning, danger, information, offline, conflict, permission and disabled states.

Raw CSS, arbitrary HTML and executable scripts are not accepted as vertical or AI patches.

### Neutral navigation and workspaces

The base registers stable navigation keys and slots. A vertical may rename labels, reorder allowed keys, hide optional entries and register bounded entries. It cannot replace the application shell or permission checks.

Workspaces change prioritisation and presentation, not authorisation. A field technician workspace can prioritise Today, Jobs and Evidence, but it cannot grant access to payroll or company settings.

## AI proposal boundary

AI is advisory. AI may infer, draft and propose only inside declared paths. It receives a redacted context snapshot, a list of unresolved questions, allowed proposal paths, risk policy, prompt version and budget. It returns structured proposals.

### Proposal request

```json
{
  "schema_version": 1,
  "context_hash": "sha256",
  "answer_revision": 18,
  "allowed_sections": ["terminology", "theme", "navigation", "questions"],
  "unanswered": ["catalogue.service_description"],
  "constraints": {
    "forbidden_paths": ["/resolved/permissions", "/resolved/activation/live"],
    "max_operations": 25
  },
  "context": {}
}
```

### Proposal response

```json
{
  "proposal_id": "proposal_...",
  "context_hash": "sha256",
  "answer_revision": 18,
  "prompt_version": "vertical-context-v1",
  "provider": "configured-provider",
  "model": "configured-model",
  "operations": [
    {
      "op": "set",
      "path": "/resolved/terminology/entity.work.singular",
      "value": "Job",
      "reason": "The business selected mobile cleaning services.",
      "confidence": 0.91,
      "risk": "low",
      "requires_confirmation": true
    }
  ]
}
```

Allowed operations are:

- `set`
- `append_unique`
- `remove_optional`
- `suggest_question`
- `skip_optional_question`
- `rephrase_question`

AI cannot:

- execute tools or domain actions;
- write canonical records;
- approve its own proposals;
- remove mandatory questions;
- change permissions or entitlements;
- connect providers or handle credentials;
- initiate payments or outbound messages;
- activate a company;
- perform destructive actions;
- replace operational safety state.

Malformed, stale, forbidden or over-budget responses are rejected before persistence.

## Answer-driven customisation flow

```text
user submits answer
→ WorkCore validates and persists canonical answer
→ WorkCore assigns provenance and answer revision
→ affected approvals are invalidated
→ WizardAnswerChanged event is emitted
→ deterministic context is recomposed
→ non-AI vertical preview revision is stored
→ bounded AI enrichment is queued
→ AI response is schema- and policy-validated
→ stale context hashes are rejected
→ accepted operations create a new draft preview revision
→ user reviews edits and approvals
→ approved snapshot compiles to an activation plan
→ activation saga invokes canonical domain actions
→ each result is recorded
→ company/vertical becomes active only after required steps succeed
```

Answer persistence does not wait for cloud AI. When AI is disabled, unavailable or over budget, the deterministic generic/vertical context remains fully usable.

## Question composition

Company-launch questions are composed from:

```text
generic company launch
+ country overlay
+ vertical family overlay
+ subtype overlay
+ selected capability overlays
+ tenant custom questions
```

Question keys remain semantic and stable. Layers may:

- add optional questions in declared slots;
- refine labels, help text, choices and examples;
- add branch conditions;
- attach action mappings and evidence requirements;
- declare dependencies used for targeted recomposition.

Layers may not remove mandatory questions for identity, privacy, payments, permissions, compliance, evidence, recording, activation or destructive changes.

When a run starts, WorkCore stores the complete composed definition snapshot and schema version. Active runs never silently load a newer pack version.

## Preview patches

The context engine produces typed, reviewable patches for:

- terminology;
- theme tokens;
- navigation;
- role workspaces;
- contextual introductions;
- targeted announcements;
- feature visibility;
- forms and checklists;
- widgets;
- AI prompts/instructions;
- public-content presets.

Each patch includes before, after, source, confidence, risk, affected surfaces, required approval and compatibility impact. A patch never contains raw SQL, PHP, Blade, JavaScript, CSS or credentials.

## Approval and risk model

| Risk | Default treatment | Examples |
|---|---|---|
| Low | Save as draft; user may batch-confirm | wording, optional labels, suggested descriptions |
| Medium | Section confirmation | navigation visibility, workflow defaults, evidence suggestions |
| High | Explicit approval tied to exact revisions | payment terms, permissions, recording, customer privacy, external messaging |
| Critical | Blocked from wizard/AI execution | maintenance mode, database restore, destructive platform administration |

An approval records:

- run ID;
- section key;
- answer revision;
- proposal revision;
- context hash;
- summary hash;
- approving actor;
- approval time.

Changing any covered answer or proposal invalidates that approval.

## Secret and untrusted-content handling

API keys, passwords, banking credentials, service-account documents and provider tokens are never ordinary wizard answers. The wizard launches a secure connection component and stores only status, provider reference and last verification time.

Uploaded documents and website content are untrusted knowledge. Extraction results carry source references and cannot change authority rules, allowed paths or system instructions.

Prompts exclude secrets and redact sensitive personal data not needed for the proposal. Logs record hashes and metadata, not full confidential payloads by default.

## Activation boundary

Wizard completion first produces an immutable `ActivationPlan`; it does not immediately mark every configuration live. This replaces the current `canonical_workcore_action_adapter_required` placeholder with explicit, testable action mappings.

An activation plan contains ordered steps such as:

- `workcore.company.update`
- `workcore.catalogue.category.create`
- `workcore.catalogue.service.create`
- `workcore.territory.create`
- `workcore.availability.rule.create`
- `workcore.worker.create`
- `workcore.worker.skill.assign`
- `workcore.finance.payment_policy.update`
- `workcore.notification.policy.update`
- `workcore.compliance.evidence_rule.create`
- `titan.vertical.apply`

Every step declares:

- idempotency key;
- required or optional status;
- authority owner;
- validated payload hash;
- preconditions;
- retry policy;
- compensation strategy when safe;
- result status.

External connections remain explicit secure tasks. Final activation requires current approvals and success of every required step.

## Failure behaviour

### Context composition failure

- Do not lose the canonical answer.
- Record the failure with correlation ID.
- Fall back to the last valid snapshot or neutral generic base.
- Block activation until a current valid snapshot exists.

### AI failure

- Do not block answer saving or deterministic preview.
- Mark enrichment unavailable or retryable.
- Never invent a proposal.

### Stale AI response

- Compare context hash and answer revision.
- Reject without modifying the current preview.
- Record a superseded result for audit.

### Activation failure

- Stop dependent required steps.
- Preserve successful step results.
- Retry only retryable steps with the same idempotency key.
- Compensate only when the domain action explicitly supports it.
- Keep the company in draft/partial state and show the exact failure.

## Compatibility strategy

### Donor roles

- `LiveCustomizer`: safe theme/font import into typed semantic tokens.
- `Menu`: stable-key navigation import and migration source.
- `MegaMenu`: separate public-site navigation adapter.
- `FocusMode`: role workspace preset donor; never a permission authority.
- `Introduction`: contextual guidance/tour donor.
- `OnboardingPro`: entity onboarding and Titan Learn/induction donor.
- `Announcement`: targeted notice donor.
- `ContentManager`: bounded asset-picker donor.
- `Maintenance`: separate operational extension.
- `DiscountManager`: separate commercial/pricing extension.

Existing folders, namespaces, slugs, route names, configuration keys, migrations and data are preserved. Import is idempotent, tenant-scoped, previewable and reversible where the donor supports a safe rollback.

### Repository source ownership

- Generic contracts and composition live in `packages/titan-interaction-engine`.
- WorkCore persistence adapters and activation mappings live in the canonical `workcore-business-network` package under `app/extensions/WorkCore_Platform/packages`.
- Materialised mirrors are generated through existing repository workflows and are not edited independently.
- UI changes reuse the closest existing wizard/review/settings surfaces.

## Caching and performance

Context cache keys include:

- company ID;
- wizard run ID;
- immutable definition version;
- answer revision;
- vertical pack versions;
- actor role/device class;
- accessibility profile;
- operational-state revision.

Cache entries store redacted snapshots only. Answer events invalidate only affected company/run keys. AI enrichment runs asynchronously and uses a context hash to reject stale results.

## Audit events

Minimum events:

- `wizard.answer.saved`
- `wizard.approval.invalidated`
- `vertical.context.composed`
- `vertical.context.failed`
- `vertical.proposal.requested`
- `vertical.proposal.accepted`
- `vertical.proposal.rejected`
- `vertical.preview.revised`
- `vertical.activation.planned`
- `vertical.activation.step_started`
- `vertical.activation.step_succeeded`
- `vertical.activation.step_failed`
- `vertical.activation.completed`

Every event includes tenant/company, actor, run, correlation, causation, definition version, answer revision and privacy class where applicable.

## Test strategy

### Generic package tests

- deterministic precedence and canonical JSON;
- nested merge and provenance;
- invalid layers and forbidden paths;
- safety-state precedence;
- proposal schema and stale-response rejection;
- provider-disabled fallback.

### WorkCore adapter tests

- tenant isolation;
- immutable definition snapshot resolution;
- answer/provenance/approval mapping;
- secret exclusion;
- approval invalidation;
- activation plan mapping, idempotency, retry and partial failure.

### Vertical pack tests

- schema validation;
- dependency and conflict validation;
- stable semantic keys;
- mandatory-question protection;
- no shell or permission replacement;
- nine family profiles and generic fallback.

### End-to-end tests

- answer changes preview;
- AI disabled and AI failure paths;
- stale AI response;
- review and approval;
- activation success and recoverable failure;
- upgrade from donor settings;
- mobile/tablet/desktop and accessibility states.

## Delivery sequence

Implementation follows GitHub issues #302 through #313 in order. Each issue has its own branch or task branch, failing tests first where code changes, fresh verification, reviewed PR, merge and issue closure before dependent work begins.

The complete executable plan is stored in:

`docs/superpowers/plans/2026-08-05-titan-vertical-ai-context-engine.md`

## Definition of done

- WorkCore remains the canonical wizard and operational authority.
- The generic context engine works without AI and without a selected vertical.
- AI changes only validated draft proposals.
- Context adapts as canonical answers change.
- Approvals bind to exact revisions and become stale when inputs change.
- Approved context compiles into durable canonical actions.
- All nine vertical families share one schema and runtime.
- Donor extensions migrate through bounded adapters.
- Source archives remain verifiable through the MiniUp manifest.
- Tests, CI, upgrade/rollback documentation and deterministic release packages are complete.
