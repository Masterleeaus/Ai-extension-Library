<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Database;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use InvalidArgumentException;
use PDO;

final class SqliteConnector extends AbstractPdoConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'sqlite',
            name: 'SQLite Database',
            connectorClass: self::class,
            category: 'database',
            authenticationTypes: ['local_file'],
            capabilities: ['connection_test', 'catalog_discovery', 'counts', 'masked_samples', 'paged_stream', 'incremental_field', 'uri_read_only'],
            supportsDiscovery: true,
            supportsIncrementalSync: true,
            readOnly: true,
            streamingMode: 'paged',
        );
    }

    protected function createPdo(array $configuration): PDO
    {
        $path = $configuration['path'] ?? $configuration['database'] ?? null;
        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('SQLite connector requires a readable database file.');
        }
        $resolved = realpath($path);
        if ($resolved === false) {
            throw new InvalidArgumentException('Unable to resolve SQLite database path.');
        }

        $dsn = 'sqlite:file:' . $resolved . '?mode=ro';

        return new PDO($dsn);
    }

    protected function prepareReadOnly(PDO $pdo, array $configuration): void
    {
        $pdo->exec('PRAGMA query_only = ON');
    }

    protected function catalogEntities(PDO $pdo, array $configuration): array
    {
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $entities = [];
        while (($table = $tables?->fetchColumn()) !== false && $table !== null) {
            $table = (string) $table;
            $quoted = $this->quoteQualifiedIdentifier($table);
            $entity = ['name' => $table, 'fields' => [], 'keys' => [], 'relationships' => []];

            $columns = $pdo->query("PRAGMA table_info({$quoted})");
            while (($row = $columns?->fetch(PDO::FETCH_ASSOC)) !== false) {
                $databaseType = (string) ($row['type'] ?? 'TEXT');
                $entity['fields'][] = [
                    'name' => (string) $row['name'],
                    'database_type' => $databaseType,
                    'type' => $this->portableType($databaseType),
                    'nullable' => ((int) ($row['notnull'] ?? 0)) === 0,
                ];
                if ((int) ($row['pk'] ?? 0) > 0) {
                    $entity['keys'][] = ['field' => (string) $row['name'], 'kind' => 'primary'];
                }
            }

            $relations = $pdo->query("PRAGMA foreign_key_list({$quoted})");
            while (($row = $relations?->fetch(PDO::FETCH_ASSOC)) !== false) {
                $entity['relationships'][] = [
                    'field' => (string) ($row['from'] ?? ''),
                    'target' => (string) ($row['table'] ?? ''),
                    'target_field' => (string) ($row['to'] ?? 'id'),
                    'kind' => 'foreign_key',
                ];
            }

            $entities[] = $entity;
        }

        return $entities;
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
