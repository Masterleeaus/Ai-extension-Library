<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Database;

use App\Extensions\Migration\System\Connectors\AbstractSourceConnector;
use App\Extensions\Migration\System\Discovery\SchemaFingerprint;
use App\Extensions\Migration\System\Security\ConnectorConfigurationRedactor;
use App\Extensions\Migration\System\Security\ReadOnlySqlGuard;
use App\Extensions\Migration\System\Security\SensitiveValueMasker;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

abstract class AbstractPdoConnector extends AbstractSourceConnector
{
    protected ?ReadOnlySqlGuard $sqlGuard = null;
    protected ?SensitiveValueMasker $masker = null;

    public function __construct(
        ?ConnectorConfigurationRedactor $configurationRedactor = null,
        ?SchemaFingerprint $schemaFingerprint = null,
    ) {
        parent::__construct($configurationRedactor, $schemaFingerprint);
    }

    public function testConnection(array $configuration): array
    {
        try {
            $pdo = $this->connect($configuration);
            $this->prepareReadOnly($pdo, $configuration);
            $pdo->query($this->sql()->assertReadOnly('SELECT 1'))?->fetchColumn();

            return [
                'successful' => true,
                'connector' => $this->definition()->key,
                'read_only' => true,
                'configuration' => $this->redactConfiguration($configuration),
            ];
        } catch (\Throwable $exception) {
            return [
                'successful' => false,
                'connector' => $this->definition()->key,
                'read_only' => true,
                'message' => $exception->getMessage(),
                'configuration' => $this->redactConfiguration($configuration),
            ];
        }
    }

    public function discover(array $configuration): array
    {
        $pdo = $this->connect($configuration);
        $this->prepareReadOnly($pdo, $configuration);
        $entities = $this->catalogEntities($pdo, $configuration);
        $sampleLimit = max(0, min(25, (int) ($configuration['sample_limit'] ?? 5)));

        foreach ($entities as &$entity) {
            if (! is_array($entity) || ! isset($entity['name'])) {
                continue;
            }
            $table = (string) $entity['name'];
            $entity['record_count'] = $this->countRows($pdo, $table);
            $entity['samples'] = [];
            if ($sampleLimit > 0) {
                foreach ($this->pageRows($pdo, $table, [], null, null, $sampleLimit, 0) as $row) {
                    $entity['samples'][] = $this->masker()->maskRow($row);
                }
            }
            $fieldNames = array_values(array_map(
                static fn (array $field): string => (string) ($field['name'] ?? ''),
                is_array($entity['fields'] ?? null) ? $entity['fields'] : [],
            ));
            $entity['incremental_fields'] ??= $this->incrementalHints($fieldNames);
            $entity['attachment_fields'] ??= $this->attachmentHints($fieldNames);
        }
        unset($entity);

        return $this->discoveryResult($entities, [
            'database' => $this->redactConfiguration([
                'database' => $configuration['database'] ?? null,
                'schema' => $configuration['schema'] ?? null,
                'host' => $configuration['host'] ?? null,
            ]),
        ]);
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = $this->configurationFromPlan($entityPlan);
        $table = $entityPlan['source']['table'] ?? $entityPlan['source']['entity'] ?? $configuration['table'] ?? null;
        if (! is_string($table) || $table === '') {
            throw new InvalidArgumentException('Database extraction requires a source table/entity.');
        }

        $fields = $entityPlan['source']['fields'] ?? [];
        if (! is_array($fields)) {
            throw new InvalidArgumentException('Database source fields must be an array.');
        }
        $fields = array_values(array_filter(array_map('strval', $fields), static fn (string $field): bool => $field !== ''));

        $incrementalField = $entityPlan['source']['incremental_field'] ?? null;
        $checkpointValue = $checkpoint['value'] ?? null;
        if ($incrementalField !== null && ! is_string($incrementalField)) {
            throw new InvalidArgumentException('Incremental field must be a string.');
        }

        $pageSize = max(50, min(5000, (int) ($configuration['page_size'] ?? 500)));
        $pdo = $this->connect($configuration);
        $this->prepareReadOnly($pdo, $configuration);
        $offset = 0;

        do {
            $rows = 0;
            foreach ($this->pageRows($pdo, $table, $fields, $incrementalField, $checkpointValue, $pageSize, $offset) as $row) {
                $rows++;
                yield $row;
            }
            $offset += $rows;
        } while ($rows === $pageSize);
    }

