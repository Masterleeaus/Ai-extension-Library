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
        'App\\Domains\\WorkCore\\System\\Actions\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/',
        'App\\Domains\\WorkCore\\System\\References\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/References/',
        'App\\Domains\\WorkCore\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/',
        'TitanZero\\Interaction\\' => $repoRoot . '/packages/titan-interaction-engine/src/',
    ];
    foreach ($prefixes as $prefix => $base) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $path = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require_once $path;
            return;
        }
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

final class RecordingEnrichmentDispatcher implements WizardAIEnrichmentDispatcherContract
{
    public array $calls = [];

    public function dispatch(WizardAnswerChanged $event, array $drafts): void
    {
        $this->calls[] = ['event' => $event->toArray(), 'drafts' => $drafts];
    }
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$passes = 0;
$runTest = static function (string $name, callable $test) use (&$passes): void {
    try {
        $test();
        $passes++;
        echo "PASS {$name}\n";
    } catch (Throwable $exception) {
        echo "FAIL {$name}: {$exception->getMessage()}\n";
        exit(1);
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
        ['key' => 'business_profile', 'title' => 'Business profile', 'approval_mode' => 'section', 'questions' => [[
            'key' => 'company.name',
            'question' => 'What is the business name?',
            'response_type' => 'text',
            'required' => true,
            'risk' => 'medium',
            'handling' => 'ask',
            'target_owner' => 'Company branding',
            'affects' => ['business_profile', 'brand'],
        ]]],
        ['key' => 'brand', 'title' => 'Brand', 'approval_mode' => 'section', 'questions' => []],
        ['key' => 'operations', 'title' => 'Operations', 'approval_mode' => 'section', 'questions' => [[
            'key' => 'operations.capacity',
            'question' => 'Daily capacity?',
            'response_type' => 'integer',
            'required' => false,
            'risk' => 'low',
            'handling' => 'ask',
            'target_owner' => 'Operations',
        ]]],
    ],
];
$db->table('tz_wizard_definitions')->insert([
    'public_id' => 'definition-2', 'company_id' => 10, 'definition_key' => 'tenant-onboarding',
    'version' => 2, 'title' => 'Tenant onboarding', 'status' => 'published',
    'definition' => json_encode($definition, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
]);
$db->table('tz_wizard_runs')->insert([
    'id' => 100, 'public_id' => 'run-10', 'company_id' => 10, 'definition_key' => 'tenant-onboarding',
    'definition_version' => 2, 'status' => 'in_progress', 'mode' => 'hybrid',
    'current_section_key' => 'business_profile', 'current_question_key' => 'company.name',
    'initiated_by_user_id' => 7, 'completion_percent' => 0,
    'metadata' => json_encode(['answer_revision' => 0, 'vertical_family' => 'field-home-services', 'subtype' => 'cleaning'], JSON_THROW_ON_ERROR),
    'started_at' => now(), 'created_at' => now(), 'updated_at' => now(),
]);
foreach (['business_profile', 'brand', 'operations'] as $section) {
    $db->table('tz_wizard_section_approvals')->insert([
        'public_id' => 'approval-' . $section, 'company_id' => 10, 'run_id' => 100,
        'section_key' => $section, 'status' => 'approved',
        'summary' => json_encode(['answer_revision' => 0], JSON_THROW_ON_ERROR),
        'approved_by_user_id' => 7, 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
}

$registry = new WizardDefinitionRegistry($db);
$wizardRepository = new EloquentWizardRepository($db);
$contextAdapter = new WizardVerticalContextAdapter(
    new DatabaseWizardVerticalContextRepository($db, $registry),
    new VerticalContextComposer(),
);
$proposalRepository = new DatabaseWizardRecompositionRepository($db);
$enrichment = new RecordingEnrichmentDispatcher();
$recomposition = new WizardAnswerRecompositionService($contextAdapter, $proposalRepository, $enrichment);
$runtime = new WizardRuntime(
    $registry,
    $wizardRepository,
    new WizardBranchEvaluator(),
    new WizardAnswerValidator(),
    new WizardRiskPolicy(),
    new WizardDependencyResolver(),
);
$action = new SaveWizardAnswer($runtime, $recomposition);
$save = static function (string $value, string $idempotencyKey, int $companyId = 10, string $source = 'api') use ($action) {
    return $action->handle(new ActionRequest(
        key: 'workcore.wizard_answer.save',
        payload: [
            'run_public_id' => 'run-10', 'question_key' => 'company.name', 'value' => $value,
            'source' => 'client-controlled', 'confidence' => 0.01, 'is_confirmed' => false,
            'agent_public_id' => 'client-agent',
        ],
        companyId: $companyId,
        actorId: 7,
        idempotencyKey: $idempotencyKey,
        source: $source,
    ));
};

$first = $save('Clean Co', 'answer-1');
$runTest('authoritative answer event and revision', function () use ($first, $db, $assert): void {
    $answer = $db->table('tz_wizard_answers')->where('run_id', 100)->first();
    $metadata = json_decode((string) $db->table('tz_wizard_runs')->where('id', 100)->value('metadata'), true, 512, JSON_THROW_ON_ERROR);
    $assert($answer->source === 'user_entered', 'Client source was trusted.');
    $assert(abs((float) $answer->confidence - 1.0) < 0.0001, 'Client confidence was trusted.');
    $assert((bool) $answer->is_confirmed, 'User answer was not confirmed.');
    $assert($answer->answered_by_agent_public_id === null, 'Client agent identity was trusted.');
    $assert($metadata['answer_revision'] === 1, 'Revision did not increment.');
    $assert($first->data['changed'] === true, 'Change was not recorded.');
    $assert($first->data['affected_sections'] === ['brand', 'business_profile'], 'Dependencies were not targeted.');
    $assert($first->data['change_event']['company_id'] === 10, 'Tenant was not server-derived.');
    $assert($first->events[0]->name === 'workcore.wizard.answer.changed', 'Changed event was not emitted.');
});

$runTest('affected approvals become stale', function () use ($db, $assert): void {
    $statuses = $db->table('tz_wizard_section_approvals')->where('run_id', 100)->pluck('status', 'section_key')->all();
    $assert($statuses['business_profile'] === 'stale', 'Primary section approval remained current.');
    $assert($statuses['brand'] === 'stale', 'Dependent section approval remained current.');
    $assert($statuses['operations'] === 'approved', 'Unrelated approval was invalidated.');
});

$runTest('deterministic drafts are immediate and enrichment is queued', function () use ($db, $enrichment, $assert): void {
    $rows = $db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->orderBy('id')->get();
    $payloads = $rows->map(static fn ($row) => json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR))->all();
    $assert($rows->count() === 2, 'Expected one draft per affected section.');
    $assert(array_column($payloads, 'section_key') === ['brand', 'business_profile'], 'Draft targeting is incorrect.');
    $assert($payloads[0]['answer_revision'] === 1, 'Draft revision is incorrect.');
    $assert(strlen($payloads[0]['context_hash']) === 64, 'Draft context hash is missing.');
    $assert($payloads[0]['source'] === 'deterministic', 'Draft source is incorrect.');
    $assert($payloads[0]['confirmation_state'] === 'draft', 'Draft confirmation state is incorrect.');
    $assert(count($enrichment->calls) === 1, 'AI enrichment was not queued once.');
});

$duplicate = $save('Clean Co', 'answer-duplicate');
$runTest('identical answers are idempotent', function () use ($duplicate, $db, $enrichment, $assert): void {
    $metadata = json_decode((string) $db->table('tz_wizard_runs')->where('id', 100)->value('metadata'), true, 512, JSON_THROW_ON_ERROR);
    $assert($duplicate->data['changed'] === false, 'Duplicate answer was treated as changed.');
    $assert($duplicate->events === [], 'Duplicate answer emitted a domain event.');
    $assert($metadata['answer_revision'] === 1, 'Duplicate answer incremented revision.');
    $assert($db->table('tz_wizard_events')->where('event_type', 'wizard.answer.changed')->count() === 1, 'Duplicate change event was stored.');
    $assert($db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->count() === 2, 'Duplicate drafts were stored.');
    $assert(count($enrichment->calls) === 1, 'Duplicate AI enrichment was queued.');
});

$runTest('tenant isolation fails closed', function () use ($save, $recomposition, $first, $assert): void {
    try {
        $save('Other Co', 'answer-cross-tenant', 11);
        $assert(false, 'Cross-tenant answer was accepted.');
    } catch (InvalidArgumentException) {
    }
    try {
        $recomposition->project(WizardAnswerChanged::fromArray([...$first->data['change_event'], 'company_id' => 11]));
        $assert(false, 'Cross-tenant recomposition was accepted.');
    } catch (InvalidArgumentException) {
    }
});

$oldEvent = WizardAnswerChanged::fromArray($first->data['change_event']);
$oldDraft = json_decode((string) $db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->orderBy('id')->value('payload'), true, 512, JSON_THROW_ON_ERROR);
$second = $save('Clean Co Group', 'answer-2');
$runTest('stale AI proposal is rejected', function () use ($recomposition, $oldEvent, $oldDraft, $db, $assert): void {
    $proposal = VerticalAIProposal::create(
        updates: ['terminology' => ['entity.company.singular' => 'Business'], 'context' => [], 'questions' => []],
        confidence: 0.8,
        rationale: 'Old-context suggestion.',
        provenance: [['source' => 'wizard.answer.company.name', 'path' => '/resolved/terminology/entity.company.singular', 'reason' => 'Wording']],
        contextHash: $oldDraft['context_hash'], providerId: 'test-provider', modelId: 'test-model', attempts: 1,
        fallbackUsed: false,
        audit: ['schema_version' => '1.0.0', 'prompt_hash' => str_repeat('a', 64), 'response_hash' => str_repeat('b', 64), 'validation_errors' => []],
    );
    try {
        $recomposition->applyAI($oldEvent, $proposal);
        $assert(false, 'Stale proposal was accepted.');
    } catch (InvalidArgumentException) {
    }
    $assert($db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.ai')->count() === 0, 'Stale AI revision was stored.');
});

$runTest('current AI proposal supersedes deterministic drafts', function () use ($recomposition, $second, $db, $assert): void {
    $event = WizardAnswerChanged::fromArray($second->data['change_event']);
    $draft = json_decode((string) $db->table('tz_wizard_events')->where('event_type', 'wizard.context.proposal.drafted')->orderByDesc('id')->value('payload'), true, 512, JSON_THROW_ON_ERROR);
    $proposal = VerticalAIProposal::create(
        updates: ['terminology' => ['entity.company.singular' => 'Business'], 'context' => ['theme' => ['density' => 'field']], 'questions' => []],
        confidence: 0.86,
        rationale: 'Current-context suggestion.',
        provenance: [['source' => 'wizard.answer.company.name', 'path' => '/resolved/terminology/entity.company.singular', 'reason' => 'Wording']],
        contextHash: $draft['context_hash'], providerId: 'test-provider', modelId: 'test-model', attempts: 1,
        fallbackUsed: false,
        audit: ['schema_version' => '1.0.0', 'prompt_hash' => str_repeat('c', 64), 'response_hash' => str_repeat('d', 64), 'validation_errors' => []],
    );
    $rows = $recomposition->applyAI($event, $proposal);
    $assert(count($rows) === 2, 'Expected AI revision per affected section.');
    foreach ($rows as $row) {
        $assert($row['source'] === 'ai', 'AI source was not recorded.');
        $assert($row['answer_revision'] === 2, 'AI revision does not match current answer revision.');
        $assert($row['supersedes_public_id'] !== null, 'Supersession link is missing.');
        $assert($row['confirmation_state'] === 'draft', 'AI proposal bypassed confirmation.');
    }
});

echo "{$passes}/7 tests passed\n";
