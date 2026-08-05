<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Legacy;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Connectors\Contracts\MigrationSourceConnectorInterface;
use App\Extensions\Migration\System\Drivers\DavinciDriver;
use LogicException;

final class LegacyDavinciConnector implements MigrationSourceConnectorInterface
{
    public function __construct(private readonly DavinciDriver $driver)
    {
    }

    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'davinci',
            name: 'Davinci AI',
            connectorClass: self::class,
            category: 'native',
            version: '1.4-legacy',
            authenticationTypes: ['sql_file', 'env_file'],
            capabilities: array_keys($this->driver->supportedCapabilities()),
            supportsDiscovery: false,
            supportsIncrementalSync: false,
            supportsAttachments: false,
        );
    }

    public function testConnection(array $configuration): array
    {
        $sqlFile = $configuration['sql_file'] ?? null;
        $readable = is_string($sqlFile) && is_file($sqlFile) && is_readable($sqlFile);

        return [
            'successful' => $readable,
            'connector' => 'davinci',
            'message' => $readable
                ? 'Davinci SQL export is readable.'
                : 'A readable Davinci SQL export is required.',
        ];
    }

    public function discover(array $configuration): array
    {
        throw $this->notYetAvailable('schema discovery');
    }

    public function estimate(array $project): array
    {
        throw $this->notYetAvailable('migration estimation');
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        throw $this->notYetAvailable('streamed extraction');
    }

    public function supportsIncrementalSync(): bool
    {
        return false;
    }

    public function readIncremental(array $entityPlan, array $checkpoint): iterable
    {
        throw $this->notYetAvailable('incremental extraction');
    }

    public function fetchAttachment(array $attachment): mixed
    {
        throw $this->notYetAvailable('attachment retrieval');
    }

    public function redactConfiguration(array $configuration): array
    {
        $redacted = [];

        foreach ($configuration as $key => $value) {
            $normalisedKey = strtolower((string) $key);

            if (preg_match('/(secret|password|token|api[_-]?key|client[_-]?id)/', $normalisedKey)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            if (is_string($value) && str_ends_with($normalisedKey, '_file')) {
                $redacted[$key] = basename($value);
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    private function notYetAvailable(string $capability): LogicException
    {
        return new LogicException(
            "Davinci {$capability} is not available through the new pipeline yet; use the preserved legacy migration flow until its extraction refactor is completed."
        );
    }
}
