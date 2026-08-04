<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Registry;

use App\Domains\WorkCore\System\Actions\ActionDefinition;
use App\Domains\WorkCore\System\Actions\BusinessActionRegistry;
use App\Services\AI\Integration\WorkCore\Actions\ConfiguredWorkCoreActionHandler;

final class ConfiguredTitanAppActionRegistrar
{
    public function __construct(private readonly BusinessActionRegistry $actions) {}

    public function register(): void
    {
        foreach ($this->actionTools() as $key) {
            if ($this->actions->has($key)) continue;
            $confirmation = $this->requiresConfirmation($key);
            $this->actions->register(new ActionDefinition(
                key: $key,
                handler: ConfiguredWorkCoreActionHandler::class,
                risk: $confirmation ? 'high' : 'medium',
                requiresConfirmation: $confirmation,
                capability: 'workcore.ai',
                permission: 'workcore.ai.execute_tools',
                metadata: ['source' => 'titan-app-manifest'],
            ));
        }
    }

    /** @return list<string> */
    private function actionTools(): array
    {
        $directory = dirname(__DIR__, 3).'/WorkCoreApps/config';
        $tools = [];
        foreach (glob($directory.'/*-workcore.json') ?: [] as $file) {
            $manifest = json_decode((string) file_get_contents($file), true);
            foreach ((array) ($manifest['workcore']['action_tools'] ?? []) as $tool) $tools[(string) $tool] = true;
        }
        return array_keys($tools);
    }

    private function requiresConfirmation(string $key): bool
    {
        foreach (['payment', 'invoice.send', 'quote.send', 'cancel', 'approve', 'schedule', 'adjust', 'transfer'] as $needle) {
            if (str_contains($key, $needle)) return true;
        }
        return false;
    }
}
