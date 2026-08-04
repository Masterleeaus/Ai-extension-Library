<?php

declare(strict_types=1);

namespace App\Extensions\VerticalCustomization\System;

use App\Extensions\VerticalCustomization\System\Branding\BrandingCustomizationProvider;
use App\Extensions\VerticalCustomization\System\Behavior\BehaviorCustomizationProvider;
use App\Extensions\VerticalCustomization\System\Forms\FormsBuilderProvider;
use App\Extensions\VerticalCustomization\System\Localization\LocalizationProvider;
use App\Extensions\VerticalCustomization\System\Prompts\PromptCustomizationProvider;
use App\Extensions\VerticalCustomization\System\Templates\TemplateManagementProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Vertical Customization Framework
 * Issues #205-210: Domain-Specific Customization for All Verticals
 *
 * Enables AiChatPro, Chatbot, and AIAgent to be customized per vertical
 * without code changes through visual builders and configuration frameworks.
 */
final class VerticalCustomizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register all customization frameworks
        $this->app->register(PromptCustomizationProvider::class);      // #205
        $this->app->register(TemplateManagementProvider::class);       // #206
        $this->app->register(FormsBuilderProvider::class);             // #207
        $this->app->register(LocalizationProvider::class);             // #208
        $this->app->register(BrandingCustomizationProvider::class);    // #209
        $this->app->register(BehaviorCustomizationProvider::class);    // #210
    }

    public function boot(): void
    {
        // Publish shared customization configuration
        $this->publishes([
            __DIR__ . '/config/vertical-customization.php' => config_path('vertical-customization.php'),
        ], 'vertical-customization');

        // Load migrations for all customization modules
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        // Register UI routes for customization builders
        $this->registerCustomizationRoutes();
    }

    private function registerCustomizationRoutes(): void
    {
        // Routes for prompt builder
        // Routes for template builder
        // Routes for form builder
        // Routes for localization manager
        // Routes for branding customizer
        // Routes for behavior configuration
    }
}