    public function readIncremental(array $entityPlan, array $checkpoint): iterable
    {
        if (! isset($entityPlan['source']['incremental_field'])) {
            throw new InvalidArgumentException('Incremental extraction requires source.incremental_field.');
        }

        return $this->stream($entityPlan, $checkpoint);
    }

    /** @return array<int, array<string, mixed>> */
    abstract protected function catalogEntities(PDO $pdo, array $configuration): array;

    abstract protected function createPdo(array $configuration): PDO;

    abstract protected function quoteIdentifier(string $identifier): string;

    protected function prepareReadOnly(PDO $pdo, array $configuration): void
    {
    }

    protected function connect(array $configuration): PDO
    {
        try {
            $pdo = $this->createPdo($configuration);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $pdo;
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to migration source database: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /** @return iterable<int, array<string, mixed>> */
    protected function pageRows(
        PDO $pdo,
        string $table,
        array $fields,
        ?string $incrementalField,
        mixed $checkpointValue,
        int $limit,
        int $offset,
    ): iterable {
        $tableSql = $this->quoteQualifiedIdentifier($table);
        $fieldSql = $fields === []
            ? '*'
            : implode(', ', array_map(fn (string $field): string => $this->quoteQualifiedIdentifier($field), $fields));
        $base = "SELECT {$fieldSql} FROM {$tableSql}";
        $params = [];
        $orderField = null;

        if ($incrementalField !== null && $incrementalField !== '') {
            $orderField = $this->quoteQualifiedIdentifier($incrementalField);
            if ($checkpointValue !== null) {
                $base .= " WHERE {$orderField} > :migration_checkpoint";
                $params['migration_checkpoint'] = $checkpointValue;
            }
        }

        $base = $this->sql()->assertReadOnly($base);
        $query = $this->pageSql($base, $orderField, $limit, $offset);
        $statement = $pdo->prepare($query);
        $statement->execute($params);
        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            yield $row;
        }
        $statement->closeCursor();
    }

    protected function pageSql(string $baseSql, ?string $orderField, int $limit, int $offset): string
    {
        $order = $orderField !== null ? " ORDER BY {$orderField}" : '';

        return $baseSql . $order . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
    }

    protected function countRows(PDO $pdo, string $table): int
    {
        $sql = $this->sql()->assertReadOnly('SELECT COUNT(*) AS aggregate FROM ' . $this->quoteQualifiedIdentifier($table));
        $statement = $pdo->query($sql);

        return (int) ($statement?->fetchColumn() ?: 0);
    }

    protected function quoteQualifiedIdentifier(string $identifier): string
    {
        $parts = explode('.', $identifier);
        if ($parts === []) {
            throw new InvalidArgumentException('Database identifier cannot be empty.');
        }

        return implode('.', array_map(function (string $part): string {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_$]*$/', $part) !== 1) {
                throw new InvalidArgumentException("Unsafe database identifier: {$part}");
            }

            return $this->quoteIdentifier($part);
        }, $parts));
    }

    /** @param array<int, string> $fields
     *  @return array<int, string>
     */
    protected function incrementalHints(array $fields): array
    {
        return array_values(array_filter(
            ['updated_at', 'modified_at', 'updated', 'modified', 'created_at', 'timestamp', 'version'],
            static fn (string $field): bool => in_array($field, $fields, true),
        ));
    }

    /** @param array<int, string> $fields
     *  @return array<int, string>
     */
    protected function attachmentHints(array $fields): array
    {
        return array_values(array_filter($fields, static fn (string $field): bool => preg_match('/(?:file|attachment|image|photo|document|media|avatar|url|path)/i', $field) === 1));
    }

    protected function sql(): ReadOnlySqlGuard
    {
        return $this->sqlGuard ??= new ReadOnlySqlGuard();
    }

    protected function masker(): SensitiveValueMasker
    {
        return $this->masker ??= new SensitiveValueMasker();
    }

    protected function portableType(string $databaseType): string
    {
        return match (true) {
            preg_match('/(?:bool|bit|tinyint\(1\))/i', $databaseType) === 1 => 'boolean',
            preg_match('/(?:int|serial)/i', $databaseType) === 1 => 'integer',
            preg_match('/(?:decimal|numeric|float|double|real|money)/i', $databaseType) === 1 => 'number',
            preg_match('/(?:date|time|timestamp)/i', $databaseType) === 1 => 'datetime',
            preg_match('/(?:json)/i', $databaseType) === 1 => 'json',
            default => 'string',
        };
    }
}
