<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System;

use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\Migration\System\Connectors\ConnectorRegistry;
use App\Extensions\Migration\System\Connectors\Legacy\LegacyDavinciConnector;
use App\Extensions\Migration\System\Drivers\DavinciDriver;
use App\Extensions\Migration\System\Enums\MigrationDriverEnum;
use App\Extensions\Migration\System\Http\Controllers\MigrationController;
use App\Extensions\Migration\System\Models\MigrationProject;
use App\Extensions\Migration\System\Policies\MigrationProjectPolicy;
use App\Extensions\Migration\System\Services\MigrationService;
use App\Extensions\Migration\System\Tenancy\MigrationTenantContext;
use App\Extensions\Migration\System\Tenancy\MigrationTenantResolver;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider implements UninstallExtensionServiceProviderInterface
{
    public function register(): void
    {
        $this->registerConfig()->registerServices();
    }

    public function boot(Kernel $kernel): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->publishAssets()
            ->registerComponents();
    }

    public function registerComponents(): static
    {
        Gate::policy(MigrationProject::class, MigrationProjectPolicy::class);

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([], 'extension');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/migration.php', 'migration');

        return $this;
    }

    public function registerServices(): static
    {
        $this->app->singleton(MigrationTenantContext::class, static fn () => new MigrationTenantContext());
        $this->app->singleton(MigrationTenantResolver::class, static function ($app) {
            return new MigrationTenantResolver($app->make(MigrationTenantContext::class));
        });

        $this->app->singleton(ConnectorRegistry::class, function ($app) {
            $registry = new ConnectorRegistry();
            $registry->register(new LegacyDavinciConnector($app->make(DavinciDriver::class)));

            return $registry;
        });

        $this->app->singleton(MigrationService::class, function ($app) {
            $drivers = collect(MigrationDriverEnum::cases())
                ->map(function (MigrationDriverEnum $driver) {
                    return app($driver->driver());
                })->toArray();

            return new MigrationService($drivers);
        });

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'migration');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'migration');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()->group([
            'middleware' => ['web', 'auth'],
            'as' => 'migration::',
            'prefix' => 'dashboard/admin/migration',
            'controller' => MigrationController::class,
        ], function (Router $router) {
            $router->get('/', 'index')->name('welcome');
            $router->get('/start', 'start')->name('start');
            $router->post('/migrate', 'migrate')->name('migrate');
        });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public static function uninstall(): void
    {
    }
}
