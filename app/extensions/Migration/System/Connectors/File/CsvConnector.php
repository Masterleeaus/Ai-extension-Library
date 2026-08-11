<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use Generator;
use RuntimeException;

final class CsvConnector extends AbstractFileConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'csv',
            name: 'CSV File',
            connectorClass: self::class,
            category: 'file',
            authenticationTypes: ['local_file'],
            capabilities: ['connection_test', 'schema_discovery', 'field_profiling', 'stream', 'checkpoint_offset'],
            supportsDiscovery: true,
            readOnly: true,
            streamingMode: 'generator',
        );
    }

    protected function rows(array $configuration): iterable
    {
        $path = $this->path($configuration);
        $delimiter = (string) ($configuration['delimiter'] ?? ',');
        if (strlen($delimiter) !== 1) {
            throw new RuntimeException('CSV delimiter must be one character.');
        }

        $enclosure = (string) ($configuration['enclosure'] ?? '"');
        $escape = (string) ($configuration['escape'] ?? '\\');
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV source.');
        }

        try {
            $header = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
            if (! is_array($header) || $header === []) {
                return;
            }

            $header = $this->normaliseHeader($header);
            while (($values = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }

                if (count($values) < count($header)) {
                    $values = array_pad($values, count($header), null);
                } elseif (count($values) > count($header)) {
                    $values = array_slice($values, 0, count($header));
                }

                yield array_combine($header, $values) ?: [];
            }
        } finally {
            fclose($handle);
        }
    }

    /** @param array<int, string|null> $header
     *  @return array<int, string>
     */
    private function normaliseHeader(array $header): array
    {
        $result = [];
        $seen = [];

        foreach ($header as $index => $name) {
            $name = trim((string) $name);
            if ($index === 0) {
                $name = preg_replace('/^\xEF\xBB\xBF/', '', $name) ?? $name;
            }
            $name = $name !== '' ? $name : 'column_' . ($index + 1);
            $base = $name;
            $suffix = 2;
            while (isset($seen[$name])) {
                $name = $base . '_' . $suffix++;
            }
            $seen[$name] = true;
            $result[] = $name;
        }

        return $result;
    }
}
