<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\AbstractSourceConnector;
use App\Extensions\Migration\System\Discovery\DiscoveryProfiler;
use InvalidArgumentException;

abstract class AbstractFileConnector extends AbstractSourceConnector
{
    protected ?DiscoveryProfiler $profiler = null;

    protected function profiler(): DiscoveryProfiler
    {
        return $this->profiler ??= new DiscoveryProfiler();
    }

    public function testConnection(array $configuration): array
    {
        try {
            $path = $this->path($configuration);
            $readable = is_file($path) && is_readable($path);

            return [
                'successful' => $readable,
                'connector' => $this->definition()->key,
                'message' => $readable ? 'Source file is readable.' : 'Source file is not readable.',
                'configuration' => $this->redactConfiguration($configuration),
            ];
        } catch (\Throwable $exception) {
            return [
                'successful' => false,
                'connector' => $this->definition()->key,
                'message' => $exception->getMessage(),
                'configuration' => $this->redactConfiguration($configuration),
            ];
        }
    }

    public function discover(array $configuration): array
    {
        $entity = (string) ($configuration['entity'] ?? pathinfo($this->path($configuration), PATHINFO_FILENAME) ?: 'records');
        $sampleLimit = max(0, min(25, (int) ($configuration['sample_limit'] ?? 5)));
        $profile = $this->profiler()->profile($this->rows($configuration), $entity, $sampleLimit);

        return $this->discoveryResult([$profile], [
            'source' => [
                'path' => basename($this->path($configuration)),
                'format' => $this->definition()->key,
            ],
        ]);
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = $this->configurationFromPlan($entityPlan);
        $skip = max(0, (int) ($checkpoint['offset'] ?? 0));
        $index = 0;

        foreach ($this->rows($configuration) as $row) {
            if ($index++ < $skip) {
                continue;
            }

            yield $row;
        }
    }

    /** @return iterable<int, array<string, mixed>> */
    abstract protected function rows(array $configuration): iterable;

    protected function path(array $configuration): string
    {
        $path = $configuration['path'] ?? null;
        if (! is_string($path) || trim($path) === '') {
            throw new InvalidArgumentException("Connector {$this->definition()->key} requires a source path.");
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('Source file must exist and be readable.');
        }

        return $path;
    }
}
