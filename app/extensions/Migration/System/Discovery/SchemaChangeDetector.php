<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Discovery;

final class SchemaChangeDetector
{
    /** @return array{breaking: bool, changes: array<int, array<string, mixed>>} */
    public function compare(array $before, array $after): array
    {
        $changes = [];
        $breaking = false;

        $beforeEntities = $this->entityMap($before);
        $afterEntities = $this->entityMap($after);

        foreach ($beforeEntities as $entityName => $beforeEntity) {
            if (! isset($afterEntities[$entityName])) {
                $breaking = true;
                $changes[] = ['kind' => 'entity_removed', 'entity' => $entityName, 'breaking' => true];
                continue;
            }

            $beforeFields = $this->fieldMap($beforeEntity);
            $afterFields = $this->fieldMap($afterEntities[$entityName]);

            foreach ($beforeFields as $fieldName => $beforeField) {
                if (! isset($afterFields[$fieldName])) {
                    $breaking = true;
                    $changes[] = [
                        'kind' => 'field_removed',
                        'entity' => $entityName,
                        'field' => $fieldName,
                        'breaking' => true,
                    ];
                    continue;
                }

                $beforeType = (string) ($beforeField['type'] ?? 'string');
                $afterType = (string) ($afterFields[$fieldName]['type'] ?? 'string');
                if ($beforeType !== $afterType && ! $this->compatibleWidening($beforeType, $afterType)) {
                    $breaking = true;
                    $changes[] = [
                        'kind' => 'field_type_changed',
                        'entity' => $entityName,
                        'field' => $fieldName,
                        'from' => $beforeType,
                        'to' => $afterType,
                        'breaking' => true,
                    ];
                }

                $beforeNullable = (bool) ($beforeField['nullable'] ?? true);
                $afterNullable = (bool) ($afterFields[$fieldName]['nullable'] ?? true);
                if ($beforeNullable && ! $afterNullable) {
                    $breaking = true;
                    $changes[] = [
                        'kind' => 'field_nullability_tightened',
                        'entity' => $entityName,
                        'field' => $fieldName,
                        'breaking' => true,
                    ];
                }
            }

            foreach ($afterFields as $fieldName => $afterField) {
                if (! isset($beforeFields[$fieldName])) {
                    $changes[] = [
                        'kind' => 'field_added',
                        'entity' => $entityName,
                        'field' => $fieldName,
                        'breaking' => false,
                    ];
                }
            }
        }

        foreach ($afterEntities as $entityName => $entity) {
            if (! isset($beforeEntities[$entityName])) {
                $changes[] = ['kind' => 'entity_added', 'entity' => $entityName, 'breaking' => false];
            }
        }

        return ['breaking' => $breaking, 'changes' => $changes];
    }

    /** @return array<string, array<string, mixed>> */
    private function entityMap(array $discovery): array
    {
        $map = [];
        foreach (($discovery['entities'] ?? []) as $entity) {
            if (is_array($entity) && isset($entity['name'])) {
                $map[(string) $entity['name']] = $entity;
            }
        }

        return $map;
    }

    /** @return array<string, array<string, mixed>> */
    private function fieldMap(array $entity): array
    {
        $map = [];
        foreach (($entity['fields'] ?? []) as $field) {
            if (is_array($field) && isset($field['name'])) {
                $map[(string) $field['name']] = $field;
            }
        }

        return $map;
    }

    private function compatibleWidening(string $before, string $after): bool
    {
        return ($before === 'integer' && $after === 'number')
            || ($before === 'datetime' && $after === 'string');
    }
}
