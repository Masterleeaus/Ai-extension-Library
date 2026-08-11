<?php

declare(strict_types=1);

$extensionRoot = dirname(__DIR__);

$requiredFiles = [
    'System/Connectors/AbstractSourceConnector.php',
    'System/Connectors/BuiltinConnectorCatalog.php',
    'System/Discovery/DiscoveryProfiler.php',
    'System/Discovery/SchemaFingerprint.php',
    'System/Discovery/SchemaChangeDetector.php',
    'System/Security/ConnectorConfigurationRedactor.php',
    'System/Security/SensitiveValueMasker.php',
    'System/Security/ReadOnlySqlGuard.php',
    'System/Security/GraphQlReadOnlyGuard.php',
    'System/Security/ArchiveSafetyGuard.php',
    'System/Connectors/File/CsvConnector.php',
    'System/Connectors/File/XlsxConnector.php',
    'System/Connectors/File/JsonConnector.php',
    'System/Connectors/File/JsonLinesConnector.php',
    'System/Connectors/File/XmlConnector.php',
    'System/Connectors/File/ZipBundleConnector.php',
    'System/Connectors/File/SqlDumpConnector.php',
    'System/Connectors/Database/MySqlConnector.php',
    'System/Connectors/Database/PostgreSqlConnector.php',
    'System/Connectors/Database/SqlServerConnector.php',
    'System/Connectors/Database/SqliteConnector.php',
    'System/Connectors/Api/RestConnector.php',
    'System/Connectors/Api/GraphqlConnector.php',
    'System/Connectors/Api/OpenApiConnector.php',
];

foreach ($requiredFiles as $relative) {
    if (! is_file($extensionRoot . '/' . $relative)) {
        fwrite(STDERR, "MigrationGenericConnectorContractTest: FAIL - required runtime file does not exist: {$relative}\n");
        exit(1);
    }
}

spl_autoload_register(static function (string $class) use ($extensionRoot): void {
    $prefix = 'App\\Extensions\\Migration\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    $path = $extensionRoot . '/' . $relative;
    if (is_file($path)) {
        require_once $path;
    }
});

use App\Extensions\Migration\System\Connectors\BuiltinConnectorCatalog;
use App\Extensions\Migration\System\Connectors\File\CsvConnector;
use App\Extensions\Migration\System\Connectors\File\JsonLinesConnector;
use App\Extensions\Migration\System\Connectors\File\SqlDumpConnector;
use App\Extensions\Migration\System\Discovery\SchemaChangeDetector;
use App\Extensions\Migration\System\Discovery\SchemaFingerprint;
use App\Extensions\Migration\System\Security\ArchiveSafetyGuard;
use App\Extensions\Migration\System\Security\ConnectorConfigurationRedactor;
use App\Extensions\Migration\System\Security\GraphQlReadOnlyGuard;
use App\Extensions\Migration\System\Security\ReadOnlySqlGuard;
use App\Extensions\Migration\System\Security\SensitiveValueMasker;

function failContract(string $message): never
{
    fwrite(STDERR, "MigrationGenericConnectorContractTest: FAIL - {$message}\n");
    exit(1);
}

function assertContract(bool $condition, string $message): void
{
    if (! $condition) {
        failContract($message);
    }
}

function expectThrows(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }

    failContract($message);
}

$expectedConnectorKeys = [
    'csv', 'xlsx', 'json', 'jsonl', 'xml', 'zip', 'sql_dump',
    'mysql', 'postgresql', 'sqlserver', 'sqlite',
    'rest', 'graphql', 'openapi',
];

$catalog = new BuiltinConnectorCatalog();
$classes = $catalog->classes();
assertContract(count($classes) === count($expectedConnectorKeys), 'builtin connector catalog must contain exactly the 14 generic connectors');

$actualKeys = [];
foreach ($classes as $class) {
    $connector = new $class();
    $definition = $connector->definition();
    $actualKeys[] = $definition->key;
    assertContract($definition->readOnly === true, "{$definition->key} must advertise read-only source behavior");
    assertContract($definition->supportsDiscovery === true, "{$definition->key} must advertise discovery support");
    assertContract($definition->capabilities !== [], "{$definition->key} must advertise explicit capabilities");
    assertContract(in_array($definition->streamingMode, ['generator', 'chunked', 'cursor', 'paged', 'bundle'], true), "{$definition->key} must advertise a truthful streaming mode");
}

sort($actualKeys);
$sortedExpected = $expectedConnectorKeys;
sort($sortedExpected);
assertContract($actualKeys === $sortedExpected, 'builtin connector keys do not match issue #379 scope');
assertContract(count(array_unique($actualKeys)) === count($actualKeys), 'builtin connector keys must be unique');

