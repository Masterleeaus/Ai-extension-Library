<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Discovery\StreamingJsonArrayReader;

final class JsonConnector extends AbstractFileConnector
{
    private ?StreamingJsonArrayReader $reader = null;

    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'json',
            name: 'JSON Array File',
            connectorClass: self::class,
            category: 'file',
            authenticationTypes: ['local_file'],
            capabilities: ['connection_test', 'schema_discovery', 'field_profiling', 'stream_top_level_array', 'checkpoint_offset'],
            supportsDiscovery: true,
            readOnly: true,
            streamingMode: 'generator',
        );
    }

    protected function rows(array $configuration): iterable
    {
        $this->reader ??= new StreamingJsonArrayReader();

        return $this->reader->rows(
            $this->path($configuration),
            max(1024, (int) ($configuration['max_item_bytes'] ?? 8388608)),
        );
    }
}
