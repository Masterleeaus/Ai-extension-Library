<?php

declare(strict_types=1);

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\Extensions\\Migration\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = $root . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

use App\Extensions\Migration\System\Canonical\BuiltinCanonicalEntityCatalog;
use App\Extensions\Migration\System\Canonical\CanonicalEntityDefinition;
use App\Extensions\Migration\System\Canonical\CanonicalEntityRegistry;
use App\Extensions\Migration\System\Canonical\ModuleAvailabilityResolver;
use App\Extensions\Migration\System\Destination\Contracts\DestinationHandlerInterface;
use App\Extensions\Migration\System\Destination\DestinationHandlerRegistry;
use App\Extensions\Migration\System\Destination\DestinationWriteContext;
use App\Extensions\Migration\System\Destination\DestinationWriteResult;
use App\Extensions\Migration\System\Identity\CanonicalChecksum;
use App\Extensions\Migration\System\Identity\ExternalIdScope;
use App\Extensions\Migration\System\Mapping\MappingPlan;
use App\Extensions\Migration\System\Mapping\MappingPreviewService;
use App\Extensions\Migration\System\Mapping\MappingRule;
use App\Extensions\Migration\System\Mapping\MappingValidationException;
use App\Extensions\Migration\System\Mapping\TransformationEngine;
use App\Extensions\Migration\System\Mapping\TransformationRegistry;
use App\Extensions\Migration\System\Matching\DuplicatePolicy;
use App\Extensions\Migration\System\Matching\DuplicateResolver;
use App\Extensions\Migration\System\Security\SensitiveValueMasker;
use App\Extensions\Migration\System\Suggestions\AdvisoryMappingSuggestionService;
use App\Extensions\Migration\System\Suggestions\MappingSuggestionApprovalService;

function failContract(string $message): never
{
    fwrite(STDERR, "MigrationCanonicalMappingContractTest: FAIL - {$message}\n");
    exit(1);
}

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        failContract($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        failContract($message . ' expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
    }
}

function assertThrows(callable $callback, string $exceptionClass, string $message): void
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        if ($throwable instanceof $exceptionClass) {
            return;
        }
        failContract($message . '; wrong exception ' . $throwable::class . ': ' . $throwable->getMessage());
    }

    failContract($message . '; no exception thrown');
}

$requiredRuntimeFiles = [
    'System/Canonical/CanonicalEntityDefinition.php',
    'System/Canonical/CanonicalEntityRegistry.php',
    'System/Canonical/BuiltinCanonicalEntityCatalog.php',
    'System/Canonical/ModuleAvailabilityResolver.php',
    'System/Canonical/Contracts/CanonicalEntityPackInterface.php',
    'System/Destination/Contracts/DestinationHandlerInterface.php',
    'System/Destination/DestinationHandlerRegistry.php',
    'System/Destination/DestinationWriteContext.php',
    'System/Destination/DestinationWriteResult.php',
    'System/Mapping/TransformationRegistry.php',
    'System/Mapping/TransformationEngine.php',
    'System/Mapping/MappingRule.php',
    'System/Mapping/MappingPlan.php',
    'System/Mapping/MappingPreviewService.php',
    'System/Mapping/MappingValidationException.php',
    'System/Identity/CanonicalChecksum.php',
    'System/Identity/ExternalIdScope.php',
    'System/Matching/DuplicatePolicy.php',
    'System/Matching/DuplicateDecision.php',
    'System/Matching/DuplicateResolver.php',
    'System/Suggestions/MappingSuggestion.php',
    'System/Suggestions/AdvisoryMappingSuggestionService.php',
    'System/Suggestions/MappingSuggestionApprovalService.php',
];

foreach ($requiredRuntimeFiles as $relative) {
    if (! is_file($root . '/' . $relative)) {
        failContract('required runtime file does not exist: ' . $relative);
    }
}

$custom = new CanonicalEntityDefinition(
    key: 'custom.asset',
    name: 'Custom Asset',
    fields: [
        'external_id' => ['type' => 'string', 'required' => true],
        'name' => ['type' => 'string', 'required' => true],
        'notes' => ['type' => 'string', 'required' => false],
    ],
    identityRules: [['fields' => ['external_id'], 'match' => 'exact']],
    dependencyKeys: ['core.company'],
    requiredModules: ['custom.module'],
    handlerKey: 'custom.asset',
);
assertSameValue(['external_id', 'name'], $custom->requiredFields(), 'required fields must derive deterministically from schema');
assertSameValue(['external_id'], $custom->primaryIdentityFields(), 'primary identity fields must be explicit');

