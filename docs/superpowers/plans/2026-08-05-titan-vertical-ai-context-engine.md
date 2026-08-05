# Titan Vertical AI Context Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Connect the canonical WorkCore onboarding wizard to a generic, provider-neutral vertical context engine that adapts terminology, theme, navigation, workspaces and questions as answers are saved, while keeping AI advisory and activation governed.

**Architecture:** Generic context DTOs, composition and AI proposal contracts live in `packages/titan-interaction-engine`. WorkCore-specific persistence, immutable wizard snapshots, approval invalidation and activation mappings live in the canonical `workcore-business-network` package under `app/extensions/WorkCore_Platform/packages`. Data-only vertical packs compose a neutral base, country, family, subtype and capability layers; approved snapshots compile into canonical WorkCore and Titan vertical actions.

**Tech Stack:** PHP 8.2+, Laravel 10/11/12-compatible Illuminate components, JSON schemas, existing Titan Interaction Engine test harness, WorkCore package tests, GitHub Actions, deterministic ZIP tooling and MiniUp source-asset manifests.

## Global Constraints

- WorkCore Wizards remain authoritative for definitions, immutable run snapshots, answers, provenance, approvals and run status.
- Do not install or persist a second wizard runtime for company launch.
- Titan Interaction Engine owns provider-neutral context and AI proposal contracts only.
- AI output is a draft proposal; it never directly writes canonical records or approves itself.
- Secrets, bank credentials, API keys, service-account files and provider tokens never enter wizard answers, AI prompts or proposal payloads.
- Context precedence is: platform base → country → vertical family → subtype → capability → tenant → role/device → accessibility → operational state.
- Safety, compliance, permission, offline, conflict, failure and disabled states override branding and AI suggestions.
- Use stable semantic keys; prohibit global text replacement, raw CSS, arbitrary HTML and technical identifier renames.
- Every query, event, cache key, proposal, preview and activation result is company/tenant scoped.
- Every code task uses red–green–refactor and fresh verification before commit, PR, merge or issue closure.
- Preserve existing extension folders, namespaces, slugs, route names, configuration keys, migrations and data.
- Edit canonical package roots and use existing materialisation workflows for generated mirrors.
- Reuse or duplicate the closest existing UI files; do not add a parallel visual system.

---

## File Ownership Map

### Generic context and AI contracts

`packages/titan-interaction-engine/src/Vertical/`

- `Contracts/VerticalContextProviderInterface.php` — returns one validated layer.
- `Contracts/VerticalAiAdvisorInterface.php` — returns validated draft proposals.
- `DTO/ContextValueProvenance.php` — immutable leaf-source metadata.
- `DTO/VerticalContextLayer.php` — immutable input layer.
- `DTO/VerticalContextSnapshot.php` — immutable resolved snapshot.
- `DTO/VerticalProposalRequest.php` — redacted request to AI.
- `DTO/ProposalOperation.php` — one allowed patch operation.
- `DTO/VerticalPatchProposal.php` — complete AI proposal.
- `GenericVerticalBaseProvider.php` — neutral safe defaults.
- `VerticalContextComposer.php` — deterministic resolver and canonical hash.
- `VerticalProposalValidator.php` — schema, path, risk and stale-context validation.
- `AiServiceVerticalAdvisor.php` — adapter to existing `AIServiceInterface`.

### WorkCore wizard bridge and activation

`app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/Wizards/`

- `Contracts/WizardVerticalContextRepositoryContract.php`
- `Events/WizardAnswerChanged.php`
- `Services/WizardVerticalContextAdapter.php`
- `Services/WizardContextProjectionService.php`
- `Services/WizardQuestionComposer.php`
- `Services/WizardApprovalInvalidator.php`
- `Services/WizardPlanCompiler.php`
- `Services/WizardActionMapperRegistry.php`
- `Services/WizardActionExecutor.php`
- `Services/WizardActivationSaga.php`
- `DTO/ActivationPlan.php`
- `DTO/ActivationStep.php`
- `Repositories/DatabaseWizardVerticalContextRepository.php`

### Vertical packs

`vertical-packs/`

- `schemas/base-pack.schema.json`
- `schemas/vertical-pack.schema.json`
- `schemas/question-overlay.schema.json`
- `generic-business/`
- `countries/au/`
- `families/<family>/`
- `subtypes/cleaning/`
- `templates/new-subtype/`

### Documentation and releases

- `docs/titan-vertical-ai/`
- `docs/vertical-pack-authoring/`
- `tools/build_titan_vertical_ai_release.py`
- `tools/validate_titan_vertical_ai_release.py`
- `.github/workflows/titan-vertical-ai.yml`

---

### Task 1: Architecture, authority and compatibility baseline — Issue #302

**Files:**
- Create: `docs/titan-vertical-ai/FOUNDATION.md`
- Create: `docs/superpowers/plans/2026-08-05-titan-vertical-ai-context-engine.md`
- Existing: `docs/titan-vertical-ai/SOURCE-ASSETS.md`
- Existing: `docs/titan-vertical-ai/source-assets.json`

