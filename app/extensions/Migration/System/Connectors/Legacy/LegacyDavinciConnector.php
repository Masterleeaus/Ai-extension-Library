<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Legacy;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Connectors\Contracts\MigrationSourceConnectorInterface;
use App\Extensions\Migration\System\Connectors\File\SqlDumpConnector;
use App\Extensions\Migration\System\Drivers\DavinciDriver;
use InvalidArgumentException;
use LogicException;

final class LegacyDavinciConnector implements MigrationSourceConnectorInterface
{
    private SqlDumpConnector $sqlDump;

    public function __construct(
        private readonly DavinciDriver $driver,
        ?SqlDumpConnector $sqlDump = null,
    ) {
        $this->sqlDump = $sqlDump ?? new SqlDumpConnector();
    }

    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'davinci',
            name: 'Davinci AI',
            connectorClass: self::class,
            category: 'native',
            version: '2.0-native-adapter',
            authenticationTypes: ['sql_file', 'env_file'],
            capabilities: array_values(array_unique(array_merge(
                array_keys($this->driver->supportedCapabilities()),
                ['schema_discovery', 'field_profiling', 'streamed_extraction'],
            ))),
            supportsDiscovery: true,
            supportsIncrementalSync: false,
            supportsAttachments: false,
            readOnly: true,
            streamingMode: 'generator',
        );
    }

    public function testConnection(array $configuration): array
    {
        $delegated = $this->sqlDump->testConnection($this->sqlConfiguration($configuration));
        $delegated['connector'] = 'davinci';
        $delegated['message'] = ($delegated['successful'] ?? false)
            ? 'Davinci SQL export is readable through the native extraction layer.'
            : 'A readable Davinci SQL export is required.';

        return $delegated;
    }

    public function discover(array $configuration): array
    {
        $discovery = $this->sqlDump->discover($this->sqlConfiguration($configuration));
        $discovery['connector'] = 'davinci';
        $discovery['source_format'] = 'davinci_sql_export';

        return $discovery;
    }

    public function estimate(array $project): array
    {
        $configuration = is_array($project['configuration'] ?? null)
            ? $project['configuration']
            : $project;

        $estimate = $this->sqlDump->estimate([
            'configuration' => $this->sqlConfiguration($configuration),
        ]);
        $estimate['connector'] = 'davinci';

        return $estimate;
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = is_array($entityPlan['configuration'] ?? null)
            ? $entityPlan['configuration']
            : (is_array($entityPlan['source']['configuration'] ?? null) ? $entityPlan['source']['configuration'] : []);
        $source = is_array($entityPlan['source'] ?? null) ? $entityPlan['source'] : [];

        return $this->sqlDump->stream([
            'configuration' => $this->sqlConfiguration($configuration),
            'source' => $source,
        ], $checkpoint);
    }

    public function supportsIncrementalSync(): bool
    {
        return false;
    }

    public function readIncremental(array $entityPlan, array $checkpoint): iterable
    {
        throw new LogicException('Davinci SQL exports do not provide a trustworthy incremental checkpoint contract.');
    }

    public function fetchAttachment(array $attachment): mixed
    {
        throw new LogicException('Davinci attachment retrieval is not exposed by the preserved legacy export format.');
    }

    public function redactConfiguration(array $configuration): array
    {
        return $this->sqlDump->redactConfiguration($configuration);
    }

    /** @return array<string, mixed> */
    private function sqlConfiguration(array $configuration): array
    {
        $path = $configuration['sql_file'] ?? $configuration['path'] ?? null;
        if (! is_string($path) || trim($path) === '') {
            throw new InvalidArgumentException('Davinci connector requires sql_file.');
        }

        return array_merge($configuration, ['path' => $path]);
    }
}