$registry = new CanonicalEntityRegistry();
$registry->register($custom);
assertSameValue($custom, $registry->get('custom.asset'), 'external verticals must be able to register canonical entities');
assertThrows(static fn () => $registry->register($custom), InvalidArgumentException::class, 'duplicate canonical keys must fail closed');

$modules = new ModuleAvailabilityResolver(['custom.module' => false]);
assertTrue(! array_key_exists('custom.asset', $registry->available($modules)), 'entities with unavailable required modules must not be advertised');
$modules->set('custom.module', true);
assertTrue(array_key_exists('custom.asset', $registry->available($modules)), 'module availability must make an external entity available without core edits');

$catalog = new BuiltinCanonicalEntityCatalog();
$definitions = $catalog->definitions();
$keys = array_map(static fn (CanonicalEntityDefinition $definition): string => $definition->key, $definitions);
foreach ([
    'core.company',
    'core.customer',
    'core.contact',
    'core.address',
    'core.product',
    'core.service',
    'core.invoice',
    'field.job',
    'accommodation.reservation',
    'real_estate.lease',
    'salon.appointment',
    'fitness.membership',
    'automotive.vehicle',
    'ecommerce.order',
    'hire.rental_booking',
    'booking.resource_booking',
] as $requiredKey) {
    assertTrue(in_array($requiredKey, $keys, true), 'built-in canonical catalog missing ' . $requiredKey);
}
assertSameValue(count($keys), count(array_unique($keys)), 'built-in canonical keys must be unique');

$destinationRegistry = new DestinationHandlerRegistry();
$handler = new class implements DestinationHandlerInterface {
    public function validate(array $payload, CanonicalEntityDefinition $definition): array
    {
        return [];
    }

    public function identity(array $payload, CanonicalEntityDefinition $definition): array
    {
        return ['external_id' => $payload['external_id'] ?? null];
    }

    public function write(array $payload, DestinationWriteContext $context): DestinationWriteResult
    {
        return new DestinationWriteResult('created', 'target-1', $payload);
    }
};
$destinationRegistry->register('custom.asset', $handler);
assertSameValue($handler, $destinationRegistry->get('custom.asset'), 'destination handlers must be independently registrable');

$transformations = new TransformationRegistry();
$engine = new TransformationEngine($transformations);
$preview = new MappingPreviewService($engine);

$customer = new CanonicalEntityDefinition(
    key: 'core.customer',
    name: 'Customer',
    fields: [
        'external_id' => ['type' => 'string', 'required' => true],
        'name' => ['type' => 'string', 'required' => true],
        'email' => ['type' => 'string', 'required' => false],
        'status' => ['type' => 'string', 'required' => false],
    ],
    identityRules: [['fields' => ['external_id'], 'match' => 'exact']],
    handlerKey: 'core.customer',
);

$plan = new MappingPlan([
    new MappingRule('legacy_id', 'external_id', [['transform' => 'trim']]),
    new MappingRule('full_name', 'name', [['transform' => 'trim']]),
    new MappingRule('EMAIL', 'email', [['transform' => 'trim'], ['transform' => 'lowercase']]),
    new MappingRule('state', 'status', [[
        'transform' => 'map_values',
        'config' => ['map' => ['A' => 'active', 'I' => 'inactive'], 'default' => 'unknown'],
    ]]),
]);

$source = ['legacy_id' => ' 42 ', 'full_name' => ' Alice Example ', 'EMAIL' => ' ALICE@EXAMPLE.COM ', 'state' => 'A'];
$first = $preview->preview($customer, $plan, $source);
$second = $preview->preview($customer, $plan, $source);
assertSameValue($first, $second, 'mapping preview must be deterministic for the same input');
assertSameValue('42', $first['payload']['external_id'] ?? null, 'trim transform must execute');
assertSameValue('alice@example.com', $first['payload']['email'] ?? null, 'lowercase transform must execute after trim');
assertSameValue('active', $first['payload']['status'] ?? null, 'map_values transform must execute deterministically');
assertTrue(count($first['audit'] ?? []) >= 4, 'mapping preview must expose an audit trace');
foreach ($first['audit'] as $entry) {
    assertTrue(isset($entry['target_field'], $entry['transforms'], $entry['before_digest'], $entry['after_digest']), 'audit entries must include field, transforms and before/after digests');
}

$missingRequired = new MappingPlan([
    new MappingRule('legacy_id', 'external_id', [['transform' => 'trim']]),
]);
assertThrows(
    static fn () => $preview->preview($customer, $missingRequired, ['legacy_id' => '1']),
    MappingValidationException::class,
    'required destination fields must never be silently omitted',
);

assertThrows(
    static fn () => $engine->apply('value', [['transform' => 'php_eval', 'config' => ['code' => 'return 1;']]]),
    InvalidArgumentException::class,
    'unknown or executable transformation operations must be rejected',
);

