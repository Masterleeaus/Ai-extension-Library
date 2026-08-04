<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Skills;

use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolSet;
use InvalidArgumentException;

final class SkillManifestValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data): SkillManifest
    {
        $name = $this->string($data, 'name', 255, true);
        $description = $this->string($data, 'description', 2000);
        $instructions = $this->string($data, 'instructions', 100000, true);
        $version = $this->string($data, 'version', 64) ?: '1.0.0';
        if (! preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $version)) {
            throw new InvalidArgumentException('Skill version must use semantic version syntax.');
        }

        $metadata = $data['metadata'] ?? [];
        if (! is_array($metadata)) {
            throw new InvalidArgumentException('Skill metadata must be an object.');
        }

        $embeddedContract = is_array($metadata['skill_contract'] ?? null)
            ? $metadata['skill_contract']
            : [];

        $inputs = $this->objectSchema($data['inputs'] ?? $embeddedContract['inputs'] ?? null, 'inputs', true);
        $outputs = $this->objectSchema($data['outputs'] ?? $embeddedContract['outputs'] ?? null, 'outputs', false);
        $tools = SkillToolSet::fromArray($data['tools'] ?? $embeddedContract['tools'] ?? []);
        $capabilities = $this->stringList(
            $data['capabilities'] ?? $embeddedContract['capabilities'] ?? [],
            'capabilities',
        );

        unset($metadata['skill_contract']);

        return new SkillManifest(
            name: $name,
            description: $description,
            instructions: $instructions,
            version: $version,
            inputs: $inputs,
            outputs: $outputs,
            tools: $tools,
            capabilities: $capabilities,
            metadata: $metadata,
        );
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key, int $maxLength, bool $required = false): string
    {
        $value = $data[$key] ?? '';

        if (! is_string($value)) {
            throw new InvalidArgumentException("Skill field [{$key}] must be a string.");
        }

        $value = trim($value);

        if ($required && $value === '') {
            throw new InvalidArgumentException("Skill field [{$key}] is required.");
        }

        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

        if ($length > $maxLength) {
            throw new InvalidArgumentException("Skill field [{$key}] exceeds {$maxLength} characters.");
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function objectSchema(mixed $value, string $field, bool $withProperties): array
    {
        if ($value === null || $value === []) {
            return $withProperties
                ? ['type' => 'object', 'properties' => []]
                : ['type' => 'object'];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("Skill field [{$field}] must be an object schema.");
        }

        $value['type'] ??= 'object';
        if ($value['type'] !== 'object') {
            throw new InvalidArgumentException("Skill field [{$field}] must use type object.");
        }

        if ($withProperties) {
            $value['properties'] ??= [];
            if (! is_array($value['properties'])) {
                throw new InvalidArgumentException("Skill field [{$field}.properties] must be an object.");
            }
        }

        if (isset($value['required'])) {
            $value['required'] = $this->stringList($value['required'], "{$field}.required");
        }

        return $value;
    }

    /** @return list<string> */
    private function stringList(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException("Skill field [{$field}] must be a list.");
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new InvalidArgumentException("Skill field [{$field}] contains an invalid value.");
            }
            $items[] = trim($item);
        }

        return array_values(array_unique($items));
    }
}
