<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Integration;

use App\Domains\WorkCore\System\Authorization\CompanyRecordAuthorizer;
use App\Domains\WorkCore\System\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

/**
 * Integrates WorkCore Shared Foundation with Chatbot PWA.
 * Provides tenant isolation, authorization, and real-time data binding.
 */
final class WorkCoreIntegrationProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind WorkCore TenantContext as singleton for Chatbot
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());

        // Bind authorization service for Chatbot operations
        $this->app->singleton(CompanyRecordAuthorizer::class, fn ($app) => new CompanyRecordAuthorizer(
            $app->make(TenantContext::class),
        ));

        // Register Chatbot WorkCore query repositories
        $this->registerQueryRepositories();

        // Register Chatbot WebSocket event listeners for real-time updates
        $this->registerRealtimeListeners();
    }

    public function boot(): void
    {
        // Middleware for tenant context resolution
        $this->app['router']->middleware('workcore.tenant', TenantContextMiddleware::class);

        // Publish PWA offline-first configuration
        $this->publishes([
            __DIR__ . '/config/workcore-chatbot.php' => config_path('workcore-chatbot.php'),
        ], 'workcore-chatbot');
    }

    private function registerQueryRepositories(): void
    {
        // Placeholder for registering WorkCore query repositories
        // Chatbot queries will be optimized for conversational context
    }

    private function registerRealtimeListeners(): void
    {
        // Placeholder for real-time updates via WebSocket
        // Chatbot will receive live updates from WorkCore modules
    }
}
