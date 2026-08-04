<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

use Closure;
use InvalidArgumentException;
use RuntimeException;

final class SkillToolRegistry
{
    /** @var array<string, SkillToolDefinition> */
    private array $definitions = [];

    /** @var array<string, Closure> */
    private array $executors = [];

    public function register(SkillToolDefinition $definition, ?callable $executor = null): void
    {
        $this->definitions[$definition->key] = $definition;

        if ($executor !== null) {
            $this->executors[$definition->key] = Closure::fromCallable($executor);
        }
    }

    /** @param array<string, mixed> $definition */
    public function registerArray(array $definition, ?callable $executor = null): void
    {
        $this->register(SkillToolDefinition::fromArray($definition), $executor);
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    public function canExecute(string $key): bool
    {
        return isset($this->executors[$key]);
    }

    public function definition(string $key): ?SkillToolDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /** @return list<SkillToolDefinition> */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    /** @param array<string, mixed> $arguments */
    public function execute(string $key, array $arguments, SkillToolExecutionContext $context): mixed
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Skill tool [{$key}] is not registered.");
        }

        if (! isset($this->executors[$key])) {
            throw new RuntimeException("Skill tool [{$key}] has no executable adapter registered.");
        }

        return ($this->executors[$key])($arguments, $context, $this->definitions[$key]);
    }
}
