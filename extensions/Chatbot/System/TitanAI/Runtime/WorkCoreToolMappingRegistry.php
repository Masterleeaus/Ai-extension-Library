<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use RuntimeException;

/**
 * Authoritative view of Titan AI operation -> native WorkCore tool mappings.
 * It never invents missing tools: unsupported operations fail closed.
 */
final class WorkCoreToolMappingRegistry
{
    /** @return array<string,string> */
    public function all(): array
    {
        $map = (array) config('titan_ai_runtime.operation_tool_map', []);
        $normalised = [];
        foreach ($map as $operation => $tool) {
            if (! is_string($operation) || trim($operation) === '') continue;
            if (! is_string($tool) || trim($tool) === '') continue;
            $normalised[trim($operation)] = trim($tool);
        }
        ksort($normalised);
        return $normalised;
    }

    public function toolFor(string $domain, string $operation): string
    {
        $key = $this->key($domain, $operation);
        $tool = $this->all()[$key] ?? null;
        if ($tool === null) {
            throw new RuntimeException("No native WorkCore AI tool is mapped for [{$key}].");
        }
        return $tool;
    }

    public function has(string $domain, string $operation): bool
    {
        return isset($this->all()[$this->key($domain, $operation)]);
    }

    /** @return array{mapped:int,duplicates:array<string,list<string>>,invalid:list<string>} */
    public function diagnostics(): array
    {
        $byTool = [];
        $invalid = [];
        foreach ($this->all() as $operation => $tool) {
            if (substr_count($operation, '.') < 1) $invalid[] = $operation;
            $byTool[$tool][] = $operation;
        }
        $duplicates = array_filter($byTool, static fn (array $operations): bool => count($operations) > 1);
        return ['mapped' => count($this->all()), 'duplicates' => $duplicates, 'invalid' => $invalid];
    }

    private function key(string $domain, string $operation): string
    {
        $domain = trim($domain);
        $operation = trim($operation);
        if ($domain === '' || $operation === '') {
            throw new RuntimeException('WorkCore tool mapping requires non-empty domain and operation values.');
        }
        return $domain.'.'.$operation;
    }
}
