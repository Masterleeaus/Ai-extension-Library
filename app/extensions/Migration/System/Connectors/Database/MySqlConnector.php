<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Database;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use InvalidArgumentException;
use PDO;

final class MySqlConnector extends AbstractPdoConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'mysql',
            name: 'MySQL Database',
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
            throw new InvalidArgumentException('MySQL connector requires a database name.');
        }
        $host = trim((string) ($configuration['host'] ?? '127.0.0.1'));
        $port = max(1, (int) ($configuration['port'] ?? 3306));
        $charset = trim((string) ($configuration['charset'] ?? 'utf8mb4'));
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        $options = [PDO::ATTR_EMULATE_PREPARES => false];
        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $options[constant('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')] = false;
        }

        return new PDO($dsn, (string) ($configuration['user'] ?? ''), (string) ($configuration['password'] ?? ''), $options);
    }

    protected function prepareReadOnly(PDO $pdo, array $configuration): void
    {
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    }

    protected function catalogEntities(PDO $pdo, array $configuration): array
    {
        $schema = trim((string) ($configuration['database'] ?? ''));
        $statement = $pdo->prepare(<<<'SQL'
SELECT c.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE, c.COLUMN_TYPE, c.IS_NULLABLE, c.COLUMN_KEY
FROM information_schema.COLUMNS c
JOIN information_schema.TABLES t
  ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
WHERE c.TABLE_SCHEMA = :schema AND t.TABLE_TYPE = 'BASE TABLE'
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION
SQL);
        $statement->execute(['schema' => $schema]);
        $entities = [];
        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            $table = (string) $row['TABLE_NAME'];
            $entities[$table] ??= [
                'name' => $table,
                'fields' => [],
                'keys' => [],
                'relationships' => [],
            ];
            $entities[$table]['fields'][] = [
                'name' => (string) $row['COLUMN_NAME'],
                'database_type' => (string) $row['COLUMN_TYPE'],
                'type' => $this->portableType((string) $row['DATA_TYPE']),
                'nullable' => strtoupper((string) $row['IS_NULLABLE']) === 'YES',
            ];
            if ((string) $row['COLUMN_KEY'] === 'PRI') {
                $entities[$table]['keys'][] = ['field' => (string) $row['COLUMN_NAME'], 'kind' => 'primary'];
            }
        }

        $relations = $pdo->prepare(<<<'SQL'
SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = :schema AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, ORDINAL_POSITION
SQL);
        $relations->execute(['schema' => $schema]);
        while (($row = $relations->fetch(PDO::FETCH_ASSOC)) !== false) {
            $table = (string) $row['TABLE_NAME'];
            if (! isset($entities[$table])) {
                continue;
            }
            $entities[$table]['relationships'][] = [
                'field' => (string) $row['COLUMN_NAME'],
                'target' => (string) $row['REFERENCED_TABLE_NAME'],
                'target_field' => (string) $row['REFERENCED_COLUMN_NAME'],
                'kind' => 'foreign_key',
            ];
        }

        return array_values($entities);
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