**Interfaces:**
- Produces the names, ownership boundaries, schemas and sequencing consumed by issues #303–#313.

- [ ] **Step 1: Verify repository evidence paths**

Read and record the current contracts from:

```text
packages/titan-interaction-engine/src/Context/ContextBuilder.php
packages/titan-interaction-engine/src/AI/AIServiceInterface.php
packages/titan-interaction-engine/src/Wizard/UniversalWizardEngine.php
app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/Wizards/Services/WizardRuntime.php
app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/Wizards/Services/WizardDefinitionRegistry.php
docs/titan-hub/FOUNDATION.md
```

Expected: the evidence confirms two existing wizard implementations, one provider-neutral AI interface, a generic `ContextBuilder`, and WorkCore’s persisted run authority.

- [ ] **Step 2: Write the foundation document**

Document:

```text
authority matrix
layer precedence
context snapshot schema
value provenance
AI proposal schema
answer-driven event flow
question composition
approval/risk model
secret handling
activation saga
failure behaviour
compatibility donors
test strategy
definition of done
```

- [ ] **Step 3: Write this implementation plan**

Ensure each issue has exact file ownership, interfaces, failing tests, implementation steps, verification commands and merge boundaries.

- [ ] **Step 4: Run documentation self-review**

Run:

```bash
python - <<'PY'
from pathlib import Path
files = [
    Path('docs/titan-vertical-ai/FOUNDATION.md'),
    Path('docs/superpowers/plans/2026-08-05-titan-vertical-ai-context-engine.md'),
]
for path in files:
    text = path.read_text()
    assert ('T' + 'BD') not in text
    assert ('TO' + 'DO') not in text
    assert 'canonical_workcore_action_adapter_required' in text or path.name == 'FOUNDATION.md'
    fence_count = sum(1 for line in text.splitlines() if line.startswith('`' * 3))
    assert fence_count % 2 == 0
print('documentation contract checks passed')
PY
```

Expected: `documentation contract checks passed`.

- [ ] **Step 5: Verify source manifest JSON**

Run:

```bash
python -m json.tool docs/titan-vertical-ai/source-assets.json >/dev/null
```

Expected: exit 0.

- [ ] **Step 6: Commit and open PR**

```bash
git add docs/titan-vertical-ai docs/superpowers/plans/2026-08-05-titan-vertical-ai-context-engine.md
git commit -m "docs: define Titan Vertical AI context architecture"
```

Open a PR against `main` with `Closes #302` and merge only after the branch diff and documentation checks are verified.

---

### Task 2: Generic vertical base and deterministic context composer — Issue #303

**Files:**
- Create: `packages/titan-interaction-engine/src/Vertical/Contracts/VerticalContextProviderInterface.php`
- Create: `packages/titan-interaction-engine/src/Vertical/DTO/ContextValueProvenance.php`
- Create: `packages/titan-interaction-engine/src/Vertical/DTO/VerticalContextLayer.php`
- Create: `packages/titan-interaction-engine/src/Vertical/DTO/VerticalContextSnapshot.php`
- Create: `packages/titan-interaction-engine/src/Vertical/GenericVerticalBaseProvider.php`
- Create: `packages/titan-interaction-engine/src/Vertical/VerticalContextComposer.php`
- Create: `packages/titan-interaction-engine/tests/vertical_context.php`
- Modify: `packages/titan-interaction-engine/tests/run.php`
- Modify: `packages/titan-interaction-engine/src/Providers/InteractionServiceProvider.php`

**Interfaces:**
- Produces: `VerticalContextProviderInterface::layer(array $input): VerticalContextLayer`
- Produces: `VerticalContextComposer::compose(array $layers, array $identity): VerticalContextSnapshot`
- Produces: `VerticalContextSnapshot::toArray(): array`
- Produces: `VerticalContextSnapshot::contextHash(): string`

- [ ] **Step 1: Add failing composer tests**

Create `tests/vertical_context.php` using the existing `$test` and `$assert` harness:

```php
<?php

declare(strict_types=1);

use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

$test('vertical context resolves deterministic precedence and provenance', function () use ($assert): void {
    $composer = new VerticalContextComposer();
    $snapshot = $composer->compose([
        VerticalContextLayer::fromArray([
            'id' => 'platform.generic', 'kind' => 'platform_base', 'version' => '1.0.0',
            'values' => ['terminology' => ['entity.work.singular' => 'Work item']],
        ]),
        VerticalContextLayer::fromArray([
            'id' => 'vertical.cleaning', 'kind' => 'subtype', 'version' => '1.0.0',
            'values' => ['terminology' => ['entity.work.singular' => 'Job']],
        ]),
    ], ['company_id' => 42]);

    $assert($snapshot->toArray()['resolved']['terminology']['entity.work.singular'] === 'Job');
    $assert($snapshot->toArray()['sources']['/resolved/terminology/entity.work.singular']['source'] === 'vertical.cleaning');
    $assert($snapshot->contextHash() === $composer->compose($snapshot->layers(), ['company_id' => 42])->contextHash());
});

$test('operational state cannot be erased by tenant branding', function () use ($assert): void {
    $composer = new VerticalContextComposer();
    $snapshot = $composer->compose([
        VerticalContextLayer::fromArray(['id' => 'tenant.brand', 'kind' => 'tenant', 'version' => '1.0.0', 'values' => ['theme' => ['state.danger' => 'pink']]]),
        VerticalContextLayer::fromArray(['id' => 'state.safety', 'kind' => 'operational_state', 'version' => '1.0.0', 'values' => ['theme' => ['state.danger' => 'system-danger']]]),
    ], ['company_id' => 42]);
    $assert($snapshot->toArray()['resolved']['theme']['state.danger'] === 'system-danger');
});
```

