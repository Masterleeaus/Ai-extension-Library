<?php

declare(strict_types=1);

$repoRoot = getenv('AI_EXTENSIONS_REPO_ROOT') ?: dirname(__DIR__, 3);
$vendorAutoload = getenv('WORKCORE_TEST_VENDOR_AUTOLOAD') ?: $repoRoot . '/vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    fwrite(STDERR, "Missing test vendor autoload: {$vendorAutoload}\n");
    exit(2);
}
require $vendorAutoload;

if (!function_exists('now')) {
    function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-05 17:00:00', new DateTimeZone('Australia/Sydney'));
    }
}

spl_autoload_register(static function (string $class) use ($repoRoot): void {
    $prefixes = [
        'App\\Domains\\WorkCore\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/',
        'App\\Domains\\WorkCore\\System\\Actions\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/',
        'App\\Domains\\WorkCore\\System\\References\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/References/',
        'TitanZero\\Interaction\\' => $repoRoot . '/packages/titan-interaction-engine/src/',
    ];
    foreach ($prefixes as $prefix => $base) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $path = $base . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
        return;
    }
});

use App\Domains\WorkCore\System\Actions\ActionRequest;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\SaveWizardAnswer;
use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardAIEnrichmentDispatcherContract;
use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;
use App\Domains\WorkCore\System\Modules\Wizards\Repositories\DatabaseWizardRecompositionRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Repositories\DatabaseWizardVerticalContextRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Repositories\EloquentWizardRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardAnswerRecompositionService;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardAnswerValidator;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardBranchEvaluator;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDefinitionRegistry;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDependencyResolver;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardRiskPolicy;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardRuntime;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardVerticalContextAdapter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposal;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

final class RecordingWizardAIEnrichmentDispatcher implements WizardAIEnrichmentDispatcherContract
{
    public array $calls = [];

    public function dispatch(WizardAnswerChanged $event, array $drafts): void
    {
        $this->calls[] = ['event' => $event->toArray(), 'drafts' => $drafts];
    }
}

