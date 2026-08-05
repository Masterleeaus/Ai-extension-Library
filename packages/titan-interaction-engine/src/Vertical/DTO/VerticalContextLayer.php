<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\DTO;

use InvalidArgumentException;

final readonly class VerticalContextLayer
{
    public const KIND_PRECEDENCE = [
        'platform_base' => 100,
        'country' => 200,
        'vertical_family' => 300,
        'subtype' => 400,
        'capability' => 500,
        'tenant' => 600,
        'role_device' => 700,
        'accessibility' => 800,
        'operational_state' => 900,
    ];

    public const VALUE_SECTIONS = [
        'terminology',
        'theme',
        'navigation',
        'workspaces',
        'capabilities',
        'questions',
        'forms',
        'checklists',
        'widgets',
        'ai',
        'activation',
    ];

    public function __construct(
        public string $id,
        public string $kind,
        public string $version,
        public int $precedence,
        public string $source,
        public array $values,
        public array $constraints,
        public ContextValueProvenance $provenance,
        public string $generatedAt,
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('Vertical context layer ID cannot be empty.');
        }
        if (!array_key_exists($kind, self::KIND_PRECEDENCE)) {
            throw new InvalidArgumentException("Unsupported vertical context layer kind: {$kind}.");
        }
        if (!preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $version)) {
            throw new InvalidArgumentException("Vertical context layer version must be semantic: {$version}.");
        }
        if ($precedence !== self::KIND_PRECEDENCE[$kind]) {
            throw new InvalidArgumentException("Layer {$id} cannot override precedence for kind {$kind}.");
        }
        if (trim($source) === '') {
            throw new InvalidArgumentException('Vertical context layer source cannot be empty.');
        }
        foreach (array_keys($values) as $section) {
            if (!in_array($section, self::VALUE_SECTIONS, true)) {
                throw new InvalidArgumentException("Unsupported vertical context value section: {$section}.");
            }
            if (!is_array($values[$section])) {
                throw new InvalidArgumentException("Vertical context value section {$section} must be an array.");
            }
        }
        if (trim($generatedAt) === '') {
            throw new InvalidArgumentException('Vertical context layer generated_at cannot be empty.');
        }
    }

    public static function fromArray(array $data): self
    {
        $id = trim((string) ($data['id'] ?? ''));
        $kind = (string) ($data['kind'] ?? '');
        $defaultSourceType = match ($kind) {
            'platform_base', 'operational_state' => 'system_default',
            'tenant' => 'existing_company_data',
            default => 'vertical_pack',
        };
        $precedence = isset($data['precedence'])
            ? (int) $data['precedence']
            : (self::KIND_PRECEDENCE[$kind] ?? -1);

        return new self(
            id: $id,
            kind: $kind,
            version: (string) ($data['version'] ?? ''),
            precedence: $precedence,
            source: (string) ($data['source'] ?? $id),
            values: (array) ($data['values'] ?? []),
            constraints: (array) ($data['constraints'] ?? []),
            provenance: ContextValueProvenance::fromArray(
                (array) ($data['provenance'] ?? []),
                $id,
                $defaultSourceType,
            ),
            generatedAt: (string) ($data['generated_at'] ?? gmdate(DATE_ATOM)),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'version' => $this->version,
            'precedence' => $this->precedence,
            'source' => $this->source,
            'values' => $this->values,
            'constraints' => $this->constraints,
            'provenance' => $this->provenance->toArray(),
            'generated_at' => $this->generatedAt,
        ];
    }
}
