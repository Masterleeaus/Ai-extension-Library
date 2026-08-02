<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

use InvalidArgumentException;

final readonly class SkillToolSet
{
    /**
     * @param array<string, SkillToolDefinition> $allowed
     * @param list<string> $required
     */
    public function __construct(
        private array $allowed,
        private array $required = [],
    ) {
        foreach ($this->required as $key) {
            if (! isset($this->allowed[$key])) {
                throw new InvalidArgumentException("Required skill tool [{$key}] is not present in the allowed tool set.");
            }
        }
    }

    /** @param array<string, mixed>|list<mixed>|null $data */
    public static function fromArray(?array $data): self
    {
        if ($data === null || $data === []) {
            return new self([]);
        }

        $allowedInput = array_is_list($data) ? $data : ($data['allowed'] ?? []);
        $requiredInput = array_is_list($data) ? [] : ($data['required'] ?? []);

        if (! is_array($allowedInput) || ! is_array($requiredInput)) {
            throw new InvalidArgumentException('Skill tools must define allowed and required lists.');
        }

        $allowed = [];
        foreach ($allowedInput as $item) {
            $definition = match (true) {
                is_string($item) => SkillToolDefinition::reference(trim($item)),
                is_array($item) => SkillToolDefinition::fromArray($item),
                default => throw new InvalidArgumentException('Each allowed skill tool must be a key string or definition object.'),
            };

            if (isset($allowed[$definition->key])) {
                throw new InvalidArgumentException("Duplicate skill tool [{$definition->key}].");
            }

            $allowed[$definition->key] = $definition;
        }

        $required = [];
        foreach ($requiredInput as $key) {
            if (! is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException('Each required skill tool must be a non-empty key string.');
            }
            $required[] = trim($key);
        }

        return new self($allowed, array_values(array_unique($required)));
    }

    public function allows(string $key): bool
    {
        return isset($this->allowed[$key]) && $this->allowed[$key]->enabled;
    }

    public function requires(string $key): bool
    {
        return in_array($key, $this->required, true);
    }

    public function definition(string $key): ?SkillToolDefinition
    {
        return $this->allowed[$key] ?? null;
    }

    /** @return list<SkillToolDefinition> */
    public function definitions(): array
    {
        return array_values($this->allowed);
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->allowed);
    }

    /** @return list<string> */
    public function requiredKeys(): array
    {
        return $this->required;
    }

    public function isEmpty(): bool
    {
        return $this->allowed === [];
    }

    /** @return array{allowed: list<array<string, mixed>>, required: list<string>} */
    public function toArray(): array
    {
        return [
            'allowed' => array_map(static fn (SkillToolDefinition $tool): array => $tool->toArray(), $this->definitions()),
            'required' => $this->required,
        ];
    }
}
