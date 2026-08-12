<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Mapping;

use App\Extensions\Migration\System\Canonical\CanonicalEntityDefinition;

final class MappingPreviewService
{
    public function __construct(private readonly TransformationEngine $engine)
    {
    }

    /**
     * @param array<string, mixed> $source
     * @return array{payload:array<string,mixed>,audit:array<int,array<string,mixed>>,valid:true}
     */
    public function preview(CanonicalEntityDefinition $entity, MappingPlan $plan, array $source): array
    {
        $payload = [];
        $audit = [];
        $errors = [];

        foreach ($plan->rules as $rule) {
            if (! array_key_exists($rule->targetField, $entity->fields)) {
                $errors[] = "Unknown target field {$rule->targetField}.";
                continue;
            }

            $before = array_key_exists($rule->sourceField, $source)
                ? $source[$rule->sourceField]
                : $rule->defaultValue;
            $result = $this->engine->apply($before, $rule->steps, $source);
            $payload[$rule->targetField] = $result['value'];
            $audit[] = [
                'source_field' => $rule->sourceField,
                'target_field' => $rule->targetField,
                'transforms' => array_values(array_map(
                    static fn (array $entry): string => $entry['transform'],
                    $result['audit'],
                )),
                'before_digest' => hash('sha256', serialize($before)),
                'after_digest' => hash('sha256', serialize($result['value'])),
            ];
        }

        foreach ($entity->requiredFields() as $field) {
            if (! array_key_exists($field, $payload) || $this->blank($payload[$field])) {
                $errors[] = "Required destination field {$field} is missing or blank.";
            }
        }

        foreach ($entity->primaryIdentityFields() as $field) {
            if (! array_key_exists($field, $payload) || $this->blank($payload[$field])) {
                $errors[] = "Destination identity field {$field} is missing or blank.";
            }
        }

        foreach ($payload as $field => $value) {
            if (! isset($entity->fields[$field]) || $this->blank($value)) {
                continue;
            }
            $type = (string) ($entity->fields[$field]['type'] ?? 'mixed');
            if (! $this->matchesType($value, $type)) {
                $errors[] = "Destination field {$field} does not match canonical type {$type}.";
            }
        }

        if ($errors !== []) {
            throw new MappingValidationException(array_values(array_unique($errors)));
        }

        return ['payload' => $payload, 'audit' => $audit, 'valid' => true];
    }

    private function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string', 'datetime', 'date' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array', 'json' => is_array($value),
            'mixed' => true,
            default => false,
        };
    }
}
