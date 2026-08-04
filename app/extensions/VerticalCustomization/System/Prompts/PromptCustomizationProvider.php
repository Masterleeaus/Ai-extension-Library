<?php

declare(strict_types=1);

namespace App\Extensions\VerticalCustomization\System\Prompts;

use Illuminate\Support\ServiceProvider;

/**
 * Vertical Customization: Prompt Customization Framework
 * Issue #205: Per-Vertical AI Prompts
 *
 * Allows each vertical to define, manage, and deploy custom AI prompts
 * without code changes through a templating and versioning system.
 */
final class PromptCustomizationProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register prompt template repository
        $this->app->singleton(PromptTemplateRepository::class);

        // Register prompt composition service
        $this->app->singleton(PromptComposer::class);

        // Register A/B testing framework
        $this->app->singleton(PromptABTestService::class);
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/prompt-customization.php' => config_path('prompt-customization.php'),
        ], 'vertical-customization');

        // Register routes for prompt management UI
        $this->registerRoutes();

        // Register database migrations
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    private function registerRoutes(): void
    {
        // Routes for prompt template CRUD
        // Routes for prompt versioning
        // Routes for A/B test management
        // Routes for performance analytics
    }
}
