<?php

declare(strict_types=1);

namespace App\Extensions\VerticalCustomization\System\Localization;

use Illuminate\Support\ServiceProvider;

/**
 * Vertical Customization: Localization Framework
 * Issue #208: Language, Region & Cultural Adaptation
 *
 * Multi-language support, regional pricing, RTL languages, and cultural awareness.
 */
final class LocalizationProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register translation manager
        // Register regional pricing service
        // Register cultural calendar service
        // Register RTL support service
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/localization.php' => config_path('localization.php'),
        ], 'vertical-customization');

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
