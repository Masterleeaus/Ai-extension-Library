<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Database;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use InvalidArgumentException;
use PDO;

final class PostgreSqlConnector extends AbstractPdoConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'postgresql',
            name: 'PostgreSQL Database',
            connectorClass: self::class,
            category: 'database',
            authenticationTypes: ['username_password', 'tls'],
            capabilities: ['connection_test', 'catalog_discovery', 'counts', 'masked_samples', 'paged_stream', 'incremental_field'],
            supportsDiscovery: true,
            supportsIncrementalSync: true,
            readOnly: true,
            streamingMode: 'paged',
        );
    }

    protected function createPdo(array $configuration): PDO
    {
        $database = trim((string) ($configuration['database'] ?? ''));
        if ($database === '') {
            throw new InvalidArgumentException('PostgreSQL connector requires a database name.');
        }
        $host = trim((string) ($configuration['host'] ?? '127.0.0.1'));
        $port = max(1, (int) ($configuration['port'] ?? 5432));
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

        return new PDO($dsn, (string) ($configuration['user'] ?? ''), (string) ($configuration['password'] ?? ''));
    }

    protected function prepareReadOnly(PDO $pdo, array $configuration): void
    {
        $pdo->exec('SET default_transaction_read_only = on');
    }

    protected function catalogEntities(PDO $pdo, array $configuration): array
    {
        $schema = trim((string) ($configuration['schema'] ?? 'public')) ?: 'public';
        $columns = $pdo->prepare(<<<'SQL'
SELECT c.table_name, c.column_name, c.data_type, c.udt_name, c.is_nullable
FROM information_schema.columns c
JOIN information_schema.tables t
  ON t.table_schema = c.table_schema AND t.table_name = c.table_name
WHERE c.table_schema = :schema AND t.table_type = 'BASE TABLE'
ORDER BY c.table_name, c.ordinal_position
SQL);
        $columns->execute(['schema' => $schema]);
        $entities = [];
        while (($row = $columns->fetch(PDO::FETCH_ASSOC)) !== false) {
            $table = (string) $row['table_name'];
            $entities[$table] ??= ['name' => $table, 'fields' => [], 'keys' => [], 'relationships' => []];
            $databaseType = (string) ($row['data_type'] ?: $row['udt_name']);
            $entities[$table]['fields'][] = [
                'name' => (string) $row['column_name'],
                'database_type' => $databaseType,
                'type' => $this->portableType($databaseType),
                'nullable' => strtoupper((string) $row['is_nullable']) === 'YES',
            ];
        }

        $keys = $pdo->prepare(<<<'SQL'
SELECT tc.table_name, kcu.column_name
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu
  ON tc.constraint_name = kcu.constraint_name AND tc.constraint_schema = kcu.constraint_schema
WHERE tc.constraint_schema = :schema AND tc.constraint_type = 'PRIMARY KEY'
ORDER BY tc.table_name, kcu.ordinal_position
SQL);
        $keys->execute(['schema' => $schema]);
        while (($row = $keys->fetch(PDO::FETCH_ASSOC)) !== false) {
            $table = (string) $row['table_name'];
            if (isset($entities[$table])) {
                $entities[$table]['keys'][] = ['field' => (string) $row['column_name'], 'kind' => 'primary'];
            }
        }

        $relations = $pdo->prepare(<<<'SQL'
SELECT tc.table_name, kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu
  ON tc.constraint_name = kcu.constraint_name AND tc.constraint_schema = kcu.constraint_schema
JOIN information_schema.constraint_column_usage ccu
  ON ccu.constraint_name = tc.constraint_name AND ccu.constraint_schema = tc.constraint_schema
WHERE tc.constraint_schema = :schema AND tc.constraint_type = 'FOREIGN KEY'
ORDER BY tc.table_name, kcu.ordinal_position
SQL);
        $relations->execute(['schema' => $schema]);
        while (($row = $relations->fetch(PDO::FETCH_ASSOC)) !== false) {
            $table = (string) $row['table_name'];
            if (isset($entities[$table])) {
                $entities[$table]['relationships'][] = [
                    'field' => (string) $row['column_name'],
                    'target' => (string) $row['foreign_table_name'],
                    'target_field' => (string) $row['foreign_column_name'],
                    'kind' => 'foreign_key',
                ];
            }
        }

        return array_values($entities);
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