$redactor = new ConnectorConfigurationRedactor();
$redacted = $redactor->redact([
    'base_url' => 'https://example.test',
    'password' => 'super-secret',
    'headers' => [
        'Authorization' => 'Bearer abc123',
        'X-Trace' => 'trace-safe',
    ],
    'nested' => [
        'api_key' => 'key-123',
        'cookie' => 'session=abc',
    ],
]);
assertContract($redacted['base_url'] === 'https://example.test', 'safe configuration values must be preserved');
assertContract($redacted['password'] === '[REDACTED]', 'password must be redacted');
assertContract($redacted['headers']['Authorization'] === '[REDACTED]', 'authorization header must be redacted');
assertContract($redacted['headers']['X-Trace'] === 'trace-safe', 'non-sensitive headers must survive redaction');
assertContract($redacted['nested']['api_key'] === '[REDACTED]', 'nested API key must be redacted');
assertContract($redacted['nested']['cookie'] === '[REDACTED]', 'nested cookie must be redacted');

$masker = new SensitiveValueMasker();
$masked = $masker->maskRow([
    'name' => 'Ada Lovelace',
    'email' => 'ada@example.test',
    'phone_number' => '+61 400 000 000',
    'access_token' => 'token-123',
]);
assertContract($masked['name'] === 'Ada Lovelace', 'ordinary sample fields must remain visible');
assertContract($masked['email'] === '[MASKED]', 'email samples must be masked');
assertContract($masked['phone_number'] === '[MASKED]', 'phone samples must be masked');
assertContract($masked['access_token'] === '[MASKED]', 'token samples must be masked');

$sqlGuard = new ReadOnlySqlGuard();
assertContract($sqlGuard->assertReadOnly('SELECT id, name FROM users WHERE id > 10') !== '', 'SELECT must be accepted by read-only SQL guard');
assertContract($sqlGuard->assertReadOnly('WITH recent AS (SELECT id FROM users) SELECT * FROM recent') !== '', 'read-only CTE must be accepted');
foreach ([
    'INSERT INTO users(name) VALUES (\'x\')',
    'UPDATE users SET name = \'x\'',
    'DELETE FROM users',
    'DROP TABLE users',
    'SELECT * FROM users FOR UPDATE',
    "SELECT * FROM users INTO OUTFILE '/tmp/users.csv'",
    'SELECT 1; SELECT 2',
] as $unsafeSql) {
    expectThrows(static fn () => $sqlGuard->assertReadOnly($unsafeSql), "unsafe SQL must be rejected: {$unsafeSql}");
}

$graphQlGuard = new GraphQlReadOnlyGuard();
assertContract($graphQlGuard->assertReadOnly('query Users { users { id name } }') !== '', 'GraphQL query must be accepted');
assertContract($graphQlGuard->assertReadOnly('{ users { id } }') !== '', 'GraphQL shorthand query must be accepted');
expectThrows(static fn () => $graphQlGuard->assertReadOnly('mutation DeleteUser { deleteUser(id: 1) }'), 'GraphQL mutations must be rejected');
expectThrows(static fn () => $graphQlGuard->assertReadOnly('subscription Events { events { id } }'), 'GraphQL subscriptions must be rejected');

$archiveGuard = new ArchiveSafetyGuard(maxEntries: 20, maxEntryBytes: 1024 * 1024, maxExpandedBytes: 2 * 1024 * 1024, maxCompressionRatio: 50.0);
$archiveGuard->assertEntrySafe('exports/users.csv', 1000, 500);
expectThrows(static fn () => $archiveGuard->assertEntrySafe('../outside.csv', 100, 100), 'archive traversal must be rejected');
expectThrows(static fn () => $archiveGuard->assertEntrySafe('/absolute.csv', 100, 100), 'absolute archive paths must be rejected');
expectThrows(static fn () => $archiveGuard->assertEntrySafe('bomb.csv', 100000, 10), 'suspicious compression ratio must be rejected');

$fingerprint = new SchemaFingerprint();
$before = [
    'entities' => [[
        'name' => 'users',
        'fields' => [
            ['name' => 'id', 'type' => 'integer', 'nullable' => false],
            ['name' => 'email', 'type' => 'string', 'nullable' => true],
        ],
        'samples' => [['id' => 1, 'email' => '[MASKED]']],
    ]],
];
$beforeWithDifferentSample = $before;
$beforeWithDifferentSample['entities'][0]['samples'] = [['id' => 999, 'email' => '[MASKED]']];
assertContract($fingerprint->fromDiscovery($before) === $fingerprint->fromDiscovery($beforeWithDifferentSample), 'schema fingerprint must ignore sample values');

$after = [
    'entities' => [[
        'name' => 'users',
        'fields' => [
            ['name' => 'id', 'type' => 'string', 'nullable' => false],
        ],
    ]],
];
$change = (new SchemaChangeDetector())->compare($before, $after);
assertContract($change['breaking'] === true, 'removed fields and incompatible type changes must be classified as breaking');
assertContract($change['changes'] !== [], 'breaking schema comparison must explain the changes');

$csvPath = tempnam(sys_get_temp_dir(), 'titan-migration-csv-');
if ($csvPath === false) {
    failContract('could not create CSV fixture');
}
file_put_contents($csvPath, "id,name,email,updated_at\n1,Ada,ada@example.test,2026-08-01T00:00:00Z\n2,Grace,grace@example.test,2026-08-02T00:00:00Z\n3,Linus,linus@example.test,2026-08-03T00:00:00Z\n");

