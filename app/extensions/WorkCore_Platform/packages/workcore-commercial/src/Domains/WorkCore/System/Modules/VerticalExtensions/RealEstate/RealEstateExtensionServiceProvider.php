<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\RealEstate;

use Illuminate\Support\ServiceProvider;

class RealEstateExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services
    }

    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerConfig();
    }

    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }

    private function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('verticals/real-estate.php'),
        ], 'real-estate-config');
    }
}
