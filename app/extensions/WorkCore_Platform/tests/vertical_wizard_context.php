<?php

declare(strict_types=1);

$repoRoot = getenv('AI_EXTENSIONS_REPO_ROOT') ?: dirname(__DIR__, 3);
$vendorAutoload = getenv('WORKCORE_TEST_VENDOR_AUTOLOAD') ?: $repoRoot . '/vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    fwrite(STDERR, "Missing test vendor autoload: {$vendorAutoload}\n");
    exit(2);
}
require $vendorAutoload;

spl_autoload_register(static function (string $class) use ($repoRoot): void {
    $prefixes = [
        'App\\Domains\\WorkCore\\' => $repoRoot . '/app/extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/',
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

use App\Domains\WorkCore\System\Modules\Wizards\Repositories\DatabaseWizardVerticalContextRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDefinitionRegistry;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardVerticalContextAdapter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

$tests = [];
$test = static function (string $name, callable $callback) use (&$tests): void {
    $tests[$name] = $callback;
};
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
    $table->string('status');
    $table->text('definition');
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
});

$definitionV2 = [
    'key' => 'tenant-onboarding',
    'version' => 2,
    'title' => 'Tenant onboarding v2',
    'sections' => [[
        'key' => 'business_profile',
        'questions' => [
            ['key' => 'company.name', 'response_type' => 'text', 'handling' => 'ask', 'target_owner' => 'Company', 'risk' => 'low', 'required' => true],
            ['key' => 'company.country_code', 'response_type' => 'country', 'handling' => 'infer_then_confirm', 'target_owner' => 'Company', 'risk' => 'low'],
            ['key' => 'company.locale', 'response_type' => 'text', 'handling' => 'infer_then_confirm', 'target_owner' => 'Company context', 'risk' => 'low'],
            ['key' => 'company.timezone', 'response_type' => 'timezone', 'handling' => 'infer_then_confirm', 'target_owner' => 'Company', 'risk' => 'low'],
            ['key' => 'company.currency_code', 'response_type' => 'currency', 'handling' => 'infer_then_confirm', 'target_owner' => 'Company/Finance', 'risk' => 'low'],
            ['key' => 'business.verticals', 'response_type' => 'multi_select', 'handling' => 'ask', 'target_owner' => 'Capability/vertical registry', 'risk' => 'medium'],
            ['key' => 'ai.credentials', 'response_type' => 'secret_connection', 'handling' => 'secure_task', 'target_owner' => 'Host AI registry/Vault', 'risk' => 'high'],
        ],
    ]],
];
$definitionV3 = [...$definitionV2, 'version' => 3, 'title' => 'Tenant onboarding v3'];

$db->table('tz_wizard_definitions')->insert([
    ['public_id' => 'def-v2', 'company_id' => 10, 'definition_key' => 'tenant-onboarding', 'version' => 2, 'status' => 'published', 'definition' => json_encode($definitionV2, JSON_THROW_ON_ERROR)],
    ['public_id' => 'def-v3', 'company_id' => 10, 'definition_key' => 'tenant-onboarding', 'version' => 3, 'status' => 'published', 'definition' => json_encode($definitionV3, JSON_THROW_ON_ERROR)],
]);
$db->table('tz_wizard_runs')->insert([
    'id' => 100,
    'public_id' => 'run-tenant-10',
    'company_id' => 10,
    'definition_key' => 'tenant-onboarding',
    'definition_version' => 2,
    'status' => 'in_progress',
    'mode' => 'hybrid',
    'current_section_key' => 'business_profile',
    'current_question_key' => 'company.name',
    'initiated_by_user_id' => 7,
    'completion_percent' => 65,
    'metadata' => json_encode([
        'vertical_family' => 'field-home-services',
        'subtype' => 'cleaning',
        'capabilities' => ['recurring-services', 'photo-evidence'],
        'launch_phase' => 'business_profile',
        'answer_revision' => 12,
    ], JSON_THROW_ON_ERROR),
    'started_at' => '2026-08-05 10:00:00',
    'created_at' => '2026-08-05 10:00:00',
    'updated_at' => '2026-08-05 10:15:00',
]);

$answerRows = [
    ['company.name', 'Clean Co', 'user_entered', 1.0, 1],
    ['company.country_code', 'AU', 'user_entered', 1.0, 1],
    ['company.locale', 'en-AU', 'ai_suggested', 0.91, 1],
    ['company.timezone', 'Australia/Sydney', 'user_entered', 1.0, 1],
    ['company.currency_code', 'AUD', 'user_entered', 1.0, 1],
    ['business.verticals', ['field-home-services'], 'user_entered', 1.0, 1],
    ['ai.credentials', ['status' => 'connected', 'provider_reference' => 'openai-company-10', 'verified_at' => '2026-08-05T10:10:00+10:00', 'api_key' => 'sk-must-not-leak'], 'user_entered', 1.0, 1],
];
foreach ($answerRows as $index => [$key, $value, $source, $confidence, $confirmed]) {
    $db->table('tz_wizard_answers')->insert([
        'id' => 200 + $index,
        'public_id' => 'answer-' . $index,
        'company_id' => 10,
        'run_id' => 100,
        'question_key' => $key,
        'value' => json_encode($value, JSON_THROW_ON_ERROR),
        'source' => $source,
        'confidence' => $confidence,
        'is_confirmed' => $confirmed,
        'answered_by_user_id' => 7,
        'answered_by_agent_public_id' => $source === 'ai_suggested' ? 'agent-1' : null,
        'created_at' => '2026-08-05 10:01:00',
        'updated_at' => '2026-08-05 10:12:00',
    ]);
}
$db->table('tz_wizard_section_approvals')->insert([
    'public_id' => 'approval-1',
    'company_id' => 10,
    'run_id' => 100,
    'section_key' => 'business_profile',
    'status' => 'approved',
    'summary' => json_encode(['hash' => 'summary-v12'], JSON_THROW_ON_ERROR),
    'approved_by_user_id' => 7,
    'approved_at' => '2026-08-05 10:14:00',
    'created_at' => '2026-08-05 10:14:00',
    'updated_at' => '2026-08-05 10:14:00',
]);

$registry = new WizardDefinitionRegistry($db);
$repository = new DatabaseWizardVerticalContextRepository($db, $registry);
$adapter = new WizardVerticalContextAdapter($repository, new VerticalContextComposer());

$test('repository resolves the exact run definition version rather than latest', function () use ($repository, $assert): void {
    $snapshot = $repository->snapshot('run-tenant-10', 10);
    $assert($snapshot['definition_snapshot']['version'] === 2, 'Expected definition version 2.');
    $assert($snapshot['run']['definition_version'] === 2, 'Run version changed unexpectedly.');
    $assert($snapshot['answer_revision'] === 12, 'Metadata answer revision was not preserved.');
});

$test('repository denies cross-company run access', function () use ($repository, $assert): void {
    try {
        $repository->snapshot('run-tenant-10', 11);
        $assert(false, 'Cross-company wizard run was returned.');
    } catch (\InvalidArgumentException) {
        $assert(true);
    }
});

$test('adapter maps wizard answers, locale, approvals and vertical selection', function () use ($adapter, $assert): void {
    $context = $adapter->compose('run-tenant-10', 10, ['id' => 7, 'roles' => ['owner'], 'device' => 'desktop'])->toArray();

    $assert($context['wizard']['definition_version'] === 2);
    $assert($context['wizard']['answer_revision'] === 12);
    $assert($context['wizard']['launch_phase'] === 'business_profile');
    $assert($context['locale']['country'] === 'AU');
    $assert($context['locale']['language'] === 'en-AU');
    $assert($context['locale']['timezone'] === 'Australia/Sydney');
    $assert($context['locale']['currency'] === 'AUD');
    $assert($context['resolved']['questions']['answers']['company.name']['value'] === 'Clean Co');
    $assert($context['resolved']['capabilities']['vertical_family'] === 'field-home-services');
    $assert($context['resolved']['capabilities']['subtype'] === 'cleaning');
    $assert($context['resolved']['capabilities']['selected'] === ['recurring-services', 'photo-evidence']);
    $assert($context['approvals']['business_profile']['status'] === 'approved');
    $assert($context['sources']['/resolved/questions/answers/company.locale/value']['source_type'] === 'ai_suggested');
    $assert(abs($context['sources']['/resolved/questions/answers/company.locale/value']['confidence'] - 0.91) < 0.0001);
});

$test('adapter excludes secret values and exposes connection status only', function () use ($adapter, $assert): void {
    $context = $adapter->compose('run-tenant-10', 10, ['id' => 7])->toArray();
    $encoded = json_encode($context, JSON_THROW_ON_ERROR);

    $assert(!str_contains($encoded, 'sk-must-not-leak'), 'Secret value leaked into context.');
    $assert(!isset($context['resolved']['questions']['answers']['ai.credentials']), 'Secure answer was exposed as an ordinary answer.');
    $connection = $context['resolved']['activation']['secure_connections']['ai.credentials'];
    $assert($connection['status'] === 'connected');
    $assert($connection['provider_reference'] === 'openai-company-10');
    $assert($connection['verified_at'] === '2026-08-05T10:10:00+10:00');
    $assert(!array_key_exists('api_key', $connection));
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
