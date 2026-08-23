<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\AI;

final class DeterministicVerticalAIProposalFallback
{
    public function create(
        string $contextHash,
        string $providerId,
        string $modelId,
        int $attempts,
        string $promptHash,
        string $responseHash,
        array $validationErrors,
    ): VerticalAIProposal {
        return VerticalAIProposal::create(
            updates: [
                'terminology' => [],
                'context' => [],
                'questions' => [],
            ],
            confidence: 0.0,
            rationale: 'AI proposal unavailable; no changes proposed.',
            provenance: [[
                'source' => 'system.vertical_ai_fallback',
                'path' => '/',
                'reason' => 'Validation or provider execution did not produce an approved proposal.',
            ]],
            contextHash: $contextHash,
            providerId: $providerId,
            modelId: $modelId,
            attempts: $attempts,
            fallbackUsed: true,
            audit: [
                'schema_version' => '1.0.0',
                'prompt_hash' => $promptHash,
                'response_hash' => $responseHash,
                'validation_errors' => array_values($validationErrors),
            ],
        );
    }
}