Require the file from `tests/run.php` immediately after `$test` and `$assert` are defined.

- [ ] **Step 2: Run tests to verify RED**

Run:

```bash
cd packages/titan-interaction-engine
php tests/run.php
```

Expected: failure because `VerticalContextLayer` and `VerticalContextComposer` do not exist.

- [ ] **Step 3: Implement immutable DTOs and provider contract**

Use these signatures:

```php
interface VerticalContextProviderInterface
{
    public function layer(array $input): VerticalContextLayer;
}

final readonly class ContextValueProvenance
{
    public function __construct(
        public string $source,
        public string $sourceType,
        public float $confidence,
        public bool $confirmed,
        public string $risk,
        public int $revision,
    ) {}
}

final readonly class VerticalContextLayer
{
    public static function fromArray(array $data): self;
    public function toArray(): array;
}

final readonly class VerticalContextSnapshot
{
    public function toArray(): array;
    public function contextHash(): string;
    public function layers(): array;
}
```

Validate allowed kinds, semantic versions, non-empty IDs and allowed top-level value sections.

- [ ] **Step 4: Implement generic base and composer**

`GenericVerticalBaseProvider` must return neutral terminology, safe semantic theme roles, stable navigation slots and no live permissions.

`VerticalContextComposer` must:

```text
sort by fixed kind precedence, then input order
reject duplicate IDs
merge recursively
preserve leaf provenance by JSON Pointer
force operational_state last
canonicalise associative keys before hashing
exclude generated_at from the hash
```

- [ ] **Step 5: Register services**

Add singleton bindings in `InteractionServiceProvider`:

```php
$this->app->singleton(VerticalContextComposer::class);
$this->app->singleton(GenericVerticalBaseProvider::class);
```

- [ ] **Step 6: Run tests to verify GREEN**

Run:

```bash
cd packages/titan-interaction-engine
php tests/run.php
php bin/verify.php
```

Expected: both commands exit 0.

- [ ] **Step 7: Refactor and verify canonical output**

Add one test asserting two equivalent associative input maps produce the same context hash. Re-run both verification commands.

- [ ] **Step 8: Commit, PR and merge**

```bash
git add packages/titan-interaction-engine
git commit -m "feat: add generic vertical context composer"
```

Open a PR with `Closes #303`. Merge only with fresh green verification.

---

### Task 3: WorkCore wizard context adapter — Issue #304

**Files:**
- Create: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/Wizards/Contracts/WizardVerticalContextRepositoryContract.php`
- Create: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/Wizards/Repositories/DatabaseWizardVerticalContextRepository.php`
- Create: `app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Modules/Wizards/Services/WizardVerticalContextAdapter.php`
- Modify: `.../Providers/WorkWizardsServiceProvider.php`
- Create: `app/extensions/WorkCore_Platform/tests/Feature/Wizards/WizardVerticalContextAdapterTest.php`

**Interfaces:**
- Consumes: `VerticalContextLayer`, `VerticalContextComposer`
- Produces: `WizardVerticalContextRepositoryContract::snapshot(string $runPublicId, int $companyId): array`
- Produces: `WizardVerticalContextAdapter::compose(string $runPublicId, int $companyId, array $actor): VerticalContextSnapshot`

- [ ] **Step 1: Write failing tenant/version tests**

```php
public function test_it_uses_the_definition_snapshot_version_from_the_run(): void
{
    $run = $this->seedWizardRun(companyId: 10, definitionVersion: 2);
    $this->publishNewerDefinition(companyId: 10, version: 3);

    $snapshot = $this->app->make(WizardVerticalContextAdapter::class)
        ->compose($run->public_id, 10, ['id' => 7, 'roles' => ['owner']]);

    self::assertSame(2, $snapshot->toArray()['wizard']['definition_version']);
}

public function test_cross_company_run_access_is_denied(): void
{
    $run = $this->seedWizardRun(companyId: 10);
    $this->expectException(ModelNotFoundException::class);
    $this->app->make(WizardVerticalContextAdapter::class)
        ->compose($run->public_id, 11, ['id' => 7]);
}
```

- [ ] **Step 2: Run the focused test to verify RED**

Run the WorkCore package’s documented test command targeting `WizardVerticalContextAdapterTest`. Expected: missing contract/adapter failure.

- [ ] **Step 3: Implement repository snapshot loading**

Return one normalized array containing:

```php
[
    'run' => [...],
    'definition_snapshot' => [...],
    'answers' => [...],
    'approved_sections' => [...],
    'answer_revision' => 18,
]
```

