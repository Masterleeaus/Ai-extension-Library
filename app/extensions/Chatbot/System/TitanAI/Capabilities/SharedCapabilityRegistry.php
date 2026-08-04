<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Capabilities;

final class SharedCapabilityRegistry
{
    /** @return array<int,array<string,mixed>> */
    public function all(array $deploymentCapabilities = []): array
    {
        $catalogue = (array) config('titan-ai.capabilities', []);
        $enabled = $deploymentCapabilities === [] ? array_keys($catalogue) : $deploymentCapabilities;

        return array_values(array_map(static function (string $id) use ($catalogue): array {
            $definition = (array) ($catalogue[$id] ?? []);
            return ['id' => $id, 'enabled' => true, ...$definition];
        }, array_values(array_unique(array_filter($enabled, 'is_string')))));
    }
}
