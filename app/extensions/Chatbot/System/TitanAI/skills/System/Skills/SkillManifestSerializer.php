<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Skills;

final class SkillManifestSerializer
{
    /** @param array<string, mixed> $metadata */
    public function serialize(
        string $name,
        string $description,
        string $version,
        string $instructions,
        array $metadata = [],
    ): string {
        $contract = is_array($metadata['skill_contract'] ?? null)
            ? $metadata['skill_contract']
            : [];
        unset($metadata['skill_contract']);

        $lines = [
            '---',
            'name: ' . $this->jsonScalar($name),
            'description: ' . $this->jsonScalar($description),
            'version: ' . $this->jsonScalar($version !== '' ? $version : '1.0.0'),
        ];

        foreach (['inputs', 'outputs', 'tools', 'capabilities'] as $field) {
            if (array_key_exists($field, $contract)) {
                $lines[] = $field . ': ' . $this->jsonValue($contract[$field]);
            }
        }

        if ($metadata !== []) {
            $lines[] = 'metadata: ' . $this->jsonValue($metadata);
        }

        $lines[] = '---';
        $lines[] = '';
        $lines[] = rtrim($instructions);

        return implode("\n", $lines);
    }

    private function jsonScalar(string $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function jsonValue(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
