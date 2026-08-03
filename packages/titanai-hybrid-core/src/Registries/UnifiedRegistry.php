<?php

declare(strict_types=1);

namespace TitanAI\Hybrid\Registries;

use TitanAI\Hybrid\Contracts\ActionDefinition;
use TitanAI\Hybrid\Contracts\ConnectorDefinition;
use TitanAI\Hybrid\Contracts\Registrable;
use TitanAI\Hybrid\Contracts\SkillDefinition;
use TitanAI\Hybrid\Contracts\ToolDefinition;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

/**
 * Central in-process component discovery registry shared by TitanAI extensions.
 */
final class UnifiedRegistry
{
    /** @var Collection<string, SkillDefinition> */
    private Collection $skills;

    /** @var Collection<string, ActionDefinition> */
    private Collection $actions;

    /** @var Collection<string, ConnectorDefinition> */
    private Collection $connectors;

    /** @var Collection<string, ToolDefinition> */
    private Collection $tools;

    public function __construct(private readonly bool $allowOverrides = false)
    {
        $this->flush();
    }

    public function register(Registrable $component): self
    {
        return match (true) {
            $component instanceof SkillDefinition => $this->registerSkill($component->key(), $component),
            $component instanceof ActionDefinition => $this->registerAction($component->key(), $component),
            $component instanceof ConnectorDefinition => $this->registerConnector($component->key(), $component),
            $component instanceof ToolDefinition => $this->registerTool($component->key(), $component),
            default => throw new InvalidArgumentException('Unsupported TitanAI component type.'),
        };
    }

    public function registerSkill(string $key, SkillDefinition $skill): self
    {
        $this->put($this->skills, $key, $skill, 'skill');
        return $this;
    }

    public function getSkill(string $key): ?SkillDefinition
    {
        return $this->skills->get($key);
    }

    /** @return Collection<string, SkillDefinition> */
    public function allSkills(): Collection
    {
        return clone $this->skills;
    }

    public function hasSkill(string $key): bool
    {
        return $this->skills->has($key);
    }

    public function registerAction(string $key, ActionDefinition $action): self
    {
        $this->put($this->actions, $key, $action, 'action');
        return $this;
    }

    public function getAction(string $key): ?ActionDefinition
    {
        return $this->actions->get($key);
    }

    /** @return Collection<string, ActionDefinition> */
    public function allActions(): Collection
    {
        return clone $this->actions;
    }

    public function hasAction(string $key): bool
    {
        return $this->actions->has($key);
    }

    public function registerConnector(string $key, ConnectorDefinition $connector): self
    {
        $this->put($this->connectors, $key, $connector, 'connector');
        return $this;
    }

    public function getConnector(string $key): ?ConnectorDefinition
    {
        return $this->connectors->get($key);
    }

    /** @return Collection<string, ConnectorDefinition> */
    public function allConnectors(): Collection
    {
        return clone $this->connectors;
    }

    public function hasConnector(string $key): bool
    {
        return $this->connectors->has($key);
    }

    public function registerTool(string $key, ToolDefinition $tool): self
    {
        $this->put($this->tools, $key, $tool, 'tool');
        return $this;
    }

    public function getTool(string $key): ?ToolDefinition
    {
        return $this->tools->get($key);
    }

    /** @return Collection<string, ToolDefinition> */
    public function allTools(): Collection
    {
        return clone $this->tools;
    }

    public function hasTool(string $key): bool
    {
        return $this->tools->has($key);
    }

    /** @return array{skills:list<string>,actions:list<string>,connectors:list<string>,tools:list<string>,total:int} */
    public function summary(): array
    {
        return [
            'skills' => $this->skills->keys()->values()->all(),
            'actions' => $this->actions->keys()->values()->all(),
            'connectors' => $this->connectors->keys()->values()->all(),
            'tools' => $this->tools->keys()->values()->all(),
            'total' => $this->skills->count() + $this->actions->count() + $this->connectors->count() + $this->tools->count(),
        ];
    }

    /** @return array{skills:int,actions:int,connectors:int,tools:int,total:int} */
    public function counts(): array
    {
        return [
            'skills' => $this->skills->count(),
            'actions' => $this->actions->count(),
            'connectors' => $this->connectors->count(),
            'tools' => $this->tools->count(),
            'total' => $this->skills->count() + $this->actions->count() + $this->connectors->count() + $this->tools->count(),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function skillMetadata(): array
    {
        return $this->metadataFor($this->skills);
    }

    /** @return array<string, array<string, mixed>> */
    public function actionMetadata(): array
    {
        return $this->metadataFor($this->actions);
    }

    /** @return array<string, array<string, mixed>> */
    public function connectorMetadata(): array
    {
        return $this->metadataFor($this->connectors);
    }

    public function flush(): self
    {
        $this->skills = collect();
        $this->actions = collect();
        $this->connectors = collect();
        $this->tools = collect();
        return $this;
    }

    /** @param Collection<string, Registrable> $collection */
    private function put(Collection $collection, string $key, Registrable $component, string $type): void
    {
        $key = trim($key);
        if ($key === '') {
            throw new InvalidArgumentException("TitanAI {$type} key cannot be empty.");
        }
        if ($component->key() !== $key) {
            throw new InvalidArgumentException("TitanAI {$type} key mismatch: [{$key}] != [{$component->key()}].");
        }
        if (! $this->allowOverrides && $collection->has($key)) {
            throw new LogicException("TitanAI {$type} [{$key}] is already registered.");
        }
        $collection->put($key, $component);
    }

    /**
     * @param Collection<string, Registrable> $collection
     * @return array<string, array<string, mixed>>
     */
    private function metadataFor(Collection $collection): array
    {
        return $collection->mapWithKeys(static function (Registrable $component, string $key): array {
            return [$key => [
                'key' => $component->key(),
                'name' => $component->name(),
                'description' => $component->description(),
                'metadata' => $component->metadata(),
            ]];
        })->all();
    }
}
