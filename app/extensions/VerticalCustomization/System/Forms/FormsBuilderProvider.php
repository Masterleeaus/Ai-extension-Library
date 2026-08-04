<?php

declare(strict_types=1);

namespace App\Extensions\VerticalCustomization\System\Forms;

use Illuminate\Support\ServiceProvider;

/**
 * Vertical Customization: Forms Builder Framework
 * Issue #207: Domain-Specific Data Collection
 *
 * Drag-and-drop form builder for each vertical without coding.
 */
final class FormsBuilderProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register form builder service
        // Register form validation service
        // Register conditional logic engine
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/forms-builder.php' => config_path('forms-builder.php'),
        ], 'vertical-customization');

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
