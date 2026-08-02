<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

/**
 * Selects registered workers by tier, domain and capability evidence.
 * It never invents worker IDs and only returns classes present in WorkerRouteRegistry.
 */
final class DynamicWorkerSelector
{
    public function __construct(private readonly WorkerRouteRegistry $workers) {}

    /** @param list<string> $terms */
    public function select(int $tier, array $terms, ?string $preferredId = null): ?string
    {
        if ($preferredId !== null && $preferredId !== '' && $this->workers->tierFor($preferredId) === $tier) {
            return $this->workers->canonicalId($preferredId);
        }

        $terms = array_values(array_filter(array_map(
            static fn (string $term): string => strtolower(trim($term)),
            $terms,
        )));

        $bestId = null;
        $bestScore = 0;
        foreach ($this->workers->all() as $id => $class) {
            if ($this->workers->tierFor($id) !== $tier) continue;
            $definition = $this->workers->definitionFor($id) ?? [];
            $haystack = strtolower(implode(' ', $this->flatten($definition)));
            $score = 0;
            foreach ($terms as $term) {
                if ($term !== '' && str_contains($haystack, $term)) $score++;
            }
            if ($score > $bestScore || ($score === $bestScore && $score > 0 && ($bestId === null || strcmp($id, $bestId) < 0))) {
                $bestId = $id;
                $bestScore = $score;
            }
        }

        return $bestId;
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
}