$tests = [];
$test = static function (string $name, callable $callback) use (&$tests): void { $tests[$name] = $callback; };
$assert = static function (bool $condition, string $message = 'Assertion failed'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$capsule = new Capsule();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$db = $capsule->getConnection();
$schema = $db->getSchemaBuilder();

$schema->create('tz_wizard_definitions', static function (Blueprint $table): void {
    $table->increments('id');
    $table->string('public_id');
    $table->unsignedBigInteger('company_id')->nullable();
    $table->string('definition_key');
    $table->unsignedInteger('version');
    $table->string('title')->nullable();
    $table->text('description')->nullable();
    $table->string('status');
    $table->text('definition');
    $table->unsignedBigInteger('created_by_user_id')->nullable();
    $table->unsignedBigInteger('updated_by_user_id')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
$schema->create('tz_wizard_runs', static function (Blueprint $table): void {
    $table->increments('id');
    $table->string('public_id');
    $table->unsignedBigInteger('company_id');
    $table->string('definition_key');
    $table->unsignedInteger('definition_version');
    $table->string('status');
    $table->string('mode');
    $table->string('current_section_key')->nullable();
    $table->string('current_question_key')->nullable();
    $table->unsignedBigInteger('initiated_by_user_id');
    $table->string('assisted_by_agent_public_id')->nullable();
    $table->decimal('completion_percent', 5, 2)->default(0);
    $table->text('metadata')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('paused_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
$schema->create('tz_wizard_answers', static function (Blueprint $table): void {
    $table->increments('id');
    $table->string('public_id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('run_id');
    $table->string('question_key');
    $table->text('value')->nullable();
    $table->string('source');
    $table->decimal('confidence', 5, 4)->nullable();
    $table->boolean('is_confirmed')->default(false);
    $table->unsignedBigInteger('answered_by_user_id')->nullable();
    $table->string('answered_by_agent_public_id')->nullable();
    $table->timestamps();
    $table->unique(['run_id', 'question_key']);
});
$schema->create('tz_wizard_section_approvals', static function (Blueprint $table): void {
    $table->increments('id');
    $table->string('public_id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('run_id');
    $table->string('section_key');
    $table->string('status');
    $table->text('summary')->nullable();
    $table->unsignedBigInteger('approved_by_user_id')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
    $table->unique(['run_id', 'section_key']);
});
$schema->create('tz_wizard_events', static function (Blueprint $table): void {
    $table->increments('id');
    $table->string('public_id')->unique();
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('run_id');
    $table->string('event_type');
    $table->text('payload');
    $table->string('actor_type');
    $table->string('actor_public_id')->nullable();
    $table->timestamp('created_at')->nullable();
});

$definition = [
    'key' => 'tenant-onboarding',
    'version' => 2,
    'title' => 'Tenant onboarding',
    'sections' => [
        [
            'key' => 'business_profile',
            'title' => 'Business profile',
            'approval_mode' => 'section',
            'questions' => [[
                'key' => 'company.name',
                'question' => 'What is the business name?',
                'response_type' => 'text',
                'required' => true,
                'risk' => 'medium',
                'handling' => 'ask',
                'target_owner' => 'Company branding',
                'affects' => ['business_profile', 'brand'],
            ]],
        ],
        [
            'key' => 'brand',
            'title' => 'Brand',
            'approval_mode' => 'section',
            'questions' => [],
        ],
        [
            'key' => 'operations',
            'title' => 'Operations',
            'approval_mode' => 'section',
            'questions' => [[
                'key' => 'operations.capacity',
                'question' => 'Daily capacity?',
                'response_type' => 'integer',
                'required' => false,
                'risk' => 'low',
                'handling' => 'ask',
                'target_owner' => 'Operations',
            ]],
        ],
    ],
];

$db->table('tz_wizard_definitions')->insert([
    'public_id' => 'definition-2',
    'company_id' => 10,
    'definition_key' => 'tenant-onboarding',
    'version' => 2,
    'title' => 'Tenant onboarding',
    'status' => 'published',
    'definition' => json_encode($definition, JSON_THROW_ON_ERROR),
]);
$db->table('tz_wizard_runs')->insert([
    'id' => 100,
    'public_id' => 'run-10',
    'company_id' => 10,
    'definition_key' => 'tenant-onboarding',
    'definition_version' => 2,
    'status' => 'in_progress',
    'mode' => 'hybrid',
    'current_section_key' => 'business_profile',
    'current_question_key' => 'company.name',
    'initiated_by_user_id' => 7,
    'completion_percent' => 0,
    'metadata' => json_encode(['answer_revision' => 0, 'vertical_family' => 'field-home-services', 'subtype' => 'cleaning'], JSON_THROW_ON_ERROR),
    'started_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);
foreach (['business_profile', 'brand', 'operations'] as $index => $section) {
    $db->table('tz_wizard_section_approvals')->insert([
        'public_id' => 'approval-' . $section,
        'company_id' => 10,
        'run_id' => 100,
        'section_key' => $section,
        'status' => 'approved',
        'summary' => json_encode(['revision' => 0], JSON_THROW_ON_ERROR),
        'approved_by_user_id' => 7,
        'approved_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

$registry = new WizardDefinitionRegistry($db);
$wizardRepository = new EloquentWizardRepository($db);
$contextRepository = new DatabaseWizardVerticalContextRepository($db, $registry);
$contextAdapter = new WizardVerticalContextAdapter($contextRepository, new VerticalContextComposer());
$recompositionRepository = new DatabaseWizardRecompositionRepository($db);
$enrichmentDispatcher = new RecordingWizardAIEnrichmentDispatcher();
$recomposition = new WizardAnswerRecompositionService($contextAdapter, $recompositionRepository, $enrichmentDispatcher);
$runtime = new WizardRuntime(
    $registry,
    $wizardRepository,
    new WizardBranchEvaluator(),
    new WizardAnswerValidator(),
    new WizardRiskPolicy(),
    new WizardDependencyResolver(),
);
$action = new SaveWizardAnswer($runtime, $recomposition);

$save = static function (string $value, string $idempotencyKey, int $companyId = 10, string $source = 'api') use ($action): mixed {
    return $action->handle(new ActionRequest(
        key: 'workcore.wizard_answer.save',
        payload: [
            'run_public_id' => 'run-10',
            'question_key' => 'company.name',
            'value' => $value,
            'source' => 'malicious_client_source',
            'confidence' => 0.01,
            'is_confirmed' => false,
            'agent_public_id' => 'client-controlled-agent',
        ],
        companyId: $companyId,
        actorId: 7,
        idempotencyKey: $idempotencyKey,
        source: $source,
    ));
};

$first = null;

$test('answer persistence emits authoritative tenant-scoped change metadata', function () use (&$first, $save, $db, $assert): void {
    $first = $save('Clean Co', 'answer-1');
    $answer = $db->table('tz_wizard_answers')->where('run_id', 100)->where('question_key', 'company.name')->first();
    $run = $db->table('tz_wizard_runs')->where('id', 100)->first();
    $metadata = json_decode((string) $run->metadata, true, 512, JSON_THROW_ON_ERROR);

    $assert($answer->source === 'user_entered', 'Client source was trusted.');
    $assert(abs((float) $answer->confidence - 1.0) < 0.0001, 'Client confidence was trusted.');
    $assert((bool) $answer->is_confirmed === true, 'User answer was not server-confirmed.');
    $assert($answer->answered_by_agent_public_id === null, 'Client agent identity was trusted.');
    $assert($metadata['answer_revision'] === 1, 'Answer revision did not increment.');
    $assert($first->data['changed'] === true);
    $assert($first->data['answer_revision'] === 1);
    $assert($first->data['affected_sections'] === ['brand', 'business_profile']);
    $assert($first->data['change_event']['company_id'] === 10);
    $assert($first->data['change_event']['actor_id'] === 7);
    $assert($first->events[0]->name === 'workcore.wizard.answer.changed');
});

$test('dependency targeting invalidates only affected approvals', function () use ($db, $assert): void {
    $statuses = $db->table('tz_wizard_section_approvals')->where('run_id', 100)->pluck('status', 'section_key')->all();
    $assert($statuses['business_profile'] === 'stale');
    $assert($statuses['brand'] === 'stale');
    $assert($statuses['operations'] === 'approved');
});

$test('low-cost drafts are stored idempotently and AI enrichment is queued', function () use ($db, $enrichmentDispatcher, $assert): void {
    $drafts = $db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->orderBy('id')->get();
    $assert($drafts->count() === 2, 'Expected one draft per affected section.');
    $payloads = $drafts->map(static fn ($row) => json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR))->all();
    $assert(array_column($payloads, 'section_key') === ['brand', 'business_profile']);
    $assert($payloads[0]['answer_revision'] === 1);
    $assert(strlen($payloads[0]['context_hash']) === 64);
    $assert($payloads[0]['source'] === 'deterministic');
    $assert($payloads[0]['confirmation_state'] === 'draft');
    $assert(count($enrichmentDispatcher->calls) === 1, 'AI enrichment was not queued once.');
});

$test('identical answers are idempotent across different action keys', function () use ($save, $db, $enrichmentDispatcher, $assert): void {
    $result = $save('Clean Co', 'answer-duplicate');
    $metadata = json_decode((string) $db->table('tz_wizard_runs')->where('id', 100)->value('metadata'), true, 512, JSON_THROW_ON_ERROR);

    $assert($result->data['changed'] === false);
    $assert($result->events === []);
    $assert($metadata['answer_revision'] === 1);
    $assert($db->table('tz_wizard_events')->where('event_type', 'wizard.answer.changed')->count() === 1);
    $assert($db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->count() === 2);
    $assert(count($enrichmentDispatcher->calls) === 1);
});

$test('cross-tenant answer and recomposition access fail closed', function () use ($save, $recomposition, $first, $assert): void {
    try {
        $save('Other Co', 'answer-cross-tenant', 11);
        $assert(false, 'Cross-tenant answer was accepted.');
    } catch (InvalidArgumentException) {
        $assert(true);
    }

    $event = WizardAnswerChanged::fromArray([...$first->data['change_event'], 'company_id' => 11]);
    try {
        $recomposition->project($event);
        $assert(false, 'Cross-tenant recomposition was accepted.');
    } catch (InvalidArgumentException) {
        $assert(true);
    }
});

$test('stale AI proposals cannot overwrite a newer answer revision', function () use ($save, $recomposition, $first, $db, $assert): void {
    $oldEvent = WizardAnswerChanged::fromArray($first->data['change_event']);
    $oldDraft = json_decode((string) $db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->orderBy('id')->value('payload'), true, 512, JSON_THROW_ON_ERROR);

    $second = $save('Clean Co Group', 'answer-2');
    $assert($second->data['answer_revision'] === 2);

    $staleProposal = VerticalAIProposal::create(
        updates: ['terminology' => ['entity.company.singular' => 'Business'], 'context' => [], 'questions' => []],
        confidence: 0.8,
        rationale: 'Suggested from the old context.',
        provenance: [['source' => 'wizard.answer.company.name', 'path' => '/resolved/terminology/entity.company.singular', 'reason' => 'Business wording']],
        contextHash: $oldDraft['context_hash'],
        providerId: 'test-provider',
        modelId: 'test-model',
        attempts: 1,
        fallbackUsed: false,
        audit: ['schema_version' => '1.0.0', 'prompt_hash' => str_repeat('a', 64), 'response_hash' => str_repeat('b', 64), 'validation_errors' => []],
    );

    try {
        $recomposition->applyAI($oldEvent, $staleProposal);
        $assert(false, 'Stale AI proposal was accepted.');
    } catch (InvalidArgumentException) {
        $assert(true);
    }

    $assert($db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.ai')->count() === 0);
});

$test('current AI proposal creates superseding revisions for affected sections', function () use ($recomposition, $db, $assert): void {
    $changed = $db->table('tz_wizard_events')->where('event_type', 'wizard.answer.changed')->orderByDesc('id')->first();
    $eventPayload = json_decode((string) $changed->payload, true, 512, JSON_THROW_ON_ERROR);
    $event = WizardAnswerChanged::fromArray($eventPayload['change_event']);
    $currentDraft = json_decode((string) $db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->orderByDesc('id')->value('payload'), true, 512, JSON_THROW_ON_ERROR);

    $proposal = VerticalAIProposal::create(
        updates: ['terminology' => ['entity.company.singular' => 'Business'], 'context' => ['theme' => ['density' => 'field']], 'questions' => []],
        confidence: 0.86,
        rationale: 'Use service-oriented business wording.',
        provenance: [['source' => 'wizard.answer.company.name', 'path' => '/resolved/terminology/entity.company.singular', 'reason' => 'Business wording']],
        contextHash: $currentDraft['context_hash'],
        providerId: 'test-provider',
        modelId: 'test-model',
        attempts: 1,
        fallbackUsed: false,
        audit: ['schema_version' => '1.0.0', 'prompt_hash' => str_repeat('c', 64), 'response_hash' => str_repeat('d', 64), 'validation_errors' => []],
    );

    $rows = $recomposition->applyAI($event, $proposal);
    $assert(count($rows) === 2);
    $assert($db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.ai')->count() === 2);
    foreach ($rows as $row) {
        $assert($row['source'] === 'ai');
        $assert($row['answer_revision'] === 2);
        $assert($row['supersedes_public_id'] !== null);
        $assert($row['confirmation_state'] === 'draft');
    }
});

$failed = 0;
foreach ($tests as $name => $callback) {
    try {
        $callback();
        echo "PASS {$name}\n";
    } catch (Throwable $exception) {
        $failed++;
        echo "FAIL {$name}: {$exception->getMessage()}\n";
    }
}
exit($failed === 0 ? 0 : 1);
