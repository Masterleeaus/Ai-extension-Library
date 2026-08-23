<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Canonical;

use InvalidArgumentException;

final readonly class CanonicalEntityDefinition
{
    private const KEY_PATTERN = '/^[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+$/';

    /**
     * @param array<string, array<string, mixed>> $fields
     * @param array<int, array<string, mixed>> $identityRules
     * @param array<int, string> $dependencyKeys
     * @param array<int, string> $requiredModules
     */
    public function __construct(
        public string $key,
        public string $name,
        public array $fields,
        public array $identityRules,
        public array $dependencyKeys = [],
        public array $requiredModules = [],
        public string $handlerKey = '',
    ) {
        if (preg_match(self::KEY_PATTERN, $this->key) !== 1) {
            throw new InvalidArgumentException('Canonical entity key must be a lowercase dotted identifier.');
        }
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Canonical entity name cannot be empty.');
        }
        if ($this->fields === []) {
            throw new InvalidArgumentException('Canonical entity must declare at least one field.');
        }
        if ($this->handlerKey === '') {
            throw new InvalidArgumentException('Canonical entity must declare a destination handler key.');
        }

        foreach ($this->fields as $field => $definition) {
            if (! is_string($field) || preg_match('/^[a-z][a-z0-9_]*$/', $field) !== 1) {
                throw new InvalidArgumentException('Canonical field names must be lowercase snake-case identifiers.');
            }
            if (! is_array($definition) || trim((string) ($definition['type'] ?? '')) === '') {
                throw new InvalidArgumentException("Canonical field {$field} must declare a type.");
            }
        }

        if ($this->identityRules === []) {
            throw new InvalidArgumentException('Canonical entity must declare at least one identity rule.');
        }
        foreach ($this->identityRules as $rule) {
            $fields = is_array($rule['fields'] ?? null) ? array_values($rule['fields']) : [];
            if ($fields === []) {
                throw new InvalidArgumentException('Canonical identity rule must declare fields.');
            }
            foreach ($fields as $field) {
                if (! is_string($field) || ! array_key_exists($field, $this->fields)) {
                    throw new InvalidArgumentException('Canonical identity rule references an unknown field.');
                }
            }
            $match = (string) ($rule['match'] ?? 'exact');
            if (! in_array($match, ['exact', 'case_insensitive', 'normalised'], true)) {
                throw new InvalidArgumentException('Canonical identity rule match mode is not supported.');
            }
        }

        foreach (array_merge($this->dependencyKeys, $this->requiredModules) as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException('Canonical dependencies/modules must be non-empty strings.');
            }
        }
    }

    /** @return array<int, string> */
    public function requiredFields(): array
    {
        $fields = [];
        foreach ($this->fields as $field => $definition) {
            if (($definition['required'] ?? false) === true) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /** @return array<int, string> */
    public function primaryIdentityFields(): array
    {
        return array_values($this->identityRules[0]['fields'] ?? []);
    }

    public function isAvailable(ModuleAvailabilityResolver $modules): bool
    {
        foreach ($this->requiredModules as $module) {
            if (! $modules->isAvailable($module)) {
                return false;
            }
        }

        return true;
    }
}
