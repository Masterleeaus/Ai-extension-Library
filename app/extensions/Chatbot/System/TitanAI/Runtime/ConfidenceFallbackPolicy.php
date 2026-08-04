<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

final class ConfidenceFallbackPolicy
{
    public function __construct(
        private readonly float $minimumManagerConfidence = 0.55,
        private readonly float $minimumAssistantConfidence = 0.65,
        private readonly float $minimumAgentConfidence = 0.72,
    ) {}

    public function requiresFallback(float $confidence, int $tier): bool
    {
        $threshold = match ($tier) {
            1 => $this->minimumManagerConfidence,
            2 => $this->minimumAssistantConfidence,
            3 => $this->minimumAgentConfidence,
            default => 1.0,
        };
        return $confidence < $threshold;
    }

    public function requiresReview(float $confidence, string $riskLevel): bool
    {
        return in_array(strtolower($riskLevel), ['red', 'critical'], true) && $confidence < 0.90;
    }

    /** @return array<string,float> */
    public function thresholds(): array
    {
        return [
            'manager' => $this->minimumManagerConfidence,
            'assistant' => $this->minimumAssistantConfidence,
            'agent' => $this->minimumAgentConfidence,
        ];
    }
}
