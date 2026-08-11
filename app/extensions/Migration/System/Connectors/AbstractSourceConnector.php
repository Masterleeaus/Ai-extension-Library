<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors;

use App\Extensions\Migration\System\Connectors\Contracts\MigrationSourceConnectorInterface;
use App\Extensions\Migration\System\Discovery\SchemaFingerprint;
use App\Extensions\Migration\System\Security\ConnectorConfigurationRedactor;
use LogicException;

abstract class AbstractSourceConnector implements MigrationSourceConnectorInterface
{
    public function __construct(
        protected ?ConnectorConfigurationRedactor $configurationRedactor = null,
        protected ?SchemaFingerprint $schemaFingerprint = null,
    ) {
        $this->configurationRedactor ??= new ConnectorConfigurationRedactor();
        $this->schemaFingerprint ??= new SchemaFingerprint();
    }

    public function estimate(array $project): array
    {
        $configuration = $project['configuration'] ?? $project;
        $discovery = $this->discover(is_array($configuration) ? $configuration : []);
        $records = 0;

        foreach (($discovery['entities'] ?? []) as $entity) {
            $records += (int) ($entity['record_count'] ?? 0);
        }

        return [
            'connector' => $this->definition()->key,
            'entities' => count($discovery['entities'] ?? []),
            'records' => $records,
            'fingerprint' => $discovery['fingerprint'] ?? null,
        ];
    }

    public function supportsIncrementalSync(): bool
    {
        return $this->definition()->supportsIncrementalSync;
    }

    public function readIncremental(array $entityPlan, array $checkpoint): iterable
    {
        if (! $this->supportsIncrementalSync()) {
            throw new LogicException("Connector {$this->definition()->key} does not support incremental extraction.");
        }

        throw new LogicException("Connector {$this->definition()->key} has not implemented incremental extraction.");
    }

    public function fetchAttachment(array $attachment): mixed
    {
        throw new LogicException("Connector {$this->definition()->key} does not expose attachment retrieval.");
    }

    public function redactConfiguration(array $configuration): array
    {
        return $this->configurationRedactor->redact($configuration);
    }

    /** @param array<int, array<string, mixed>> $entities
     *  @param array<string, mixed> $metadata
     *  @return array<string, mixed>
     */
    protected function discoveryResult(array $entities, array $metadata = []): array
    {
        $result = array_merge([
            'connector' => $this->definition()->key,
            'read_only' => true,
            'entities' => $entities,
        ], $metadata);

        $result['fingerprint'] = $this->schemaFingerprint->fromDiscovery($result);

        return $result;
    }

    protected function configurationFromPlan(array $entityPlan): array
    {
        $configuration = $entityPlan['configuration']
            ?? ($entityPlan['source']['configuration'] ?? null)
            ?? $entityPlan;

        return is_array($configuration) ? $configuration : [];
    }
}
