<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors;

use InvalidArgumentException;

final readonly class ConnectorDefinition
{
    /**
     * @param array<int, string> $authenticationTypes
     * @param array<int, string> $capabilities
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $connectorClass,
        public string $category,
        public string $version = '1.0.0',
        public array $authenticationTypes = [],
        public array $capabilities = [],
        public bool $supportsDiscovery = false,
        public bool $supportsIncrementalSync = false,
        public bool $supportsAttachments = false,
        public bool $readOnly = true,
        public string $streamingMode = 'generator',
    ) {
        if (! preg_match('/^[a-z0-9][a-z0-9._-]*$/', $this->key)) {
            throw new InvalidArgumentException('Connector keys must contain only lowercase letters, numbers, dots, underscores and hyphens.');
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Connector name cannot be empty.');
        }

        if (trim($this->connectorClass) === '') {
            throw new InvalidArgumentException('Connector class cannot be empty.');
        }

        if (trim($this->category) === '') {
            throw new InvalidArgumentException('Connector category cannot be empty.');
        }

        if (! in_array($this->streamingMode, ['generator', 'chunked', 'cursor', 'paged', 'bundle'], true)) {
            throw new InvalidArgumentException('Connector streaming mode must be generator, chunked, cursor, paged or bundle.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'connector_class' => $this->connectorClass,
            'category' => $this->category,
            'version' => $this->version,
            'authentication_types' => $this->authenticationTypes,
            'capabilities' => $this->capabilities,
            'supports_discovery' => $this->supportsDiscovery,
            'supports_incremental_sync' => $this->supportsIncrementalSync,
            'supports_attachments' => $this->supportsAttachments,
            'read_only' => $this->readOnly,
            'streaming_mode' => $this->streamingMode,
        ];
    }
}
