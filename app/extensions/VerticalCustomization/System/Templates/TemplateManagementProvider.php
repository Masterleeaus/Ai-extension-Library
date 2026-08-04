<?php

declare(strict_types=1);

namespace App\Extensions\VerticalCustomization\System\Templates;

use Illuminate\Support\ServiceProvider;

/**
 * Vertical Customization: Template Management Framework
 * Issue #206: Domain-Specific Templates
 *
 * Enables each vertical to define templates for responses, documents,
 * forms, and communications without code changes.
 */
final class TemplateManagementProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register template repository
        // Register template composition service
        // Register document generation service
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/template-management.php' => config_path('template-management.php'),
        ], 'vertical-customization');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
