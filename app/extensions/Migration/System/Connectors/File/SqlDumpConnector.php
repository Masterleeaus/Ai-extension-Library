<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Discovery\SqlDumpScanner;
use InvalidArgumentException;

final class SqlDumpConnector extends AbstractFileConnector
{
    private ?SqlDumpScanner $scanner = null;

    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'sql_dump',
            name: 'Legacy SQL Dump',
            connectorClass: self::class,
            category: 'file',
            authenticationTypes: ['local_file'],
            capabilities: ['connection_test', 'ddl_discovery', 'field_profiling', 'insert_stream', 'checkpoint_offset'],
            supportsDiscovery: true,
            readOnly: true,
            streamingMode: 'generator',
        );
    }

    public function discover(array $configuration): array
    {
        $path = $this->path($configuration);
        $schemas = $this->scanner()->schemas($path);
        $sampleLimit = max(0, min(25, (int) ($configuration['sample_limit'] ?? 5)));
        $entities = [];

        foreach ($schemas as $table => $schema) {
            $schemaFields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
            $columns = array_values(array_map(static fn (array $field): string => (string) ($field['name'] ?? ''), $schemaFields));
            $profile = $this->profiler()->profile($this->scanner()->rows($path, $table, $columns), $table, $sampleLimit);
            $profile['fields'] = $this->mergeSchemaFields($schemaFields, $profile['fields'] ?? []);
            $profile['keys'] = $schema['keys'] ?? $profile['keys'];
            $profile['relationships'] = $schema['relationships'] ?? $profile['relationships'];
            $entities[] = $profile;
        }

        return $this->discoveryResult($entities, [
            'source' => ['path' => basename($path), 'format' => 'sql_dump'],
        ]);
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = $this->configurationFromPlan($entityPlan);
        $entity = $entityPlan['source']['entity'] ?? ($configuration['entity'] ?? null);
        if (! is_string($entity) || $entity === '') {
            throw new InvalidArgumentException('SQL dump extraction requires a source entity/table.');
        }
        $configuration['entity'] = $entity;
        $skip = max(0, (int) ($checkpoint['offset'] ?? 0));
        $index = 0;

        foreach ($this->rows($configuration) as $row) {
            if ($index++ < $skip) {
                continue;
            }
            yield $row;
        }
    }

    protected function rows(array $configuration): iterable
    {
        $path = $this->path($configuration);
        $entity = $configuration['entity'] ?? null;
        if (! is_string($entity) || $entity === '') {
            throw new InvalidArgumentException('SQL dump extraction requires an entity/table.');
        }

        $schema = $this->scanner()->schemas($path)[$entity] ?? null;
        $columns = [];
        if (is_array($schema)) {
            foreach (($schema['fields'] ?? []) as $field) {
                if (is_array($field) && isset($field['name'])) {
                    $columns[] = (string) $field['name'];
                }
            }
        }

        return $this->scanner()->rows($path, $entity, $columns);
    }

    /** @param array<int, array<string, mixed>> $schemaFields
     *  @param array<int, array<string, mixed>> $profileFields
     *  @return array<int, array<string, mixed>>
     */
    private function mergeSchemaFields(array $schemaFields, array $profileFields): array
    {
        $profileMap = [];
        foreach ($profileFields as $field) {
            if (isset($field['name'])) {
                $profileMap[(string) $field['name']] = $field;
            }
        }

        $merged = [];
        foreach ($schemaFields as $field) {
            $name = (string) ($field['name'] ?? '');
            $merged[] = array_merge($profileMap[$name] ?? [], $field);
            unset($profileMap[$name]);
        }

        foreach ($profileMap as $field) {
            $merged[] = $field;
        }
        usort($merged, static fn (array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        return $merged;
    }

    private function scanner(): SqlDumpScanner
    {
        return $this->scanner ??= new SqlDumpScanner();
    }
}
