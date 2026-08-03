<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Registry;

use App\Domains\WorkCore\System\ReadModels\ReadModelDefinition;
use App\Domains\WorkCore\System\ReadModels\ReadModelRegistry;
use App\Services\AI\Integration\WorkCore\Reads\ConfiguredWorkCoreReadHandler;

final class ConfiguredTitanAppReadRegistrar
{
    public function __construct(private readonly ReadModelRegistry $reads) {}

    public function register(): void
    {
        foreach ($this->readTools() as $key) {
            if ($this->reads->has($key)) continue;
            $this->reads->register(new ReadModelDefinition(
                key: $key,
                handler: ConfiguredWorkCoreReadHandler::class,
                capability: 'workcore.ai',
                metadata: ['method' => 'execute', 'read_model' => $key, 'source' => 'titan-app-manifest'],
                permission: 'workcore.ai.execute_tools',
            ));
        }
    }

    /** @return list<string> */
    private function readTools(): array
    {
        $directory = dirname(__DIR__, 3).'/WorkCoreApps/config';
        $tools = [];
        foreach (glob($directory.'/*-workcore.json') ?: [] as $file) {
            $manifest = json_decode((string) file_get_contents($file), true);
            foreach ((array) ($manifest['workcore']['read_tools'] ?? []) as $tool) $tools[(string) $tool] = true;
        }
        return array_keys($tools);
    }
}
