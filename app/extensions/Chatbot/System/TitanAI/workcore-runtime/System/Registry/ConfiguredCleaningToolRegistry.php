<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Registry;

use App\Domains\WorkCore\System\AI\Tools\{AiToolDefinition,DomainToolRegistryContract};
use InvalidArgumentException;

final class ConfiguredCleaningToolRegistry implements DomainToolRegistryContract
{
    /** @return list<AiToolDefinition> */
    public function registerTools(): array
    {
        $tools = [];
        foreach ((array) config('titan_ai_runtime.cleaning_tools', []) as $definition) {
            if (! is_array($definition)) {
                continue;
            }
            $name = (string) ($definition['name'] ?? '');
            $target = (string) ($definition['target'] ?? '');
            if (! $this->isCleaningKey($name) || ! $this->isCleaningKey($target)) {
                throw new InvalidArgumentException("Non-cleaning AI tool [{$name}] cannot be registered in this package.");
            }
            $tools[] = new AiToolDefinition(
                name: $name,
                description: (string) ($definition['description'] ?? $name),
                kind: (string) ($definition['kind'] ?? 'read_model'),
                target: $target,
                inputSchema: (array) ($definition['input_schema'] ?? ['type' => 'object', 'properties' => []]),
                outputSchema: (array) ($definition['output_schema'] ?? []),
                risk: (string) ($definition['risk'] ?? 'low'),
                requiresConfirmation: (bool) ($definition['requires_confirmation'] ?? false),
                dataClassification: (string) ($definition['data_classification'] ?? 'internal'),
                channels: array_values((array) ($definition['channels'] ?? ['internal'])),
                agents: array_values((array) ($definition['agents'] ?? ['*'])),
                enabled: (bool) ($definition['enabled'] ?? true),
                metadata: ['vertical' => 'cleaning'] + (array) ($definition['metadata'] ?? []),
            );
        }
        return $tools;
    }

    private function isCleaningKey(string $key): bool
    {
        foreach (['cleaning.', 'residential_cleaning.', 'commercial_cleaning.', 'pool_cleaning.', 'pressure_washing.', 'car_detailing.'] as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
