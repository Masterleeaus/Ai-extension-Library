<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Database;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use InvalidArgumentException;
use PDO;

final class SqlServerConnector extends AbstractPdoConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'sqlserver',
            name: 'SQL Server Database',
            connectorClass: self::class,
            category: 'database',
            authenticationTypes: ['username_password', 'tls'],
            capabilities: ['connection_test', 'catalog_discovery', 'counts', 'masked_samples', 'paged_stream', 'incremental_field', 'client_enforced_read_only'],
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
            throw new InvalidArgumentException('SQL Server connector requires a database name.');
        }
        $host = trim((string) ($configuration['host'] ?? '127.0.0.1'));
        $port = max(1, (int) ($configuration['port'] ?? 1433));
        $encrypt = (bool) ($configuration['encrypt'] ?? true) ? 'yes' : 'no';
        $trust = (bool) ($configuration['trust_server_certificate'] ?? false) ? 'yes' : 'no';
        $dsn = "sqlsrv:Server={$host},{$port};Database={$database};Encrypt={$encrypt};TrustServerCertificate={$trust}";

        return new PDO($dsn, (string) ($configuration['user'] ?? ''), (string) ($configuration['password'] ?? ''));
    }

    protected function catalogEntities(PDO $pdo, array $configuration): array
    {
        $schema = trim((string) ($configuration['schema'] ?? 'dbo')) ?: 'dbo';
        $columns = $pdo->prepare(<<<'SQL'
SELECT c.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE, c.IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS c
JOIN INFORMATION_SCHEMA.TABLES t
  ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
WHERE c.TABLE_SCHEMA = :schema AND t.TABLE_TYPE = 'BASE TABLE'
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION
SQL);
        $columns->execute(['schema' => $schema]);
        $entities = [];
        while (($row = $columns->fetch(PDO::FETCH_ASSOC)) !== false) {
            $table = (string) $row['TABLE_NAME'];
            $entities[$table] ??= ['name' => $table, 'fields' => [], 'keys' => [], 'relationships' => []];
            $entities[$table]['fields'][] = [
                'name' => (string) $row['COLUMN_NAME'],
                'database_type' => (string) $row['DATA_TYPE'],
                'type' => $this->portableType((string) $row['DATA_TYPE']),
                'nullable' => strtoupper((string) $row['IS_NULLABLE']) === 'YES',
            ];
        }

        $keys = $pdo->prepare(<<<'SQL'
SELECT tc.TABLE_NAME, kcu.COLUMN_NAME
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
  ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
WHERE tc.CONSTRAINT_SCHEMA = :schema AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
ORDER BY tc.TABLE_NAME, kcu.ORDINAL_POSITION
SQL);
        $keys->execute(['schema' => $schema]);
        while (($row = $keys->fetch(PDO::FETCH_ASSOC)) !== false) {
            $table = (string) $row['TABLE_NAME'];
            if (isset($entities[$table])) {
                $entities[$table]['keys'][] = ['field' => (string) $row['COLUMN_NAME'], 'kind' => 'primary'];
            }
        }

        $relations = $pdo->prepare(<<<'SQL'
SELECT OBJECT_NAME(fkc.parent_object_id) AS table_name,
       COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS column_name,
       OBJECT_NAME(fkc.referenced_object_id) AS foreign_table_name,
       COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS foreign_column_name
FROM sys.foreign_key_columns fkc
JOIN sys.tables t ON t.object_id = fkc.parent_object_id
JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE s.name = :schema
ORDER BY table_name, fkc.constraint_column_id
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

    protected function pageSql(string $baseSql, ?string $orderField, int $limit, int $offset): string
    {
        $order = $orderField !== null ? $orderField : '(SELECT 0)';

        return $baseSql . " ORDER BY {$order} OFFSET " . max(0, $offset) . ' ROWS FETCH NEXT ' . max(1, $limit) . ' ROWS ONLY';
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '[' . str_replace(']', ']]', $identifier) . ']';
    }
}
