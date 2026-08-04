<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\AI\Providers;

use App\Domains\WorkCore\System\AI\Contracts\{AiRunRecorderContract,AiToolRunRecorderContract,CredentialResolverContract,ModelProfileResolverContract};
use App\Domains\WorkCore\System\AI\Persistence\{DatabaseAiRunRecorder,DatabaseAiToolRunRecorder,DatabaseModelProfileResolver};
use App\Domains\WorkCore\System\AI\Policies\{AiBudgetGuard,AiRateLimiter,ProviderCircuitBreaker};
use App\Domains\WorkCore\System\AI\Security\{NativeAiAccessGate,SensitiveDataRedactor};
use App\Domains\WorkCore\System\AI\Tools\{AiToolRegistry,AiToolRouter,JsonSchemaValidator,NativeToolCatalogue,WorkCoreActionToolExecutor,WorkCoreReadToolExecutor};
use App\Domains\WorkCore\System\Actions\BusinessActionRegistry;
use App\Domains\WorkCore\System\Actions\Contracts\EntitlementResolverContract;
use App\Domains\WorkCore\System\Contracts\PermissionResolverContract;
use App\Domains\WorkCore\System\ReadModels\ReadModelRegistry;
use Illuminate\Support\ServiceProvider;

final class WorkCoreAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/workcore_ai.php', 'workcore_ai');
        if (! (bool) config('workcore_ai.enabled', true)) {
            return;
        }

        $this->app->singleton(SensitiveDataRedactor::class, static fn () => new SensitiveDataRedactor((array) config('workcore_ai.sensitive_keys', [])));
        $this->app->bind(CredentialResolverContract::class, (string) config('workcore_ai.credential_resolver'));
        $this->app->bind(ModelProfileResolverContract::class, DatabaseModelProfileResolver::class);
        $this->app->bind(AiRunRecorderContract::class, DatabaseAiRunRecorder::class);
        $this->app->bind(AiToolRunRecorderContract::class, DatabaseAiToolRunRecorder::class);
        $this->app->singleton(NativeAiAccessGate::class, fn ($app) => new NativeAiAccessGate(
            $app->make(\App\Domains\WorkCore\System\Contracts\TenantContextContract::class),
            $app->make(\App\Domains\WorkCore\System\Contracts\OperationContextContract::class),
            $app->make(EntitlementResolverContract::class),
            $app->make(PermissionResolverContract::class),
            (string) config('workcore_ai.access.capability', 'workcore.ai'),
            (string) config('workcore_ai.access.use_permission', 'workcore.ai.use'),
            (string) config('workcore_ai.access.tool_permission', 'workcore.ai.execute_tools'),
        ));
        $this->app->singleton(JsonSchemaValidator::class);
        $this->app->singleton(AiToolRegistry::class);
        $this->app->singleton(ModelProviderFactory::class);
        $this->app->singleton(AiRateLimiter::class);
        $this->app->singleton(AiBudgetGuard::class);
        $this->app->singleton(ProviderCircuitBreaker::class);
        $this->app->singleton(NativeToolCatalogue::class, fn ($app) => new NativeToolCatalogue(
            $app,
            $app->make(BusinessActionRegistry::class),
            $app->make(ReadModelRegistry::class),
            $app->make(EntitlementResolverContract::class),
            $app->make(PermissionResolverContract::class),
            $app->make(NativeAiAccessGate::class),
            $app->make(AiToolRegistry::class),
            array_values((array) config('workcore_ai.tool_registries', [])),
        ));
        $this->app->scoped(WorkCoreActionToolExecutor::class);
        $this->app->scoped(WorkCoreReadToolExecutor::class);
        $this->app->scoped(AiToolRouter::class);
        $this->app->scoped(NativeModelGateway::class);
    }

    public function boot(): void
    {
        if (! (bool) config('workcore_ai.enabled', true)) {
            return;
        }
        $this->publishes([
            __DIR__ . '/../../../config/workcore_ai.php' => config_path('workcore_ai.php'),
        ], 'workcore-ai-config');
    }
}
