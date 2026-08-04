<?php

declare(strict_types=1);

namespace App\Extensions\VerticalCustomization\System\Branding;

use Illuminate\Support\ServiceProvider;

/**
 * Vertical Customization: Branding & Theming Framework
 * Issue #209: White-Label Customization
 *
 * Visual theme builder, color schemes, logos, fonts, and white-label support.
 */
final class BrandingCustomizationProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register theme builder service
        // Register color palette manager
        // Register font/typography service
        // Register white-label mode service
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/branding.php' => config_path('branding.php'),
        ], 'vertical-customization');

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
