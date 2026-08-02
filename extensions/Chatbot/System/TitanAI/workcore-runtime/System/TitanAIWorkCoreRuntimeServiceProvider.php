<?php

declare(strict_types=1);

namespace App\Services\AI\Integration\WorkCore;

use App\Domains\WorkCore\System\AI\Contracts\CredentialResolverContract;
use App\Services\AI\Integration\WorkCore\Registry\{ConfiguredCleaningActionRegistrar,ConfiguredCleaningToolRegistry};
use App\Services\AI\Integration\WorkCore\Security\TitanVaultCredentialResolver;
use Illuminate\Support\ServiceProvider;

final class TitanAIWorkCoreRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/titan_ai_runtime.php', 'titan_ai_runtime');

        $registries = array_values(array_unique(array_merge(
            (array) config('workcore_ai.tool_registries', []),
            [ConfiguredCleaningToolRegistry::class],
        )));
        config(['workcore_ai.tool_registries' => $registries]);
        config(['workcore_ai.credential_resolver' => TitanVaultCredentialResolver::class]);

        $this->app->singleton(WorkerModelRequestFactory::class);
        $this->app->scoped(WorkCoreRuntimeClient::class);
        $this->app->singleton(ConfiguredCleaningActionRegistrar::class);
        $this->app->bind(CredentialResolverContract::class, TitanVaultCredentialResolver::class);
    }

    public function boot(ConfiguredCleaningActionRegistrar $actions): void
    {
        $actions->register();
        $this->publishes([
            __DIR__ . '/../config/titan_ai_runtime.php' => config_path('titan_ai_runtime.php'),
        ], 'titan-ai-runtime-config');
    }
}
