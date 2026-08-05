<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\AI;

final readonly class VerticalAIProposal
{
    private function __construct(
        private string $proposalId,
        private array $updates,
        private float $confidence,
        private string $rationale,
        private array $provenance,
        private string $contextHash,
        private string $providerId,
        private string $modelId,
        private int $attempts,
        private bool $fallbackUsed,
        private array $audit,
    ) {}

    public static function create(
        array $updates,
        float $confidence,
        string $rationale,
        array $provenance,
        string $contextHash,
        string $providerId,
        string $modelId,
        int $attempts,
        bool $fallbackUsed,
        array $audit,
    ): self {
        $identity = self::canonicalize([
            'schema_version' => '1.0.0',
            'updates' => $updates,
            'confidence' => $confidence,
            'rationale' => $rationale,
            'provenance' => $provenance,
            'context_hash' => $contextHash,
            'provider_id' => $providerId,
            'model_id' => $modelId,
            'attempts' => $attempts,
            'fallback_used' => $fallbackUsed,
            'audit' => $audit,
        ]);

        return new self(
            proposalId: hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
            updates: $updates,
            confidence: $confidence,
            rationale: $rationale,
            provenance: $provenance,
            contextHash: $contextHash,
            providerId: $providerId,
            modelId: $modelId,
            attempts: $attempts,
            fallbackUsed: $fallbackUsed,
            audit: $audit,
        );
    }

    public function toArray(): array
    {
        return [
            'schema_version' => '1.0.0',
            'proposal_id' => $this->proposalId,
            'updates' => $this->updates,
            'confidence' => $this->confidence,
            'rationale' => $this->rationale,
            'provenance' => $this->provenance,
            'context_hash' => $this->contextHash,
            'provider_id' => $this->providerId,
            'model_id' => $this->modelId,
            'attempts' => $this->attempts,
            'fallback_used' => $this->fallbackUsed,
            'audit' => $this->audit,
        ];
    }

    private static function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(self::canonicalize(...), $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalize($item);
        }

        return $value;
    }
}