Scope every table query by `company_id`. Read the immutable definition stored by the run; if the current schema stores only key/version, resolve the exact version and add a migration to persist the snapshot before enabling the adapter.

- [ ] **Step 4: Map answers into a tenant layer**

Map server-owned provenance fields. Exclude values whose question handling is `secure_task`, `secret_connection` or whose target owner is a vault/credential registry. Expose only connection state references.

- [ ] **Step 5: Register binding**

```php
$this->app->bind(
    WizardVerticalContextRepositoryContract::class,
    DatabaseWizardVerticalContextRepository::class,
);
$this->app->singleton(WizardVerticalContextAdapter::class);
```

- [ ] **Step 6: Run focused and package verification**

Expected: tenant, version, provenance, approval and secret tests pass; package verification exits 0.

- [ ] **Step 7: Materialise mirrors**

Run the repository’s existing WorkCore materialisation command. Verify generated mirrors match the canonical package and no unrelated files change.

- [ ] **Step 8: Commit, PR and merge**

Commit message:

```text
feat(workcore): project wizard runs into vertical context
```

Open PR with `Closes #304`.

---

### Task 4: Provider-neutral AI proposal bridge — Issue #305

**Files:**
- Create: `packages/titan-interaction-engine/src/Vertical/Contracts/VerticalAiAdvisorInterface.php`
- Create: `packages/titan-interaction-engine/src/Vertical/DTO/VerticalProposalRequest.php`
- Create: `packages/titan-interaction-engine/src/Vertical/DTO/ProposalOperation.php`
- Create: `packages/titan-interaction-engine/src/Vertical/DTO/VerticalPatchProposal.php`
- Create: `packages/titan-interaction-engine/src/Vertical/VerticalProposalValidator.php`
- Create: `packages/titan-interaction-engine/src/Vertical/AiServiceVerticalAdvisor.php`
- Create: `packages/titan-interaction-engine/src/Vertical/NullVerticalAiAdvisor.php`
- Create: `packages/titan-interaction-engine/tests/vertical_ai_proposals.php`
- Modify: `packages/titan-interaction-engine/tests/run.php`
- Modify: `packages/titan-interaction-engine/src/Providers/InteractionServiceProvider.php`

**Interfaces:**
- Produces: `VerticalAiAdvisorInterface::propose(VerticalProposalRequest $request): VerticalPatchProposal`
- Consumes: existing `AIServiceInterface::generate(string $prompt, array $options = []): string`
- Produces: `VerticalProposalValidator::validate(VerticalPatchProposal $proposal, VerticalProposalRequest $request): void`

- [ ] **Step 1: Write failing schema and forbidden-path tests**

```php
$test('vertical proposal validator rejects live permission paths', function () use ($assert): void {
    $request = VerticalProposalRequest::fromArray([
        'context_hash' => str_repeat('a', 64),
        'answer_revision' => 4,
        'allowed_sections' => ['terminology'],
        'forbidden_paths' => ['/resolved/permissions'],
    ]);
    $proposal = VerticalPatchProposal::fromArray([
        'context_hash' => str_repeat('a', 64),
        'answer_revision' => 4,
        'operations' => [['op' => 'set', 'path' => '/resolved/permissions/admin', 'value' => true, 'confidence' => .9, 'risk' => 'high']],
    ]);
    try {
        (new VerticalProposalValidator())->validate($proposal, $request);
        $assert(false, 'Forbidden path was accepted');
    } catch (InvalidArgumentException) {
        $assert(true);
    }
});
```

Add tests for malformed JSON, stale hash, too many operations, invalid confidence and `NullVerticalAiAdvisor` returning an empty valid proposal.

- [ ] **Step 2: Run tests to verify RED**

Expected: missing classes.

- [ ] **Step 3: Implement DTO validation**

Allowed operations:

```php
['set', 'append_unique', 'remove_optional', 'suggest_question', 'skip_optional_question', 'rephrase_question']
```

Allowed sections:

```php
['terminology', 'theme', 'navigation', 'workspaces', 'capabilities', 'questions', 'forms', 'checklists', 'widgets', 'ai', 'activation']
```

`activation` accepts draft planning metadata only; live activation paths are forbidden.

- [ ] **Step 4: Implement AI adapter**

Build a versioned system prompt that instructs the provider to return JSON only. Pass configured model/budget options to `AIServiceInterface`. Decode with `JSON_THROW_ON_ERROR`, create DTOs and validate before returning.

- [ ] **Step 5: Register provider-neutral binding**

Bind `VerticalAiAdvisorInterface` to `NullVerticalAiAdvisor` when AI is disabled and `AiServiceVerticalAdvisor` when enabled. Reuse existing AI provider configuration; do not add provider credentials.

- [ ] **Step 6: Verify GREEN and failure fallback**

Run `php tests/run.php` and `php bin/verify.php`. Add a fake `AIServiceInterface` returning malformed JSON and assert a controlled exception contains no prompt or secret content.

- [ ] **Step 7: Commit, PR and merge**

Commit message:

```text
feat: add governed vertical AI proposal bridge
```

Open PR with `Closes #305`.

