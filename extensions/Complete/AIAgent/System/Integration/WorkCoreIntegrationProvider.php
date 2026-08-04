<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Integration;

use App\Domains\WorkCore\System\Authorization\CompanyRecordAuthorizer;
use App\Domains\WorkCore\System\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

/**
 * Integrates WorkCore Shared Foundation with AIAgent autonomous operations.
 * Provides tenant isolation, authorized actions, governed workflows, and audit trails.
 */
final class WorkCoreIntegrationProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind WorkCore TenantContext as singleton for AIAgent operations
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());

        // Bind authorization service for AIAgent actions
        $this->app->singleton(CompanyRecordAuthorizer::class, fn ($app) => new CompanyRecordAuthorizer(
            $app->make(TenantContext::class),
        ));

        // Register AIAgent WorkCore action handlers
        $this->registerActionHandlers();

        // Register AIAgent WorkCore event publishers
        $this->registerEventPublishers();

        // Register AIAgent approval workflow engine
        $this->registerApprovalWorkflows();
    }

    public function boot(): void
    {
        // Middleware for tenant context resolution in agent workers
        $this->app['router']->middleware('workcore.tenant', TenantContextMiddleware::class);

        // Agent-specific queue configuration for isolated operations
        $this->publishConfiguration();

        // Register agent command for autonomous operations
        if ($this->app->runningInConsole()) {
            $this->commands([
                AutonomousAgentCommand::class,
            ]);
        }
    }

    private function registerActionHandlers(): void
    {
        // Placeholder for AIAgent action handlers that interact with WorkCore
        // These handlers will execute WorkCore operations with proper governance
    }

    private function registerEventPublishers(): void
    {
        // Placeholder for AIAgent event publishers
        // AIAgent will publish events for autonomous actions
    }

    private function registerApprovalWorkflows(): void
    {
        // Placeholder for approval workflow engine
        // Risky actions will require approval before execution
    }

    private function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__ . '/config/workcore-aiagent.php' => config_path('workcore-aiagent.php'),
        ], 'workcore-aiagent');
    }
}
