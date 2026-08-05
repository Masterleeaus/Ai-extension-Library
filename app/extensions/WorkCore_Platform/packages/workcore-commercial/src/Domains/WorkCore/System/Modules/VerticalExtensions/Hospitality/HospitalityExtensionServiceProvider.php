<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Services\ChannelSyncService;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\Hospitality\Services\DynamicPricingService;
use Illuminate\Support\ServiceProvider;

class HospitalityExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelSyncService::class, function () {
            return new ChannelSyncService();
        });

        $this->app->singleton(DynamicPricingService::class, function () {
            return new DynamicPricingService();
        });
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
            __DIR__ . '/config.php' => config_path('verticals/hospitality.php'),
        ], 'hospitality-config');
    }
}