---

### Task 5: Answer-driven recomposition — Issue #306

**Files:**
- Create: `.../Wizards/Events/WizardAnswerChanged.php`
- Create: `.../Wizards/Services/WizardApprovalInvalidator.php`
- Create: `.../Wizards/Services/WizardContextProjectionService.php`
- Create: `.../Wizards/Listeners/RecomposeWizardVerticalContext.php`
- Create: migration for proposal/context revisions if canonical tables do not exist
- Modify: `.../Wizards/Services/WizardRuntime.php`
- Modify: `.../Wizards/Providers/WorkWizardsServiceProvider.php`
- Create: `.../tests/Feature/Wizards/WizardAnswerRecompositionTest.php`

**Interfaces:**
- Produces: `WizardAnswerChanged` with company, run, question, answer revision, correlation and causation IDs.
- Produces: `WizardContextProjectionService::refresh(string $runPublicId, int $companyId, int $answerRevision): array`
- Produces: `WizardApprovalInvalidator::invalidateAffected(...): array`

- [ ] **Step 1: Write failing event/idempotency tests**

Assert one event after a successful save, no event after validation failure, one projection per `(run, answer_revision)`, and affected approvals become stale.

- [ ] **Step 2: Verify RED with focused tests**

Expected: no event and no invalidator.

- [ ] **Step 3: Add server-owned answer revision**

Increment revision inside the same transaction as answer persistence. Do not accept revision, provenance or confidence from the browser as authority.

- [ ] **Step 4: Emit event after commit**

Use an after-commit event/listener so failed transactions never enqueue recomposition.

- [ ] **Step 5: Implement deterministic projection**

Compose the generic base plus WorkCore adapter layers synchronously. Store a redacted snapshot, context hash and revision. If the same revision exists, return it without duplication.

- [ ] **Step 6: Queue bounded AI enrichment**

Queue only affected allowed sections based on question dependencies. Save the request hash. Reject responses whose context hash or answer revision is no longer current.

- [ ] **Step 7: Verify GREEN**

Run focused tests and package verification. Add a concurrency test where revision 19 AI output arrives after revision 20 and remains superseded.

- [ ] **Step 8: Commit, PR and merge**

Commit message:

```text
feat(workcore): recompose vertical context after wizard answers
```

Open PR with `Closes #306`.

---

### Task 6: Layered question composition — Issue #307

**Files:**
- Create: `.../Wizards/Services/WizardQuestionComposer.php`
- Create: `.../Wizards/Services/MandatoryQuestionPolicy.php`
- Modify: `.../Wizards/Services/WizardDefinitionRegistry.php`
- Create: `vertical-packs/generic-business/questions.json`
- Create: `vertical-packs/countries/au/questions.json`
- Create: `vertical-packs/families/field-home-services/questions.json`
- Create: `vertical-packs/subtypes/cleaning/questions.json`
- Create: `.../tests/Feature/Wizards/WizardQuestionComposerTest.php`

**Interfaces:**
- Produces: `WizardQuestionComposer::compose(array $layerDefinitions): array`
- Produces: `MandatoryQuestionPolicy::assertValid(array $definition): void`
- Modifies start-run flow to persist the complete composed definition snapshot.

- [ ] **Step 1: Write failing composition tests**

Cover stable keys, precedence, optional slot additions, mandatory-question protection, generic fallback and immutable run snapshots.

- [ ] **Step 2: Verify RED**

Expected: composer missing.

- [ ] **Step 3: Implement question-layer schema**

Question overlays may use:

```json
{
  "add": [],
  "refine": {},
  "branch": {},
  "dependencies": {},
  "slots": {}
}
```

They may not delete mandatory keys. `refine` may change presentation metadata, choices, help text and risk only when the new risk is equal or stronger.

- [ ] **Step 4: Add generic, AU and cleaning fixtures**

Use the existing WorkCore question catalogue keys. Country AU adds ABN/GST branches. Cleaning refines work/customer/location terminology and evidence examples without copying the base launch definition.

- [ ] **Step 5: Persist immutable snapshot**

On `start`, save composed JSON and schema version with the run. Registry lookup for active runs reads the snapshot, not the latest published definition.

- [ ] **Step 6: Verify GREEN and upgrade behaviour**

Start a run with pack v1, publish v2, and assert the existing run remains v1 while a new run receives v2.

- [ ] **Step 7: Commit, PR and merge**

Commit message:

```text
feat(workcore): compose generic and vertical onboarding questions
```

Open PR with `Closes #307`.

---

### Task 7: Typed vertical customisation planner — Issue #308

**Files:**
- Create: `packages/titan-interaction-engine/src/Vertical/VerticalCustomizationPlanner.php`
- Create: `packages/titan-interaction-engine/src/Vertical/DTO/VerticalCustomizationPreview.php`
- Create: `packages/titan-interaction-engine/src/Vertical/Policy/AllowedPatchPathPolicy.php`
- Create: `packages/titan-interaction-engine/tests/vertical_customization.php`
- Create fixture JSON for generic, cleaning, accommodation, salon, fitness, automotive, retail and hire/rental previews

