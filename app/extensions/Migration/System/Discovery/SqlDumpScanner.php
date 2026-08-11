<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Discovery;

use App\Extensions\Migration\System\Utils\SqlValueParser;
use RuntimeException;

final class SqlDumpScanner
{
    /** @return array<string, array<string, mixed>> */
    public function schemas(string $path, int $maxDefinitionBytes = 4194304): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open SQL dump.');
        }

        $schemas = [];
        $table = null;
        $buffer = '';

        try {
            while (($line = fgets($handle)) !== false) {
                if ($table === null && preg_match('/^\s*CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+[`"]?([A-Za-z0-9_.-]+)[`"]?\s*\(/i', $line, $match) === 1) {
                    $table = $match[1];
                    $buffer = $line;
                    continue;
                }

                if ($table === null) {
                    continue;
                }

                $buffer .= $line;
                if (strlen($buffer) > $maxDefinitionBytes) {
                    throw new RuntimeException("CREATE TABLE definition for {$table} exceeds the configured safety bound.");
                }

                if (preg_match('/\)\s*(?:ENGINE\s*=|;)/i', $line) === 1) {
                    $schemas[$table] = $this->parseCreateTable($table, $buffer);
                    $table = null;
                    $buffer = '';
                }
            }
        } finally {
            fclose($handle);
        }

        return $schemas;
    }

    /** @param array<int, string> $defaultColumns
     *  @return iterable<int, array<string, mixed>>
     */
    public function rows(string $path, string $targetTable, array $defaultColumns = []): iterable
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open SQL dump.');
        }

        $active = false;
        $columns = $defaultColumns;
        $tuple = '';
        $depth = 0;
        $inString = false;
        $escaped = false;

        try {
            while (($line = fgets($handle)) !== false) {
                $fragment = $line;

                if (! $active) {
                    if (preg_match('/^\s*INSERT\s+INTO\s+[`"]?([A-Za-z0-9_.-]+)[`"]?\s*(?:\((.*?)\))?\s+VALUES\s*(.*)$/is', $line, $match) !== 1) {
                        continue;
                    }

                    if ((string) $match[1] !== $targetTable) {
                        continue;
                    }

                    $active = true;
                    if (isset($match[2]) && trim((string) $match[2]) !== '') {
                        $columns = array_values(array_map(
                            static fn (string $column): string => trim($column, " \t\n\r\0\x0B`\""),
                            str_getcsv((string) $match[2], ',', '`', '\\'),
                        ));
                    }
                    $fragment = (string) ($match[3] ?? '');
                }

                $length = strlen($fragment);
                for ($i = 0; $i < $length; $i++) {
                    $char = $fragment[$i];

                    if ($inString) {
                        $tuple .= $char;
                        if ($escaped) {
                            $escaped = false;
                            continue;
                        }
                        if ($char === '\\') {
                            $escaped = true;
                            continue;
                        }
                        if ($char === "'") {
                            if ($i + 1 < $length && $fragment[$i + 1] === "'") {
                                $tuple .= "'";
                                $i++;
                                continue;
                            }
                            $inString = false;
                        }
                        continue;
                    }

                    if ($char === "'") {
                        $inString = true;
                        $tuple .= $char;
                        continue;
                    }

                    if ($char === '(') {
                        if ($depth > 0) {
                            $tuple .= $char;
                        }
                        $depth++;
                        continue;
                    }

                    if ($char === ')' && $depth > 0) {
                        $depth--;
                        if ($depth === 0) {
                            $values = SqlValueParser::parseRow($tuple);
                            $tuple = '';
                            yield $this->combine($columns, $values);
                        } else {
                            $tuple .= $char;
                        }
                        continue;
                    }

                    if ($char === ';' && $depth === 0) {
                        $active = false;
                        $columns = $defaultColumns;
                        $tuple = '';
                        $inString = false;
                        $escaped = false;
                        continue;
                    }

                    if ($depth > 0) {
                        $tuple .= $char;
                        if (strlen($tuple) > 8388608) {
                            throw new RuntimeException('Single SQL dump row exceeds the configured per-record memory bound.');
                        }
                    }
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return array<string, mixed> */
    private function parseCreateTable(string $table, string $sql): array
    {
        $fields = [];
        $keys = [];
        $relationships = [];

        foreach (preg_split('/\r?\n/', $sql) ?: [] as $line) {
            $trimmed = trim($line, " \t\r\n,");
            if (preg_match('/^[`"]([^`"]+)[`"]\s+([A-Za-z]+(?:\([^)]*\))?)(.*)$/i', $trimmed, $match) === 1) {
                $databaseType = strtolower($match[2]);
                $fields[] = [
                    'name' => $match[1],
                    'database_type' => $databaseType,
                    'type' => $this->portableType($databaseType),
                    'nullable' => stripos($match[3], 'NOT NULL') === false,
                ];
                continue;
            }

            if (preg_match('/PRIMARY\s+KEY\s*\(([^)]+)\)/i', $trimmed, $match) === 1) {
                foreach ($this->identifierList($match[1]) as $field) {
                    $keys[] = ['field' => $field, 'kind' => 'primary'];
                }
            }

            if (preg_match('/FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+[`"]?([^`"\s(]+)[`"]?\s*\(([^)]+)\)/i', $trimmed, $match) === 1) {
                $local = $this->identifierList($match[1]);
                $remote = $this->identifierList($match[3]);
                foreach ($local as $index => $field) {
                    $relationships[] = [
                        'field' => $field,
                        'target' => $match[2],
                        'target_field' => $remote[$index] ?? $remote[0] ?? 'id',
                        'kind' => 'foreign_key',
                    ];
                }
            }
        }

        return [
            'name' => $table,
            'fields' => $fields,
            'keys' => $keys,
            'relationships' => $relationships,
        ];
    }

    /** @param array<int, string> $columns
     *  @param array<int, mixed> $values
     *  @return array<string, mixed>
     */
    private function combine(array $columns, array $values): array
    {
        if ($columns === []) {
            $columns = array_map(static fn (int $index): string => 'column_' . ($index + 1), array_keys($values));
        }
        if (count($values) < count($columns)) {
            $values = array_pad($values, count($columns), null);
        }
        if (count($values) > count($columns)) {
            $values = array_slice($values, 0, count($columns));
        }

        return array_combine($columns, $values) ?: [];
    }

    /** @return array<int, string> */
    private function identifierList(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $field): string => trim($field, " \t`\""),
            explode(',', $value),
        ), static fn (string $field): bool => $field !== ''));
    }

    private function portableType(string $databaseType): string
    {
        return match (true) {
            preg_match('/(?:tinyint\(1\)|boolean|bool)/i', $databaseType) === 1 => 'boolean',
            preg_match('/(?:int|serial|bigint|smallint)/i', $databaseType) === 1 => 'integer',
            preg_match('/(?:decimal|numeric|float|double|real)/i', $databaseType) === 1 => 'number',
            preg_match('/(?:date|time|timestamp|datetime)/i', $databaseType) === 1 => 'datetime',
            preg_match('/(?:json)/i', $databaseType) === 1 => 'json',
            default => 'string',
        };
    }
}
