<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Discovery;

use App\Extensions\Migration\System\Security\SensitiveValueMasker;

final class DiscoveryProfiler
{
    public function __construct(
        private readonly TypeInferrer $types = new TypeInferrer(),
        private readonly SensitiveValueMasker $masker = new SensitiveValueMasker(),
    ) {
    }

    /**
     * @param iterable<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    public function profile(iterable $rows, string $entity, int $sampleLimit = 5): array
    {
        $count = 0;
        $fieldState = [];
        $samples = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $count++;
            if (count($samples) < max(0, $sampleLimit)) {
                $samples[] = $this->masker->maskRow($row);
            }

            foreach ($row as $name => $value) {
                $name = (string) $name;
                $observed = $this->types->infer($value);
                $state = $fieldState[$name] ?? [
                    'name' => $name,
                    'type' => null,
                    'nullable' => false,
                    'observed' => 0,
                ];

                $state['observed']++;
                $state['nullable'] = $state['nullable'] || $observed === 'null';
                $state['type'] = $this->types->merge($state['type'], $observed);
                $fieldState[$name] = $state;
            }
        }

        $fields = [];
        foreach ($fieldState as $state) {
            $state['nullable'] = $state['nullable'] || $state['observed'] < $count;
            $state['type'] = $state['type'] ?? 'string';
            unset($state['observed']);
            $fields[] = $state;
        }

        usort($fields, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
        $fieldNames = array_column($fields, 'name');

        return [
            'name' => $entity,
            'record_count' => $count,
            'fields' => $fields,
            'keys' => $this->keyHints($fieldNames, $entity),
            'relationships' => $this->relationshipHints($fieldNames),
            'incremental_fields' => $this->incrementalHints($fieldNames),
            'attachment_fields' => $this->attachmentHints($fieldNames),
            'samples' => $samples,
        ];
    }

    /** @param array<int, string> $fields
     *  @return array<int, array<string, string>>
     */
    private function keyHints(array $fields, string $entity): array
    {
        $normalisedEntity = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $entity) ?? $entity);
        foreach (['id', 'uuid', $normalisedEntity . '_id'] as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return [['field' => $candidate, 'kind' => 'candidate_primary']];
            }
        }

        return [];
    }

    /** @param array<int, string> $fields
     *  @return array<int, array<string, string>>
     */
    private function relationshipHints(array $fields): array
    {
        $relationships = [];
        foreach ($fields as $field) {
            if ($field !== 'id' && str_ends_with(strtolower($field), '_id')) {
                $relationships[] = [
                    'field' => $field,
                    'target_hint' => substr($field, 0, -3),
                    'kind' => 'inferred_foreign_key',
                ];
            }
        }

        return $relationships;
    }

    /** @param array<int, string> $fields
     *  @return array<int, string>
     */
    private function incrementalHints(array $fields): array
    {
        $preferred = ['updated_at', 'modified_at', 'updated', 'modified', 'created_at', 'timestamp', 'version'];

        return array_values(array_filter($preferred, static fn (string $field): bool => in_array($field, $fields, true)));
    }

    /** @param array<int, string> $fields
     *  @return array<int, string>
     */
    private function attachmentHints(array $fields): array
    {
        return array_values(array_filter($fields, static function (string $field): bool {
            return preg_match('/(?:file|attachment|image|photo|document|media|avatar|url|path)/i', $field) === 1;
        }));
    }
}