**Interfaces:**
- Produces: `VerticalCustomizationPlanner::plan(VerticalContextSnapshot $snapshot, array $proposals = []): VerticalCustomizationPreview`
- Produces: `VerticalCustomizationPreview::diff(): array`

- [ ] **Step 1: Write failing semantic-key and raw-CSS tests**

Assert `entity.work.singular` can become `Job`, route name `dashboard.user.index` cannot be renamed, and `/theme/raw_css` is rejected.

- [ ] **Step 2: Verify RED**

Expected: planner missing.

- [ ] **Step 3: Implement deterministic defaults**

Create typed sections for terminology, theme, navigation, workspaces, introductions, announcements, features, forms, checklists, widgets, AI instructions and public content.

- [ ] **Step 4: Apply validated AI proposals**

Apply only operations already accepted by `VerticalProposalValidator`. Keep source/confidence/risk metadata and before/after values.

- [ ] **Step 5: Enforce platform-owned boundaries**

Reject shell, authentication, permissions, offline runtime, canonical route identifiers, safety colours/states and executable content.

- [ ] **Step 6: Verify GREEN with fixtures**

Run the focused planner tests, then confirm canonical JSON snapshots for each fixture must be byte stable and contain no raw code fields.

- [ ] **Step 7: Commit, PR and merge**

Commit message:

```text
feat: plan safe vertical language and presentation patches
```

Open PR with `Closes #308`.

---

### Task 8: Provenance, approvals, secrets and tenant governance — Issue #309

**Files:**
- Modify: `.../Wizards/Services/WizardRuntime.php`
- Modify: `.../Wizards/Repositories/*WizardRepository*.php`
- Create: `.../Wizards/Services/WizardProvenancePolicy.php`
- Create: `.../Wizards/Services/WizardSecretRedactor.php`
- Modify: proposal persistence and activation approval records
- Create: `.../tests/Feature/Wizards/WizardGovernanceTest.php`

**Interfaces:**
- Produces: `WizardProvenancePolicy::derive(array $requestContext, array $question, mixed $value): array`
- Produces: `WizardSecretRedactor::redact(array $payload): array`
- Approval records bind to answer revision, proposal revision, context hash and summary hash.

- [ ] **Step 1: Write failing forged-provenance test**

Send `source=ai_extracted`, `confidence=1`, `is_confirmed=true` from a normal browser answer and assert persisted provenance is `user_entered`, confidence `1`, confirmed `true` because the user entered it—not because the browser labelled it AI.

Add tests for AI self-approval denial, stale approval denial, cross-tenant proposal access and secret redaction.

- [ ] **Step 2: Verify RED**

Expected: current save path accepts client provenance and approvals remain current after edits.

- [ ] **Step 3: Derive provenance server-side**

Use authenticated execution context and trusted AI job metadata. Ignore browser-supplied source/confidence as authority.

- [ ] **Step 4: Version approvals**

Store exact revisions and invalidate on any covered change. `complete()` and activation re-check current revision/hash.

- [ ] **Step 5: Add secret policy**

Reject or redact keys matching credential classes and questions declared `secure_task`/`secret_connection`. Persist only provider reference, connection status and verification time.

- [ ] **Step 6: Add permission, rate and audit controls**

Protect view, answer, proposal, approval and activation actions separately. Record denied attempts without confidential payloads.

- [ ] **Step 7: Verify GREEN**

Run focused tests, package verification and a grep check that test fixtures contain no credential-shaped values.

- [ ] **Step 8: Commit, PR and merge**

Commit message:

```text
fix(workcore): enforce governed wizard and AI provenance
```

Open PR with `Closes #309`.

---

### Task 9: Canonical activation compiler and saga — Issue #310

**Files:**
- Create: `.../Wizards/DTO/ActivationStep.php`
- Create: `.../Wizards/DTO/ActivationPlan.php`
- Create: `.../Wizards/Services/WizardActionMapperRegistry.php`
- Create: `.../Wizards/Services/WizardPlanCompiler.php`
- Create: `.../Wizards/Services/WizardActionExecutor.php`
- Create: `.../Wizards/Services/WizardActivationSaga.php`
- Modify: `.../Wizards/Services/WizardRuntime.php`
- Modify or add migration/repository for `tz_wizard_action_results`
- Create: `.../tests/Feature/Wizards/WizardActivationSagaTest.php`

**Interfaces:**
- Produces: `WizardPlanCompiler::compile(VerticalContextSnapshot $snapshot, VerticalCustomizationPreview $preview): ActivationPlan`
- Produces: `WizardActivationSaga::execute(ActivationPlan $plan, array $approval): array`
- Mapper registry maps semantic answer/patch keys to canonical capability names.

- [ ] **Step 1: Write failing compiler test**

Use a fixture containing company name, cleaning service, territory and payment terms. Assert ordered steps:

```php
[
    'workcore.company.update',
    'workcore.catalogue.category.create',
    'workcore.catalogue.service.create',
    'workcore.territory.create',
    'workcore.finance.payment_policy.update',
    'titan.vertical.apply',
]
```

Assert every step has an idempotency key and payload hash.

