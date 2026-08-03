<?php

declare(strict_types=1);

namespace TitanAI\Hybrid\Diagnostics;

use TitanAI\Hybrid\Registries\UnifiedRegistry;
use Throwable;

final class TitanAIDiagnostics
{
    /** @var array<string,array<string,int>> */
    private array $counters = [
        'events' => ['published' => 0, 'duplicate' => 0, 'disabled' => 0, 'failed' => 0],
        'memory' => ['bridged' => 0, 'forgotten' => 0, 'read' => 0, 'failed' => 0],
        'orchestration' => ['succeeded' => 0, 'failed' => 0],
    ];

    /** @var list<array<string,mixed>> */
    private array $recent = [];

    public function __construct(private readonly int $limit = 50) {}

    public function recordEvent(string $status, object $event, ?string $idempotencyKey = null, ?Throwable $exception = null): void
    {
        $counter = match ($status) {
            'published' => 'published',
            'duplicate' => 'duplicate',
            'disabled' => 'disabled',
            default => 'failed',
        };
        $this->counters['events'][$counter]++;
        $this->remember('event', $status, [
            'event' => $event::class,
            'idempotency_key_hash' => $idempotencyKey !== null ? hash('sha256', $idempotencyKey) : null,
            'exception' => $exception !== null ? $exception::class : null,
        ]);
    }

    public function recordMemory(string $status, string $operation, array $context = [], ?Throwable $exception = null): void
    {
        $counter = match ($status) {
            'bridged' => 'bridged',
            'forgotten' => 'forgotten',
            'read' => 'read',
            default => 'failed',
        };
        $this->counters['memory'][$counter]++;
        $this->remember('memory', $status, [
            'operation' => $operation,
            'entity_type' => $context['entity_type'] ?? null,
            'scope' => $context['scope'] ?? null,
            'source' => $context['source'] ?? null,
            'exception' => $exception !== null ? $exception::class : null,
        ]);
    }

    public function recordOrchestration(string $status, string $componentType, string $key, array $context = [], ?Throwable $exception = null): void
    {
        $counter = $status === 'succeeded' ? 'succeeded' : 'failed';
        $this->counters['orchestration'][$counter]++;
        $this->remember('orchestration', $status, [
            'component_type' => $componentType,
            'key' => $key,
            'correlation_id' => $context['correlation_id'] ?? null,
            'exception' => $exception !== null ? $exception::class : null,
        ]);
    }

    public function snapshot(?UnifiedRegistry $registry = null): array
    {
        $failed = $this->counters['events']['failed']
            + $this->counters['memory']['failed']
            + $this->counters['orchestration']['failed'];

        $snapshot = [
            'health' => $failed > 0 ? 'degraded' : 'healthy',
            ...$this->counters,
            'recent' => $this->recent,
        ];

        if ($registry !== null) {
            $snapshot['registry'] = $registry->counts();
        }

        return $snapshot;
    }

    public function reset(): void
    {
        foreach ($this->counters as $group => $counters) {
            foreach (array_keys($counters) as $counter) {
                $this->counters[$group][$counter] = 0;
            }
        }
        $this->recent = [];
    }

    private function remember(string $category, string $status, array $context): void
    {
        $this->recent[] = [
            'at' => gmdate('c'),
            'category' => $category,
            'status' => $status,
            ...array_filter($context, static fn (mixed $value): bool => $value !== null && $value !== ''),
        ];

        $limit = max(1, $this->limit);
        if (count($this->recent) > $limit) {
            $this->recent = array_slice($this->recent, -$limit);
        }
    }
}
