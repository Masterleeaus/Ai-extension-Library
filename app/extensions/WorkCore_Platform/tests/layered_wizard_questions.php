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
        return new DateTimeImmutable('2026-08-05 18:20:00', new DateTimeZone('Australia/Sydney'));
    }
}

spl_autoload_register(static function (string $class) use ($repoRoot): void {
    $prefixes = [
        'App\\Domains\\WorkCore\\System\\Actions\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Actions/',
        'App\\Domains\\WorkCore\\System\\References\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/References/',
        'App\\Domains\\WorkCore\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/',
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

use App\Domains\WorkCore\System\Modules\Wizards\Repositories\EloquentWizardRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Services\LayeredWizardQuestionCatalogue;
use App\Domains\WorkCore\System\Modules\Wizards\Services\LayeredWizardQuestionComposer;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardAnswerValidator;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardBranchEvaluator;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDefinitionRegistry;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDependencyResolver;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardQuestionPlanner;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardRiskPolicy;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardRuntime;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

$tests = [];
$test = static function (string $name, callable $callback) use (&$tests): void { $tests[$name] = $callback; };
$assert = static function (bool $condition, string $message = 'Assertion failed'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$question = static function (array $definition, string $key): array {
    foreach ($definition['sections'] as $section) {
        foreach ($section['questions'] as $candidate) {
            if ($candidate['key'] === $key) {
                return $candidate;
            }
        }
    }
    throw new RuntimeException("Question {$key} not found.");
};
$questionKeys = static function (array $definition): array {
    $keys = [];
    foreach ($definition['sections'] as $section) {
        foreach ($section['questions'] as $candidate) {
            $keys[] = $candidate['key'];
        }
    }
    return $keys;
};

$catalogue = LayeredWizardQuestionCatalogue::defaults();
$composer = new LayeredWizardQuestionComposer($catalogue);
$planner = new WizardQuestionPlanner(new WizardBranchEvaluator());
$request = [
    'country' => 'AU',
    'vertical_family' => 'field-home-services',
    'subtype' => 'cleaning',
    'capabilities' => ['customer-portal', 'online-booking'],
    'tenant_layer' => [
        'id' => 'tenant.clean-co',
        'version' => '1.0.0',
        'questions' => [[
            'slot' => 'business.optional',
            'key' => 'tenant.referral_source',
            'question' => 'How did customers usually find you?',
            'response_type' => 'text',
            'required' => false,
            'risk' => 'low',
        ]],
    ],
    'ai_layer' => [
        'id' => 'ai.proposal-1',
        'version' => '1.0.0',
        'wording' => [
            'company.name' => [
                'question' => 'What business name should appear across your workspace?',
                'help_text' => 'Use the public trading name.',
            ],
        ],
        'questions' => [[
            'slot' => 'operations.optional',
            'key' => 'ai.peak_periods',
            'question' => 'Are there predictable peak periods?',
            'response_type' => 'text',
            'required' => false,
            'risk' => 'low',
        ]],
    ],
];

$test('layers compose in fixed precedence with stable semantic keys and ownership', function () use ($composer, $request, $question, $assert): void {
    $definition = $composer->compose($request);
    $assert($definition['key'] === 'layered-company-launch');
    $assert($definition['schema_version'] === '1.0.0');
    $assert($definition['composition']['layers'] === [
        'generic.company-launch',
        'country.au',
        'vertical.field-home-services',
        'subtype.cleaning',
        'capability.customer-portal',
        'capability.online-booking',
        'tenant.clean-co',
        'ai.proposal-1',
    ]);
    $assert(strlen($definition['composition_hash']) === 64);
    $assert($question($definition, 'cleaning.service_types')['source_layer'] === 'subtype.cleaning');
    $assert($question($definition, 'booking.minimum_notice')['source_layer'] === 'capability.online-booking');
    $assert($question($definition, 'tenant.referral_source')['source_layer'] === 'tenant.clean-co');
    $assert($question($definition, 'ai.peak_periods')['source_layer'] === 'ai.proposal-1');
    $company = $question($definition, 'company.name');
    $assert($company['source_layer'] === 'generic.company-launch', 'AI wording changed semantic ownership.');
    $assert($company['wording_source_layer'] === 'ai.proposal-1');
    $assert($company['question'] === 'What business name should appear across your workspace?');
});

$test('composition is deterministic across unordered capability and custom input', function () use ($composer, $request, $assert): void {
    $reordered = $request;
    $reordered['capabilities'] = array_reverse($request['capabilities']);
    $reordered['tenant_layer']['questions'] = array_reverse($request['tenant_layer']['questions']);
    $first = $composer->compose($request);
    $second = $composer->compose($reordered);
    $assert($first['composition_hash'] === $second['composition_hash']);
    $assert($first === $second);
});

$test('mandatory identity privacy payment permission compliance evidence and activation questions are protected', function () use ($composer, $request, $question, $assert): void {
    $definition = $composer->compose($request);
    $domains = [];
    foreach (['company.name', 'privacy.acceptance', 'payments.primary_method', 'security.primary_admin', 'compliance.applicable', 'activation.evidence_ack', 'activation.confirm'] as $key) {
        $item = $question($definition, $key);
        $assert($item['protected'] === true, "{$key} is not protected.");
        $assert($item['required'] === true, "{$key} is no longer required.");
        $domains[] = $item['mandatory_domain'];
    }
    sort($domains);
    $assert($domains === ['activation', 'compliance', 'evidence', 'identity', 'payment', 'permission', 'privacy']);

    $remove = $request;
    $remove['ai_layer']['remove_questions'] = ['company.name'];
    try {
        $composer->compose($remove);
        $assert(false, 'AI removed a mandatory question.');
    } catch (InvalidArgumentException) {
    }

    $required = $request;
    $required['ai_layer']['questions'][0]['required'] = true;
    try {
        $composer->compose($required);
        $assert(false, 'AI added a mandatory question.');
    } catch (InvalidArgumentException) {
    }

    $schemaChange = $request;
    $schemaChange['ai_layer']['wording']['company.name']['response_type'] = 'boolean';
    try {
        $composer->compose($schemaChange);
        $assert(false, 'AI changed a mandatory question schema.');
    } catch (InvalidArgumentException) {
    }
});

$test('AI and tenant questions are confined to declared extension slots', function () use ($composer, $request, $assert): void {
    $invalid = $request;
    $invalid['ai_layer']['questions'][0]['slot'] = 'security.mandatory';
    try {
        $composer->compose($invalid);
        $assert(false, 'AI wrote outside a declared extension slot.');
    } catch (InvalidArgumentException) {
    }

    $tenantInvalid = $request;
    $tenantInvalid['tenant_layer']['questions'][0]['slot'] = 'unknown.slot';
    try {
        $composer->compose($tenantInvalid);
        $assert(false, 'Tenant custom question wrote outside a declared slot.');
    } catch (InvalidArgumentException) {
    }
});

$test('cleaning is the reference subtype and unsupported subtypes receive generic fallback', function () use ($composer, $request, $questionKeys, $question, $assert): void {
    $cleaning = $composer->compose($request);
    $assert(in_array('cleaning.service_types', $questionKeys($cleaning), true));
    $assert(!in_array('service.offering', $questionKeys($cleaning), true));

    $unknown = $request;
    $unknown['subtype'] = 'orbital-window-farming';
    $unknown['capabilities'] = [];
    unset($unknown['tenant_layer'], $unknown['ai_layer']);
    $fallback = $composer->compose($unknown);
    $assert(in_array('service.offering', $questionKeys($fallback), true));
    $assert($question($fallback, 'service.offering')['source_layer'] === 'fallback.generic-service');
    $assert($fallback['composition']['fallback_used'] === true);
});

$test('dependency graph reveals skips and reprioritises questions without changing the definition', function () use ($composer, $planner, $request, $assert): void {
    $definition = $composer->compose($request);
    $hash = $definition['composition_hash'];
    $basePlan = $planner->plan($definition, ['cleaning.service_types' => ['office'], 'business.online_booking' => false]);
    $shortStayPlan = $planner->plan($definition, ['cleaning.service_types' => ['short_stay'], 'business.online_booking' => true]);
    $baseKeys = array_column($basePlan, 'question_key');
    $shortStayKeys = array_column($shortStayPlan, 'question_key');
    $assert(!in_array('cleaning.turnover_window', $baseKeys, true));
    $assert(in_array('cleaning.turnover_window', $shortStayKeys, true));
    $booking = array_values(array_filter($shortStayPlan, static fn (array $row): bool => $row['question_key'] === 'booking.minimum_notice'))[0];
    $assert($booking['effective_order'] < $booking['base_order'], 'Conditional priority did not change.');
    $assert($definition['composition_hash'] === $hash, 'Planning rewrote definition history.');
    $assert(in_array('cleaning.service_types', $definition['dependency_graph']['cleaning.turnover_window']['depends_on'], true));
});

$test('run start snapshots the complete definition and later pack upgrades cannot rewrite the active run', function () use ($catalogue, $request, $assert): void {
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $db = $capsule->getConnection();
    $schema = $db->getSchemaBuilder();
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
    });
    $schema->create('tz_wizard_section_approvals', static function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedBigInteger('company_id');
        $table->unsignedBigInteger('run_id');
        $table->string('section_key');
        $table->string('status');
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

    $repository = new EloquentWizardRepository($db);
    $registry = new WizardDefinitionRegistry($db);
    $runtimeV1 = new WizardRuntime(
        $registry,
        $repository,
        new WizardBranchEvaluator(),
        new WizardAnswerValidator(),
        new WizardRiskPolicy(),
        new WizardDependencyResolver(),
        new LayeredWizardQuestionComposer($catalogue),
        new WizardQuestionPlanner(new WizardBranchEvaluator()),
    );
    $record = $runtimeV1->start('layered-company-launch', ['composition' => $request], 10, 7);
    $run = $repository->run($record['public_id'], 10);
    $metadata = json_decode((string) $run['metadata'], true, 512, JSON_THROW_ON_ERROR);
    $snapshot = $metadata['definition_snapshot'];
    $assert($metadata['definition_schema_version'] === '1.0.0');
    $assert($metadata['definition_hash'] === $snapshot['composition_hash']);
    $assert($record['definition_hash'] === $snapshot['composition_hash']);

    $catalogueV2 = $catalogue;
    $catalogueV2['layers']['generic.company-launch']['sections']['identity']['questions']['company.name']['question'] = 'NEW PACK WORDING THAT MUST NOT ALTER ACTIVE RUNS';
    $runtimeV2 = new WizardRuntime(
        $registry,
        $repository,
        new WizardBranchEvaluator(),
        new WizardAnswerValidator(),
        new WizardRiskPolicy(),
        new WizardDependencyResolver(),
        new LayeredWizardQuestionComposer($catalogueV2),
        new WizardQuestionPlanner(new WizardBranchEvaluator()),
    );
    $next = $runtimeV2->next($record['public_id'], 10);
    $assert($next['question']['key'] === 'company.name');
    $assert($next['question']['question'] !== 'NEW PACK WORDING THAT MUST NOT ALTER ACTIVE RUNS');
    $assert($next['definition_hash'] === $snapshot['composition_hash']);
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
