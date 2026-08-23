<?php

declare(strict_types=1);

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Connectors\ConnectorRegistry;
use App\Extensions\Migration\System\Connectors\Contracts\MigrationSourceConnectorInterface;
use App\Extensions\Migration\System\Connectors\Exceptions\DuplicateConnectorException;
use App\Extensions\Migration\System\Connectors\Exceptions\UnknownConnectorException;

$extensionRoot = dirname(__DIR__);

require_once $extensionRoot . '/System/Connectors/ConnectorDefinition.php';
require_once $extensionRoot . '/System/Connectors/Contracts/MigrationSourceConnectorInterface.php';
require_once $extensionRoot . '/System/Connectors/Exceptions/DuplicateConnectorException.php';
require_once $extensionRoot . '/System/Connectors/Exceptions/UnknownConnectorException.php';
require_once $extensionRoot . '/System/Connectors/ConnectorRegistry.php';

final class TestConnector implements MigrationSourceConnectorInterface
{
    public function __construct(private readonly ConnectorDefinition $definition)
    {
    }

    public function definition(): ConnectorDefinition { return $this->definition; }
    public function testConnection(array $configuration): array { return ['successful' => true]; }
    public function discover(array $configuration): array { return []; }
    public function estimate(array $project): array { return []; }
    public function stream(array $entityPlan, ?array $checkpoint = null): iterable { return []; }
    public function supportsIncrementalSync(): bool { return false; }
    public function readIncremental(array $entityPlan, array $checkpoint): iterable { return []; }
    public function fetchAttachment(array $attachment): mixed { return null; }
    public function redactConfiguration(array $configuration): array { return []; }
}

function expectTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$registry = new ConnectorRegistry();
$csv = new TestConnector(new ConnectorDefinition('csv', 'CSV', TestConnector::class, 'file'));
$davinci = new TestConnector(new ConnectorDefinition('davinci', 'Davinci AI', TestConnector::class, 'native'));
$registry->register($csv);
$registry->register($davinci);

expectTrue($registry->has('csv'), 'Registered connector should be discoverable.');
expectTrue($registry->get('davinci') === $davinci, 'Registry should return the registered connector instance.');
expectTrue(array_keys($registry->all()) === ['csv', 'davinci'], 'Registry should list connectors in deterministic key order.');
expectTrue($registry->definitions()['csv']->toArray()['category'] === 'file', 'Definitions should expose connector metadata.');

try {
    new ConnectorDefinition('Invalid Key', 'Invalid', TestConnector::class, 'file');
    throw new RuntimeException('Invalid connector keys should fail validation.');
} catch (InvalidArgumentException) {
}

try {
    $registry->register($csv);
    throw new RuntimeException('Duplicate connector registration should fail.');
} catch (DuplicateConnectorException) {
}

try {
    $registry->get('missing');
    throw new RuntimeException('Unknown connector lookup should fail.');
} catch (UnknownConnectorException) {
}

echo "ConnectorRegistryTest: PASS\n";