- [ ] **Step 2: Write failing partial-failure test**

Fake step three failure. Assert steps one/two stay succeeded, dependent required steps remain blocked, retry reuses the same idempotency key, and the run is not active.

- [ ] **Step 3: Verify RED**

Expected: compiler/saga missing and `complete()` still returns `canonical_workcore_action_adapter_required`.

- [ ] **Step 4: Implement DTOs and mapper registry**

`ActivationStep` fields:

```text
id, capability, authority, payload, payload_hash, idempotency_key,
required, depends_on, retry_policy, compensation_capability, status
```

- [ ] **Step 5: Implement compiler**

Reject unapproved high-risk values and unmapped required keys. Secure connection questions create `secure_task` steps rather than credential payloads.

- [ ] **Step 6: Implement durable executor/saga**

Persist started/succeeded/failed/skipped states in `tz_wizard_action_results`. Dispatch through existing WorkCore business action/command boundaries. Never call controllers or write domain tables directly.

- [ ] **Step 7: Replace placeholder completion**

`complete()` returns an immutable draft activation plan. A separate approved activation action executes it. Preserve backward-compatible response fields where clients expect an action plan.

- [ ] **Step 8: Verify GREEN**

Run compiler, idempotency, partial failure, retry, stale approval and activation-gate tests.

- [ ] **Step 9: Commit, PR and merge**

Commit message:

```text
feat(workcore): execute approved wizard activation plans
```

Open PR with `Closes #310`.

---

### Task 10: Vertical pack schema, SDK and nine family profiles — Issue #311

**Files:**
- Create: `vertical-packs/schemas/*.schema.json`
- Create: `vertical-packs/generic-business/pack.json`
- Create: `vertical-packs/countries/au/pack.json`
- Create: `vertical-packs/families/{field-home-services,accommodation,real-estate,salons-personal-care,fitness-membership,automotive,ecommerce-retail,hire-rental,booking-capacity}/pack.json`
- Create: `vertical-packs/subtypes/cleaning/*`
- Create: `vertical-packs/templates/new-subtype/*`
- Create: `tools/validate_vertical_packs.py`
- Create: `tests/test_vertical_packs.py`
- Create: `docs/vertical-pack-authoring/README.md`

**Interfaces:**
- Produces validated pack JSON consumed by context and question composers.
- Produces `python tools/validate_vertical_packs.py` verification command.

- [ ] **Step 1: Write failing schema tests**

```python
def test_every_family_extends_generic_base():
    for pack in family_packs():
        assert pack["extends"] == "generic-business"
        assert pack["type"] == "vertical-family"


def test_pack_cannot_replace_platform_shell():
    errors = validate_pack({"slug": "bad", "overrides": {"shell": "custom"}})
    assert any("shell" in error for error in errors)
```

Add duplicate slug, dependency, semantic key and authority-owner tests.

- [ ] **Step 2: Run tests to verify RED**

Run:

```bash
python -m unittest tests/test_vertical_packs.py -v
```

Expected: schemas/tool/packs missing.

- [ ] **Step 3: Implement schemas and validator**

Required pack fields:

```text
schema_version, slug, name, version, type, extends, authority,
dependencies, terminology, theme, navigation, workspaces, questions,
forms, checklists, widgets, ai, action_mappings, public_content, compliance
```

Empty sections are allowed; unknown top-level sections are rejected.

- [ ] **Step 4: Add generic and nine family packs**

Use stable semantic keys and family-level defaults only. Do not copy full pages or PHP runtime code.

- [ ] **Step 5: Complete cleaning reference subtype**

Include recurring, deep, end-of-lease, inspection, emergency and re-clean job types; room/area checklists; photo evidence; manager widgets; role workspaces; and bounded AI instructions.

- [ ] **Step 6: Add authoring template and docs**

Document layer precedence, semantic keys, allowed slots, action mapping, versioning, tests and packaging.

- [ ] **Step 7: Verify GREEN**

Run unit tests and validator twice. Hash normalized pack output and assert identical results.

- [ ] **Step 8: Commit, PR and merge**

Commit message:

```text
feat: add Titan vertical pack SDK and family catalogue
```

Open PR with `Closes #311`.

---

### Task 11: Donor compatibility adapters — Issue #312

**Files:**
- Create: `app/extensions/WorkCore_Platform/.../Vertical/Import/LiveCustomizerImporter.php`
- Create: `.../Vertical/Import/MenuImporter.php`
- Create: `.../Vertical/Import/FocusModeImporter.php`
- Create: `.../Vertical/Import/IntroductionImporter.php`
- Create: `.../Vertical/Import/AnnouncementImporter.php`
- Create: `.../Vertical/Import/ContentManagerImporter.php`
- Create: `.../Vertical/Import/OnboardingProImporter.php`
- Create: `.../Vertical/Import/VerticalDonorImportCoordinator.php`
- Create migrations for import ownership/results only when existing audit storage cannot hold them
- Create: `.../tests/Feature/Vertical/VerticalDonorImportTest.php`
- Create: `docs/titan-vertical-ai/DONOR-MIGRATION.md`