$csv = new CsvConnector();
$csvDiscovery = $csv->discover(['path' => $csvPath, 'sample_limit' => 2]);
assertContract(($csvDiscovery['entities'][0]['record_count'] ?? null) === 3, 'CSV discovery must count records without loading all rows into a result array');
assertContract(($csvDiscovery['entities'][0]['samples'][0]['email'] ?? null) === '[MASKED]', 'CSV discovery samples must be masked');
assertContract(is_string($csvDiscovery['fingerprint'] ?? null) && strlen($csvDiscovery['fingerprint']) === 64, 'CSV discovery must include SHA-256 schema fingerprint');
$csvStream = $csv->stream(['configuration' => ['path' => $csvPath]]);
assertContract($csvStream instanceof Traversable, 'CSV extraction must return a lazy iterable');
$csvRows = 0;
foreach ($csvStream as $row) {
    $csvRows++;
    if ($csvRows === 2) {
        break;
    }
}
assertContract($csvRows === 2, 'CSV stream must be consumable incrementally');
unlink($csvPath);

$jsonlPath = tempnam(sys_get_temp_dir(), 'titan-migration-jsonl-');
if ($jsonlPath === false) {
    failContract('could not create JSONL fixture');
}
file_put_contents($jsonlPath, "{\"id\":1,\"name\":\"Ada\",\"email\":\"ada@example.test\"}\n{\"id\":2,\"name\":\"Grace\",\"email\":\"grace@example.test\"}\n");
$jsonl = new JsonLinesConnector();
$jsonlDiscovery = $jsonl->discover(['path' => $jsonlPath]);
assertContract(($jsonlDiscovery['entities'][0]['record_count'] ?? null) === 2, 'JSONL discovery must stream line records');
assertContract(($jsonlDiscovery['entities'][0]['samples'][0]['email'] ?? null) === '[MASKED]', 'JSONL samples must be masked');
unlink($jsonlPath);

$sqlPath = tempnam(sys_get_temp_dir(), 'titan-migration-sql-');
if ($sqlPath === false) {
    failContract('could not create SQL fixture');
}
file_put_contents($sqlPath, <<<'SQL'
CREATE TABLE `users` (
  `id` bigint NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
INSERT INTO `users` (`id`, `email`) VALUES
(1, 'ada@example.test'),
(2, 'grace@example.test');
SQL);
$sqlDump = new SqlDumpConnector();
$sqlDiscovery = $sqlDump->discover(['path' => $sqlPath]);
assertContract(($sqlDiscovery['entities'][0]['name'] ?? null) === 'users', 'SQL dump discovery must find CREATE TABLE entities');
$sqlRows = iterator_to_array($sqlDump->stream(['configuration' => ['path' => $sqlPath], 'source' => ['entity' => 'users']]), false);
assertContract(count($sqlRows) === 2, 'SQL dump extraction must stream INSERT rows without executing source SQL');
unlink($sqlPath);

$providerSource = file_get_contents($extensionRoot . '/System/MigrationServiceProvider.php');
assertContract(is_string($providerSource) && str_contains($providerSource, 'BuiltinConnectorCatalog'), 'service provider must register the builtin connector catalog');
assertContract(str_contains($providerSource, 'LegacyDavinciConnector'), 'service provider must preserve the Davinci connector registration');

$davinciSource = file_get_contents($extensionRoot . '/System/Connectors/Legacy/LegacyDavinciConnector.php');
assertContract(is_string($davinciSource) && str_contains($davinciSource, 'SqlDumpConnector'), 'Davinci connector must delegate through the native SQL dump extraction layer');
assertContract(str_contains($davinciSource, 'supportsDiscovery: true'), 'Davinci connector must advertise discovery only after native delegation exists');

$xlsxSource = file_get_contents($extensionRoot . '/System/Connectors/File/XlsxConnector.php');
assertContract(is_string($xlsxSource) && str_contains($xlsxSource, 'XlsxChunkReadFilter'), 'XLSX connector must use bounded chunk reads');
$zipSource = file_get_contents($extensionRoot . '/System/Connectors/File/ZipBundleConnector.php');
assertContract(is_string($zipSource) && str_contains($zipSource, 'ArchiveSafetyGuard'), 'ZIP bundle connector must apply archive safety validation');
$sqliteSource = file_get_contents($extensionRoot . '/System/Connectors/Database/SqliteConnector.php');
assertContract(is_string($sqliteSource) && str_contains($sqliteSource, 'mode=ro'), 'SQLite connector must open sources in read-only mode');
$mysqlSource = file_get_contents($extensionRoot . '/System/Connectors/Database/MySqlConnector.php');
assertContract(is_string($mysqlSource) && stripos($mysqlSource, 'READ ONLY') !== false, 'MySQL connector must request a read-only session/transaction');
$postgresSource = file_get_contents($extensionRoot . '/System/Connectors/Database/PostgreSqlConnector.php');
assertContract(is_string($postgresSource) && str_contains($postgresSource, 'default_transaction_read_only'), 'PostgreSQL connector must enable default_transaction_read_only');

fwrite(STDOUT, "MigrationGenericConnectorContractTest: PASS\n");
