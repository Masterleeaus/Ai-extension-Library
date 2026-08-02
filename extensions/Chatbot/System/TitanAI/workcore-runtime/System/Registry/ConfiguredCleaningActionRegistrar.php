<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore\Registry;

use App\Domains\WorkCore\System\Actions\ActionDefinition;
use App\Domains\WorkCore\System\Actions\BusinessActionRegistry;
use App\Services\AI\Integration\WorkCore\Actions\ConfiguredWorkCoreActionHandler;

final class ConfiguredCleaningActionRegistrar
{
    public function __construct(private readonly BusinessActionRegistry $actions) {}

    public function register(): void
    {
        foreach ((array) config('titan_ai_runtime.cleaning_actions', []) as $key => $definition) {
            if (! is_string($key) || $key === '' || ! is_array($definition)) {
                continue;
            }
            $this->actions->register(new ActionDefinition(
                key: $key,
                handler: ConfiguredWorkCoreActionHandler::class,
                risk: (string) ($definition['risk'] ?? 'medium'),
                requiresConfirmation: (bool) ($definition['requires_confirmation'] ?? false),
                capability: isset($definition['capability']) ? (string) $definition['capability'] : null,
                permission: isset($definition['permission']) ? (string) $definition['permission'] : null,
                metadata: ['vertical' => 'cleaning'] + (array) ($definition['metadata'] ?? []),
            ));
        }
    }
}
