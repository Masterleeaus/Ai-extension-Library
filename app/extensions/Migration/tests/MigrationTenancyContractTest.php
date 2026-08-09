<?php

declare(strict_types=1);

$root = dirname(__DIR__);

function failTest(string $message): never
{
    fwrite(STDERR, "MigrationTenancyContractTest: FAIL - {$message}\n");
    exit(1);
}

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        failTest($message);
    }
}

function assertContains(string $needle, string $haystack, string $message): void
{
    assertTrue(str_contains($haystack, $needle), $message . " (missing: {$needle})");
}

$requiredRuntimeFiles = [
    'System/Enums/MigrationRunState.php',
    'System/Enums/MigrationPermission.php',
    'System/Authorization/CompanyBoundary.php',
    'System/Tenancy/MigrationTenantContext.php',
];

foreach ($requiredRuntimeFiles as $relative) {
    $path = $root . '/' . $relative;
    if (! is_file($path)) {
        failTest("required runtime file does not exist: {$relative}");
    }
    require_once $path;
}

use App\Extensions\Migration\System\Authorization\CompanyBoundary;
use App\Extensions\Migration\System\Enums\MigrationPermission;
use App\Extensions\Migration\System\Enums\MigrationRunState;
use App\Extensions\Migration\System\Tenancy\MigrationTenantContext;

assertTrue(CompanyBoundary::allows(7, 7), 'same-company access must be allowed');
assertTrue(! CompanyBoundary::allows(7, 8), 'cross-company access must be denied');
assertTrue(! CompanyBoundary::allows(null, 8), 'missing actor company must fail closed');
assertTrue(! CompanyBoundary::allows(8, null), 'missing resource company must fail closed');

$context = new MigrationTenantContext();
assertTrue($context->companyId() === null, 'tenant context must start empty');
$result = $context->run(42, function () use ($context): string {
    assertTrue($context->companyId() === 42, 'tenant context must expose the active company inside run()');

    return $context->run(99, function () use ($context): string {
        assertTrue($context->companyId() === 99, 'nested tenant context must override company temporarily');
        return 'ok';
    });
});
assertTrue($result === 'ok', 'tenant context must return callback result');
assertTrue($context->companyId() === null, 'tenant context must restore prior company after run()');

assertTrue(MigrationRunState::DRAFT->canTransitionTo(MigrationRunState::READY), 'draft must transition to ready');
assertTrue(! MigrationRunState::DRAFT->canTransitionTo(MigrationRunState::COMPLETED), 'draft must not skip to completed');
assertTrue(MigrationRunState::RUNNING->canTransitionTo(MigrationRunState::COMPLETED), 'running must transition to completed');
assertTrue(MigrationRunState::COMPLETED->canTransitionTo(MigrationRunState::ROLLBACK_QUEUED), 'completed must allow rollback queueing');
assertTrue(! MigrationRunState::ROLLED_BACK->canTransitionTo(MigrationRunState::RUNNING), 'rolled back must be terminal');

$permissions = array_map(static fn (MigrationPermission $permission): string => $permission->value, MigrationPermission::cases());
foreach ([
    'migration.view',
    'migration.configure',
    'migration.approve',
    'migration.execute',
    'migration.resolve',
    'migration.rollback',
    'migration.purge',
] as $permission) {
    assertTrue(in_array($permission, $permissions, true), "missing permission {$permission}");
}

$migrationPath = $root . '/database/migrations/2026_08_09_180800_create_titan_migration_engine_core_tables.php';
assertTrue(is_file($migrationPath), 'core persistence migration must exist');
$migrationSource = (string) file_get_contents($migrationPath);

$tables = [
    'ext_migration_projects',
    'ext_migration_connections',
    'ext_migration_entity_plans',
    'ext_migration_mappings',
    'ext_migration_runs',
    'ext_migration_steps',
    'ext_migration_checkpoints',
    'ext_migration_external_ids',
    'ext_migration_record_results',
    'ext_migration_conflicts',
    'ext_migration_failures',
    'ext_migration_artifacts',
    'ext_migration_templates',
    'ext_migration_audit_events',
];

foreach ($tables as $table) {
    assertContains("Schema::create('{$table}'", $migrationSource, "migration must create {$table}");
}

$createBlocks = preg_split("/Schema::create\('/", $migrationSource) ?: [];
array_shift($createBlocks);
assertTrue(count($createBlocks) === count($tables), 'migration must contain exactly the required 14 table create blocks');
foreach ($createBlocks as $block) {
    $table = strtok($block, "'") ?: 'unknown';
    assertContains("'company_id'", $block, "{$table} must include company_id tenant boundary");
}

$modelNames = [
    'MigrationProject',
    'MigrationConnection',
    'MigrationEntityPlan',
    'MigrationMapping',
    'MigrationRun',
    'MigrationStep',
    'MigrationCheckpoint',
    'MigrationExternalId',
    'MigrationRecordResult',
    'MigrationConflict',
    'MigrationFailure',
    'MigrationArtifact',
    'MigrationTemplate',
    'MigrationAuditEvent',
];

foreach ($modelNames as $modelName) {
    $path = $root . "/System/Models/{$modelName}.php";
    assertTrue(is_file($path), "model {$modelName} must exist");
    $source = (string) file_get_contents($path);
    assertContains('extends TenantMigrationModel', $source, "{$modelName} must inherit tenant isolation");
}

$connectionSource = (string) file_get_contents($root . '/System/Models/MigrationConnection.php');
assertContains("'credential_reference'", $connectionSource, 'connection model must define credential_reference');
assertContains("'encrypted'", $connectionSource, 'credential_reference must use Laravel encrypted cast');
assertContains('protected $hidden', $connectionSource, 'connection model must hide secrets during serialization');

$traitSource = (string) file_get_contents($root . '/System/Tenancy/Concerns/BelongsToMigrationCompany.php');
assertContains("addGlobalScope('migration_company'", $traitSource, 'tenant model trait must install a company global scope');
assertContains("'company_id'", $traitSource, 'tenant model trait must fill company_id');

$providerSource = (string) file_get_contents($root . '/System/MigrationServiceProvider.php');
assertContains('MigrationTenantContext::class', $providerSource, 'service provider must register tenant context');
assertContains('MigrationTenantResolver::class', $providerSource, 'service provider must register tenant resolver');

$policyPath = $root . '/System/Policies/MigrationProjectPolicy.php';
assertTrue(is_file($policyPath), 'migration project policy must exist');
$policySource = (string) file_get_contents($policyPath);
foreach (['view', 'configure', 'approve', 'execute', 'resolve', 'rollback', 'purge'] as $action) {
    assertContains("function {$action}", $policySource, "policy must expose {$action} action");
}
assertContains('CompanyBoundary::allows', $policySource, 'policy must enforce company boundary before permissions');

fwrite(STDOUT, "MigrationTenancyContractTest: PASS\n");
