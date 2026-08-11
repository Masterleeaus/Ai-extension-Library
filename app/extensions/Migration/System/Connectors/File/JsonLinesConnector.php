<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use JsonException;
use RuntimeException;

final class JsonLinesConnector extends AbstractFileConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'jsonl',
            name: 'JSON Lines File',
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
        $handle = fopen($this->path($configuration), 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open JSONL source.');
        }

        try {
            $lineNumber = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                try {
                    $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new RuntimeException("Invalid JSON on line {$lineNumber}: {$exception->getMessage()}", 0, $exception);
                }

                if (! is_array($decoded)) {
                    yield ['value' => $decoded];
                    continue;
                }

                yield array_is_list($decoded) ? ['value' => $decoded] : $decoded;
            }
        } finally {
            fclose($handle);
        }
    }
}