**Interfaces:**
- Each importer exposes `preview(int $companyId): ImportPreview` and `apply(ImportPreview $preview, int $actorId): ImportResult`.
- Coordinator executes idempotently by donor, company and source hash.

- [ ] **Step 1: Write failing idempotency and unsafe-value tests**

Import the same donor twice and assert no duplicate context values. Import raw custom CSS and assert it is rejected with a report entry, not activated.

- [ ] **Step 2: Verify RED**

Expected: importers missing.

- [ ] **Step 3: Implement safe mappings**

Mappings:

```text
LiveCustomizer fonts/colours → typed theme tokens
Menu stable keys/order/visibility → navigation overlay
FocusMode → role workspace preset
Introduction/OnboardingPro → contextual guidance and induction references
Announcement → targeted notice rules
ContentManager → asset references
```

`MegaMenu`, `Maintenance` and `DiscountManager` remain external adapters/extensions.

- [ ] **Step 4: Add preview, source hash and ownership markers**

No import writes before preview approval. Store donor key, source record ID/hash, target path and result status.

- [ ] **Step 5: Add rollback**

Rollback removes only values owned by that import and only when no later tenant edit superseded them.

- [ ] **Step 6: Verify GREEN and compatibility**

Run the focused import tests, then assert no existing folder, namespace, slug, route, configuration key, migration or table is renamed.

- [ ] **Step 7: Commit, PR and merge**

Commit message:

```text
feat: migrate vertical donor settings through bounded adapters
```

Open PR with `Closes #312`.

---

### Task 12: Preview UI, end-to-end verification and release — Issue #313

**Files:**
- Modify the closest existing WorkCore wizard/review Blade views and controllers
- Create shared partials/components only when no existing component fits
- Create browser/feature tests under existing WorkCore UI test roots
- Create: `.github/workflows/titan-vertical-ai.yml`
- Create: `tools/build_titan_vertical_ai_release.py`
- Create: `tools/validate_titan_vertical_ai_release.py`
- Create: `docs/titan-vertical-ai/UPGRADE-ROLLBACK.md`
- Create: `docs/titan-vertical-ai/VERIFICATION.md`
- Create: `docs/titan-vertical-ai/RISK-REGISTER.md`

**Interfaces:**
- UI consumes context/preview/approval/activation application services; it does not query donor tables.
- Release builder produces deterministic ZIPs plus SHA-256 manifest.

- [ ] **Step 1: Write failing feature tests**

Cover:

```text
context summary and active source layers
AI suggestion source/confidence/reason
before/after typed diff
stale approval warning
AI-disabled deterministic preview
activation progress and retry
permission denied
offline and conflict states
```

- [ ] **Step 2: Verify RED**

Expected: current wizard review does not render context/proposal/activation data.

- [ ] **Step 3: Reuse existing UI surfaces**

Add bounded sections to the current wizard/review flow. Do not create a second onboarding dashboard. Use existing design tokens, typography, buttons, cards and responsive layout.

- [ ] **Step 4: Add structured controls**

Use dedicated components for schedules, service collections, pricing rules, maps, workers, skills, credentials, evidence, notification matrices and secure connections. Do not fall back to generic textareas for structured data.

- [ ] **Step 5: Add accessibility and state coverage**

Verify keyboard focus, minimum touch targets, contrast, reduced motion, mobile/tablet/desktop layouts and loading/empty/error/offline/conflict states.

- [ ] **Step 6: Add CI workflow**

Run:

```text
interaction-engine test and verify
WorkCore focused/unit/feature tests
PHP syntax lint
vertical-pack Python tests and validator
JSON schema validation
source-assets JSON validation
deterministic release build twice with SHA comparison
secret/residue/nested-archive checks
```

- [ ] **Step 7: Build deterministic release packages**

Exclude credentials, runtime residue, vendor, node_modules and donor binaries. Include source manifests and MiniUp references. Generate `SHA256SUMS` and provenance metadata.

- [ ] **Step 8: Write upgrade, rollback and risk documents**

Document donor imports, schema migrations, feature flags, rollback points, activation recovery, external-provider tests and deferred items.

- [ ] **Step 9: Run full fresh verification**

Run all commands in `docs/titan-vertical-ai/VERIFICATION.md`. Record exact test counts, exit codes and release hashes in the PR.

- [ ] **Step 10: Commit, PR and merge**

Commit message:

```text
release: verify and package Titan Vertical AI context engine
```

Open PR with `Closes #313`. Merge only when the complete matrix is green.

---

## Plan Self-Review Checklist

- Every requirement in `docs/titan-vertical-ai/FOUNDATION.md` maps to an issue/task above.
- Generic context code has no WorkCore or extension-specific model dependency.
- WorkCore remains the persisted wizard and activation authority.
- AI provider configuration is reused; no second provider registry or credential store is introduced.
- Stable keys and typed patches replace global text/CSS mutation.
- Mandatory questions, operational state, permissions and secrets are protected.
- Tests precede production code in every implementation task.
- Each issue ends in an independently reviewable PR and merge gate.
- The MiniUp asset manifest is documentation only and large binaries are not committed.
