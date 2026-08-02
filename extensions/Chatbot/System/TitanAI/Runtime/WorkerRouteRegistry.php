<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use App\Services\AI\Registry\CleaningAIWorkerRegistry;
use RuntimeException;

final class WorkerRouteRegistry
{
    /** @var array<string,class-string>|null */
    private ?array $workers = null;

    /** @var array<string,int>|null */
    private ?array $tiers = null;

    public function __construct(private readonly Tier3AgentCapabilityCatalog $tier3Catalog) {}

    public function has(string $workerId): bool
    {
        return $this->classFor($workerId) !== null;
    }

    /** @return class-string|null */
    public function classFor(string $workerId): ?string
    {
        $canonical = $this->canonicalId($workerId);
        return $this->all()[$canonical] ?? null;
    }

    public function canonicalId(string $workerId): string
    {
        $workerId = trim($workerId);
        return CleaningAIWorkerRegistry::aliases()[$workerId] ?? $workerId;
    }

    public function tierFor(string $workerId): ?int
    {
        $this->all();
        return $this->tiers[$this->canonicalId($workerId)] ?? null;
    }

    /** @return array<string,mixed>|null */
    public function definitionFor(string $workerId): ?array
    {
        $class = $this->classFor($workerId);
        if ($class === null || ! method_exists($class, 'definition')) return null;
        $definition = (array) $class::definition();
        return $this->tierFor($workerId) === 3
            ? $this->tier3Catalog->enrich($this->canonicalId($workerId), $definition)
            : $definition;
    }

    /** @return array<string,class-string> */
    public function all(): array
    {
        if ($this->workers !== null) return $this->workers;

        $workers = [];
        $tiers = [];
        foreach (CleaningAIWorkerRegistry::byTier() as $tier => $classes) {
            foreach ($classes as $class) {
                if (! class_exists($class) || ! method_exists($class, 'id')) continue;
                $id = trim((string) $class::id());
                if ($id === '') throw new RuntimeException("Titan AI worker [{$class}] has an empty id.");
                if (isset($workers[$id]) && $workers[$id] !== $class) {
                    throw new RuntimeException("Duplicate Titan AI worker id [{$id}].");
                }
                $workers[$id] = $class;
                $tiers[$id] = (int) $tier;
            }
        }

        ksort($workers);
        $this->tiers = $tiers;
        return $this->workers = $workers;
    }

    /** @return array<string,mixed> */
    public function diagnostics(?AgentExecutionPathResolver $executionPaths = null, ?AssistantDelegationGraph $delegations = null): array
    {
        $byTier = [1 => 0, 2 => 0, 3 => 0];
        $invalid = [];
        foreach ($this->all() as $id => $class) {
            $tier = $this->tierFor($id);
            if (isset($byTier[$tier])) $byTier[$tier]++;
            $definition = $this->definitionFor($id) ?? [];
            foreach (['id','name','tier','role','capabilities','permissions'] as $field) {
                if (! array_key_exists($field, $definition)) $invalid[$id][] = "missing_{$field}";
            }
        }

        return [
            'counts' => $byTier,
            'total' => array_sum($byTier),
            'aliases' => CleaningAIWorkerRegistry::aliases(),
            'invalid' => $invalid,
            'execution_paths' => $executionPaths?->diagnostics(),
            'delegations' => $delegations?->diagnostics(),
            'healthy' => $invalid === []
                && ($executionPaths === null || $executionPaths->diagnostics()['invalid'] === [])
                && ($delegations === null || $delegations->diagnostics()['healthy'] === true),
        ];
    }
}
