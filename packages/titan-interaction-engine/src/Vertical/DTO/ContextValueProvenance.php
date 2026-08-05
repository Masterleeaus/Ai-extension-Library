<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\DTO;

use InvalidArgumentException;

final readonly class ContextValueProvenance
{
    private const SOURCE_TYPES = [
        'system_default',
        'vertical_pack',
        'existing_company_data',
        'imported',
        'user_entered',
        'ai_extracted',
        'ai_suggested',
    ];

    private const RISKS = ['low', 'medium', 'high', 'critical'];

    public function __construct(
        public string $source,
        public string $sourceType = 'system_default',
        public float $confidence = 1.0,
        public bool $confirmed = true,
        public string $risk = 'low',
        public int $revision = 0,
    ) {
        if (trim($source) === '') {
            throw new InvalidArgumentException('Context provenance source cannot be empty.');
        }
        if (!in_array($sourceType, self::SOURCE_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported context provenance source type: {$sourceType}.");
        }
        if (!is_finite($confidence) || $confidence < 0.0 || $confidence > 1.0) {
            throw new InvalidArgumentException('Context provenance confidence must be between 0 and 1.');
        }
        if (!in_array($risk, self::RISKS, true)) {
            throw new InvalidArgumentException("Unsupported context provenance risk: {$risk}.");
        }
        if ($revision < 0) {
            throw new InvalidArgumentException('Context provenance revision cannot be negative.');
        }
    }

    public static function fromArray(array $data, string $defaultSource, string $defaultSourceType): self
    {
        return new self(
            source: (string) ($data['source'] ?? $defaultSource),
            sourceType: (string) ($data['source_type'] ?? $defaultSourceType),
            confidence: (float) ($data['confidence'] ?? 1.0),
            confirmed: (bool) ($data['confirmed'] ?? true),
            risk: (string) ($data['risk'] ?? 'low'),
            revision: (int) ($data['revision'] ?? 0),
        );
    }

    public function withSource(string $source): self
    {
        return new self(
            source: $source,
            sourceType: $this->sourceType,
            confidence: $this->confidence,
            confirmed: $this->confirmed,
            risk: $this->risk,
            revision: $this->revision,
        );
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'source_type' => $this->sourceType,
            'confidence' => $this->confidence,
            'confirmed' => $this->confirmed,
            'risk' => $this->risk,
            'revision' => $this->revision,
        ];
    }
}
