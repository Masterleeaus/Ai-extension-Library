<?php

declare(strict_types=1);

namespace App\Extensions\AIChatPro\System\Integration;

use App\Domains\WorkCore\System\Authorization\CompanyRecordAuthorizer;
use App\Domains\WorkCore\System\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

/**
 * Integrates WorkCore Shared Foundation with AiChatPro.
 * Provides tenant isolation, authorization, and governed actions.
 */
final class WorkCoreIntegrationProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind WorkCore TenantContext as singleton for AiChatPro
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());

        // Bind authorization service for AiChatPro operations
        $this->app->singleton(CompanyRecordAuthorizer::class, fn ($app) => new CompanyRecordAuthorizer(
            $app->make(TenantContext::class),
        ));

        // Register AiChatPro WorkCore query repositories
        $this->registerQueryRepositories();

        // Register AiChatPro WorkCore event listeners
        $this->registerEventListeners();
    }

    public function boot(): void
    {
        // Middleware for tenant context resolution
        $this->app['router']->middleware('workcore.tenant', TenantContextMiddleware::class);

        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/workcore-aichatpro.php' => config_path('workcore-aichatpro.php'),
        ], 'workcore-aichatpro');
    }

    private function registerQueryRepositories(): void
    {
        // Placeholder for registering WorkCore query repositories
        // These will be populated as specific WorkCore modules are integrated
    }

    private function registerEventListeners(): void
    {
        // Placeholder for registering WorkCore domain event listeners
        // AiChatPro will listen to WorkCore events for real-time updates
    }
}
