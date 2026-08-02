<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

/**
 * Builds an explicit, registry-backed Tier 2 -> Tier 3 delegation graph.
 * Edges are derived from shared domain, operation, capability and permission terms;
 * no unregistered worker ID can enter the graph.
 */
final class AssistantDelegationGraph
{
    /** @var array<string,list<string>>|null */
    private ?array $graph = null;

    public function __construct(private readonly WorkerRouteRegistry $workers) {}

    /** @return list<string> */
    public function agentsFor(string $assistantId, array $terms = [], int $limit = 8): array
    {
        $assistantId = $this->workers->canonicalId($assistantId);
        $candidates = $this->all()[$assistantId] ?? [];
        if ($terms === []) return array_slice($candidates, 0, max(1, $limit));

        $terms = $this->normaliseTerms($terms);
        $scored = [];
        foreach ($candidates as $agentId) {
            $definition = $this->workers->definitionFor($agentId) ?? [];
            $score = $this->score($definition, $terms);
            if ($score > 0) $scored[$agentId] = $score;
        }
        arsort($scored);
        return array_slice(array_keys($scored), 0, max(1, $limit));
    }

    public function canDelegate(string $assistantId, string $agentId): bool
    {
        $assistantId = $this->workers->canonicalId($assistantId);
        $agentId = $this->workers->canonicalId($agentId);
        return in_array($agentId, $this->all()[$assistantId] ?? [], true);
    }

    /** @return array<string,list<string>> */
    public function all(): array
    {
        if ($this->graph !== null) return $this->graph;

        $assistants = [];
        $agents = [];
        foreach ($this->workers->all() as $id => $class) {
            $tier = $this->workers->tierFor($id);
            if ($tier === 2) $assistants[$id] = $this->workers->definitionFor($id) ?? [];
            if ($tier === 3) $agents[$id] = $this->workers->definitionFor($id) ?? [];
        }

        $graph = [];
        foreach ($assistants as $assistantId => $assistantDefinition) {
            $assistantTerms = $this->normaliseTerms($this->flatten($assistantDefinition));
            $scored = [];
            foreach ($agents as $agentId => $agentDefinition) {
                $score = $this->score($agentDefinition, $assistantTerms);
                if ($score > 0) $scored[$agentId] = $score;
            }
            arsort($scored);
            $graph[$assistantId] = array_slice(array_keys($scored), 0, 12);
        }

        ksort($graph);
        return $this->graph = $graph;
    }

    /** @return array<string,mixed> */
    public function diagnostics(): array
    {
        $graph = $this->all();
        $empty = array_keys(array_filter($graph, static fn (array $agents): bool => $agents === []));
        $invalid = [];
        foreach ($graph as $assistantId => $agents) {
            foreach ($agents as $agentId) {
                if ($this->workers->tierFor($assistantId) !== 2 || $this->workers->tierFor($agentId) !== 3) {
                    $invalid[] = ['assistant' => $assistantId, 'agent' => $agentId];
                }
            }
        }
        return [
            'assistant_count' => count($graph),
            'edge_count' => array_sum(array_map('count', $graph)),
            'assistants_without_agents' => $empty,
            'invalid_edges' => $invalid,
            'healthy' => $empty === [] && $invalid === [],
        ];
    }

    /** @param array<string,mixed> $definition @param list<string> $terms */
    private function score(array $definition, array $terms): int
    {
        $haystack = strtolower(implode(' ', $this->flatten($definition)));
        $score = 0;
        foreach ($terms as $term) {
            if ($term !== '' && str_contains($haystack, $term)) $score++;
        }
        return $score;
    }

    /** @return list<string> */
    private function flatten(mixed $value): array
    {
        if (is_scalar($value)) return [(string) $value];
        if (! is_array($value)) return [];
        $result = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) $result[] = $key;
            array_push($result, ...$this->flatten($item));
        }
        return $result;
    }

    /** @param array<int,mixed> $terms @return list<string> */
    private function normaliseTerms(array $terms): array
    {
        $normalised = [];
        foreach ($terms as $term) {
            if (! is_scalar($term)) continue;
            foreach (preg_split('/[^a-z0-9]+/i', strtolower((string) $term)) ?: [] as $token) {
                if (strlen($token) >= 3) $normalised[$token] = true;
            }
        }
        return array_keys($normalised);
    }
}