$checksum = new CanonicalChecksum();
assertSameValue(
    $checksum->hash(['b' => 2, 'a' => ['y' => 2, 'x' => 1]]),
    $checksum->hash(['a' => ['x' => 1, 'y' => 2], 'b' => 2]),
    'canonical checksum must be independent of associative key ordering',
);
assertTrue($checksum->hash(['a' => 1]) !== $checksum->hash(['a' => 2]), 'checksum must change when payload changes');

$scopeA = new ExternalIdScope(10, 20, 30, 'customers', 'source-1');
$scopeB = new ExternalIdScope(10, 20, 31, 'customers', 'source-1');
assertTrue($scopeA->key() !== $scopeB->key(), 'external-ID scope must include connection_id');
assertSameValue(10, $scopeA->companyId, 'external-ID scope must retain company_id');
assertSameValue(30, $scopeA->connectionId, 'external-ID scope must retain connection_id');
assertThrows(static fn () => new ExternalIdScope(0, 20, 30, 'customers', 'source-1'), InvalidArgumentException::class, 'external IDs require a positive company_id');
assertThrows(static fn () => new ExternalIdScope(10, 20, 0, 'customers', 'source-1'), InvalidArgumentException::class, 'external IDs require a positive connection_id');

$resolver = new DuplicateResolver(minimumFuzzyConfidence: 0.85);
$highRisk = $resolver->resolve(DuplicatePolicy::MERGE, false, 0.94, 0.72, 0.40);
assertSameValue('review', $highRisk->action, 'fuzzy match above approved risk threshold must never auto-merge');
assertTrue($highRisk->requiresReview, 'high-risk fuzzy match must require review');
$lowRisk = $resolver->resolve(DuplicatePolicy::MERGE, false, 0.94, 0.20, 0.40);
assertSameValue('merge', $lowRisk->action, 'approved low-risk fuzzy match may follow merge policy');
$exact = $resolver->resolve(DuplicatePolicy::UPDATE, true, 1.0, 0.90, 0.10);
assertSameValue('update', $exact->action, 'exact identity match may follow explicit update policy');

$suggestionService = new AdvisoryMappingSuggestionService(new SensitiveValueMasker());
$suggestion = $suggestionService->create(
    sourceField: 'email_address',
    targetField: 'email',
    confidence: 0.93,
    evidence: ['email' => 'alice@example.com', 'api_token' => 'secret-token', 'source_type' => 'string'],
);
assertSameValue('advisory', $suggestion->status, 'AI mapping suggestion must remain advisory by default');
assertTrue($suggestion->requiresApproval, 'AI mapping suggestion must require explicit approval');
assertSameValue(null, $suggestion->approvedBy, 'AI mapping suggestion must not self-approve');
assertSameValue('[MASKED]', $suggestion->evidence['email'] ?? null, 'suggestion evidence must mask email samples');
assertSameValue('[MASKED]', $suggestion->evidence['api_token'] ?? null, 'suggestion evidence must mask secret/token samples');

$approval = new MappingSuggestionApprovalService();
assertThrows(static fn () => $approval->approve($suggestion, 0), InvalidArgumentException::class, 'approval requires an explicit positive actor ID');
$approved = $approval->approve($suggestion, 77);
assertSameValue('approved', $approved->status, 'explicit approval must produce approved status');
assertSameValue(77, $approved->approvedBy, 'approval actor must be auditable');

$connectorRoot = $root . '/System/Connectors';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($connectorRoot));
foreach ($iterator as $file) {
    if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $contents = file_get_contents($file->getPathname()) ?: '';
    foreach (['App\\Models\\', 'Illuminate\\Database\\Eloquent\\Model', 'Illuminate\\Support\\Facades\\DB'] as $forbidden) {
        if (str_contains($contents, $forbidden)) {
            failContract('source connector directly references Titan persistence primitive ' . $forbidden . ' in ' . $file->getFilename());
        }
    }
}

$migrationFiles = glob($root . '/database/migrations/*scope_migration_external_ids_to_connections.php') ?: [];
assertTrue(count($migrationFiles) === 1, 'connection-scoped external-ID migration must exist exactly once');
$migrationContents = file_get_contents($migrationFiles[0]) ?: '';
assertTrue(str_contains($migrationContents, "'connection_id'"), 'external-ID migration must add connection_id');
assertTrue(str_contains($migrationContents, 'mig_external_ids_source_connection_uq'), 'external-ID migration must create connection-scoped source uniqueness');

fwrite(STDOUT, "MigrationCanonicalMappingContractTest: PASS\n");
