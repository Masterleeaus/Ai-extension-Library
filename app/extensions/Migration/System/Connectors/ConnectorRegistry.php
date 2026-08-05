<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors;

use App\Extensions\Migration\System\Connectors\Contracts\MigrationSourceConnectorInterface;
use App\Extensions\Migration\System\Connectors\Exceptions\DuplicateConnectorException;
use App\Extensions\Migration\System\Connectors\Exceptions\UnknownConnectorException;

final class ConnectorRegistry
{
    /** @var array<string, MigrationSourceConnectorInterface> */
    private array $connectors = [];

    public function register(MigrationSourceConnectorInterface $connector): void
    {
        $key = $connector->definition()->key;

        if ($this->has($key)) {
            throw DuplicateConnectorException::forKey($key);
        }

        $this->connectors[$key] = $connector;
        ksort($this->connectors);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->connectors);
    }

    public function get(string $key): MigrationSourceConnectorInterface
    {
        return $this->connectors[$key] ?? throw UnknownConnectorException::forKey($key);
    }

    /** @return array<string, MigrationSourceConnectorInterface> */
    public function all(): array
    {
        return $this->connectors;
    }

    /** @return array<string, ConnectorDefinition> */
    public function definitions(): array
    {
        return array_map(
            static fn (MigrationSourceConnectorInterface $connector): ConnectorDefinition => $connector->definition(),
            $this->connectors,
        );
    }
}
