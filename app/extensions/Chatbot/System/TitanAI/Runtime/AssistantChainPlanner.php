<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use RuntimeException;

/** Plans an auditable manager -> assistant -> agent chain with fallbacks. */
final class AssistantChainPlanner
{
    public function __construct(
        private readonly WorkerRouteRegistry $workers,
        private readonly DynamicWorkerSelector $selector,
        private readonly AssistantDelegationGraph $delegations,
        private readonly ConfidenceFallbackPolicy $fallbacks,
    ) {}

    /** @param list<string> $terms @return array<string,mixed> */
    public function plan(IntentDecision $decision, array $terms): array
    {
        $manager = $this->selector->select(1, $terms, $decision->manager);
        $assistant = $this->selector->select(2, $terms, $decision->assistant);
        if ($manager === null || $assistant === null) {
            throw new RuntimeException('Titan AI could not select a complete manager/assistant chain.');
        }

        $agent = $decision->agent !== null && $this->delegations->canDelegate($assistant, $decision->agent)
            ? $this->workers->canonicalId($decision->agent)
            : null;

        $agentFallbacks = $this->delegations->agentsFor($assistant, $terms, 5);
        if ($agent === null) $agent = $agentFallbacks[0] ?? $this->selector->select(3, $terms, $decision->agent);
        if ($agent === null) throw new RuntimeException("Assistant [{$assistant}] has no reachable Tier 3 agent.");

        $assistantFallbacks = $this->rankTier(2, $terms, $assistant, 3);
        $agentFallbacks = array_values(array_filter($agentFallbacks, static fn (string $id): bool => $id !== $agent));

        return [
            'manager' => $manager,
            'assistant' => $assistant,
            'agent' => $agent,
            'assistant_fallbacks' => $assistantFallbacks,
            'agent_fallbacks' => $agentFallbacks,
            'confidence' => $decision->confidence,
            'fallback_required' => [
                'manager' => $this->fallbacks->requiresFallback($decision->confidence, 1),
                'assistant' => $this->fallbacks->requiresFallback($decision->confidence, 2),
                'agent' => $this->fallbacks->requiresFallback($decision->confidence, 3),
            ],
            'manual_review_recommended' => $this->fallbacks->requiresReview($decision->confidence, $decision->riskLevel),
        ];
    }

    /** @param list<string> $terms @return list<string> */
    private function rankTier(int $tier, array $terms, string $exclude, int $limit): array
    {
        $ranked = [];
        foreach ($this->workers->all() as $id => $class) {
            if ($id === $exclude || $this->workers->tierFor($id) !== $tier) continue;
            $definition = $this->workers->definitionFor($id) ?? [];
            $haystack = strtolower(json_encode($definition, JSON_UNESCAPED_SLASHES) ?: '');
            $score = 0;
            foreach ($terms as $term) {
                $term = strtolower(trim($term));
                if ($term !== '' && str_contains($haystack, $term)) $score++;
            }
            if ($score > 0) $ranked[$id] = $score;
        }
        arsort($ranked);
        return array_slice(array_keys($ranked), 0, $limit);
    }
}
