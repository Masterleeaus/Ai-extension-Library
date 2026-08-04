<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

use InvalidArgumentException;

final readonly class SkillToolDefinition
{
    /**
     * @param array<string, mixed> $inputSchema
     * @param array<string, mixed> $outputSchema
     * @param list<string> $capabilities
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $key,
        public string $functionName,
        public string $description,
        public string $source = 'custom',
        public array $inputSchema = ['type' => 'object', 'properties' => []],
        public array $outputSchema = ['type' => 'object'],
        public array $capabilities = [],
        public string $approval = SkillToolApproval::NEVER,
        public bool $enabled = true,
        public array $metadata = [],
    ) {
        if (! preg_match('/^[a-z0-9][a-z0-9._-]{1,127}$/', $this->key)) {
            throw new InvalidArgumentException("Invalid skill tool key [{$this->key}].");
        }

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_-]{0,127}$/', $this->functionName)) {
            throw new InvalidArgumentException("Invalid skill tool function name [{$this->functionName}].");
        }

        if (($this->inputSchema['type'] ?? null) !== 'object') {
            throw new InvalidArgumentException("Skill tool [{$this->key}] input schema must be an object schema.");
        }

        SkillToolApproval::normalize($this->approval);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $key = self::requiredString($data, 'key');
        $functionName = self::optionalString($data, 'function_name') ?: self::defaultFunctionName($key);
        $description = self::optionalString($data, 'description') ?: $key;
        $source = self::optionalString($data, 'source') ?: self::inferSource($key);
        $inputSchema = self::schema($data['input_schema'] ?? ['type' => 'object', 'properties' => []], 'input_schema');
        $outputSchema = self::schema($data['output_schema'] ?? ['type' => 'object'], 'output_schema', false);
        $capabilities = self::stringList($data['capabilities'] ?? [], 'capabilities');
        $approval = SkillToolApproval::normalize($data['approval'] ?? SkillToolApproval::NEVER);
        $metadata = $data['metadata'] ?? [];

        if (! is_array($metadata)) {
            throw new InvalidArgumentException("Skill tool [{$key}] metadata must be an object.");
        }

        return new self(
            key: $key,
            functionName: $functionName,
            description: $description,
            source: $source,
            inputSchema: $inputSchema,
            outputSchema: $outputSchema,
            capabilities: $capabilities,
            approval: $approval,
            enabled: (bool) ($data['enabled'] ?? true),
            metadata: $metadata,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'function_name' => $this->functionName,
            'description' => $this->description,
            'source' => $this->source,
            'input_schema' => $this->inputSchema,
            'output_schema' => $this->outputSchema,
            'capabilities' => $this->capabilities,
            'approval' => $this->approval,
            'enabled' => $this->enabled,
            'metadata' => $this->metadata,
        ];
    }

    public static function reference(string $key): self
    {
        return self::fromArray(['key' => $key]);
    }

    private static function defaultFunctionName(string $key): string
    {
        $name = preg_replace('/[^A-Za-z0-9_]+/', '_', $key) ?: 'tool';

        return substr($name, 0, 128);
    }

    private static function inferSource(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'connector.') => 'connector',
            str_starts_with($key, 'ai-agent.') => 'ai-agent',
            str_starts_with($key, 'core.') => 'core',
            default => 'custom',
        };
    }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        $value = self::optionalString($data, $key);
        if ($value === '') {
            throw new InvalidArgumentException("Skill tool field [{$key}] is required.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private static function optionalString(array $data, string $key): string
    {
        $value = $data[$key] ?? '';
        if (! is_string($value)) {
            throw new InvalidArgumentException("Skill tool field [{$key}] must be a string.");
        }

        return trim($value);
    }

    /** @return array<string, mixed> */
    private static function schema(mixed $value, string $field, bool $requireObject = true): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException("Skill tool field [{$field}] must be an object schema.");
        }

        if ($requireObject && (($value['type'] ?? 'object') !== 'object')) {
            throw new InvalidArgumentException("Skill tool field [{$field}] must use type object.");
        }

        $value['type'] ??= 'object';
        if ($field === 'input_schema') {
            $value['properties'] ??= [];
        }

        return $value;
    }

    /** @return list<string> */
    private static function stringList(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException("Skill tool field [{$field}] must be a list.");
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new InvalidArgumentException("Skill tool field [{$field}] contains an invalid value.");
            }
            $items[] = trim($item);
        }

        return array_values(array_unique($items));
    }
}
