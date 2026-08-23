<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System;

use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\Migration\System\Canonical\BuiltinCanonicalEntityCatalog;
use App\Extensions\Migration\System\Canonical\CanonicalEntityRegistry;
use App\Extensions\Migration\System\Canonical\ModuleAvailabilityResolver;
use App\Extensions\Migration\System\Connectors\BuiltinConnectorCatalog;
use App\Extensions\Migration\System\Connectors\ConnectorRegistry;
use App\Extensions\Migration\System\Connectors\File\SqlDumpConnector;
use App\Extensions\Migration\System\Connectors\Legacy\LegacyDavinciConnector;
use App\Extensions\Migration\System\Destination\DestinationHandlerRegistry;
use App\Extensions\Migration\System\Drivers\DavinciDriver;
use App\Extensions\Migration\System\Enums\MigrationDriverEnum;
use App\Extensions\Migration\System\Http\Controllers\MigrationController;
use App\Extensions\Migration\System\Identity\CanonicalChecksum;
use App\Extensions\Migration\System\Identity\Contracts\ExternalIdRepositoryInterface;
use App\Extensions\Migration\System\Identity\EloquentExternalIdRepository;
use App\Extensions\Migration\System\Identity\ExternalIdMapService;
use App\Extensions\Migration\System\Mapping\MappingPreviewService;
use App\Extensions\Migration\System\Mapping\TransformationEngine;
use App\Extensions\Migration\System\Mapping\TransformationRegistry;
use App\Extensions\Migration\System\Matching\DuplicateResolver;
use App\Extensions\Migration\System\Models\MigrationProject;
use App\Extensions\Migration\System\Policies\MigrationProjectPolicy;
use App\Extensions\Migration\System\Security\SensitiveValueMasker;
use App\Extensions\Migration\System\Services\MigrationService;
use App\Extensions\Migration\System\Suggestions\AdvisoryMappingSuggestionService;
use App\Extensions\Migration\System\Suggestions\MappingSuggestionApprovalService;
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

        $this->app->singleton(BuiltinConnectorCatalog::class, static fn () => new BuiltinConnectorCatalog());
        $this->app->singleton(ConnectorRegistry::class, function ($app) {
            $registry = new ConnectorRegistry();
            $catalog = $app->make(BuiltinConnectorCatalog::class);

            foreach ($catalog->classes() as $connectorClass) {
                $registry->register($app->make($connectorClass));
            }

            $registry->register(new LegacyDavinciConnector(
                $app->make(DavinciDriver::class),
                $app->make(SqlDumpConnector::class),
            ));

            return $registry;
        });

        $this->app->singleton(ModuleAvailabilityResolver::class, function ($app) {
            return new ModuleAvailabilityResolver(
                modules: (array) config('migration.canonical.modules', []),
                detector: static function (string $module) use ($app): bool {
                    $markers = (array) config("migration.canonical.module_markers.{$module}", []);
                    foreach ($markers as $marker) {
                        if (! is_string($marker) || trim($marker) === '') {
                            continue;
                        }
                        if (str_starts_with($marker, 'class:')) {
                            if (class_exists(substr($marker, 6))) {
                                return true;
                            }
                            continue;
                        }
                        $path = str_starts_with($marker, 'path:') ? substr($marker, 5) : $marker;
                        $fullPath = $app->basePath($path);
                        if (is_dir($fullPath) || is_file($fullPath)) {
                            return true;
                        }
                    }

                    return false;
                },
            );
        });
        $this->app->singleton(BuiltinCanonicalEntityCatalog::class, static fn () => new BuiltinCanonicalEntityCatalog());
        $this->app->singleton(CanonicalEntityRegistry::class, function ($app) {
            $registry = new CanonicalEntityRegistry();
            foreach ($app->make(BuiltinCanonicalEntityCatalog::class)->definitions() as $definition) {
                $registry->register($definition);
            }

            return $registry;
        });
        $this->app->singleton(DestinationHandlerRegistry::class, static fn () => new DestinationHandlerRegistry());

        $this->app->singleton(TransformationRegistry::class, static fn () => new TransformationRegistry());
        $this->app->singleton(TransformationEngine::class, static function ($app) {
            return new TransformationEngine($app->make(TransformationRegistry::class));
        });
        $this->app->singleton(MappingPreviewService::class, static function ($app) {
            return new MappingPreviewService($app->make(TransformationEngine::class));
        });

        $this->app->singleton(CanonicalChecksum::class, static fn () => new CanonicalChecksum());
        $this->app->singleton(ExternalIdRepositoryInterface::class, EloquentExternalIdRepository::class);
        $this->app->singleton(ExternalIdMapService::class, static function ($app) {
            return new ExternalIdMapService(
                $app->make(ExternalIdRepositoryInterface::class),
                $app->make(CanonicalChecksum::class),
            );
        });

        $this->app->singleton(DuplicateResolver::class, static function () {
            return new DuplicateResolver((float) config('migration.canonical.minimum_fuzzy_confidence', 0.85));
        });
        $this->app->singleton(SensitiveValueMasker::class, static fn () => new SensitiveValueMasker());
        $this->app->singleton(AdvisoryMappingSuggestionService::class, static function ($app) {
            return new AdvisoryMappingSuggestionService($app->make(SensitiveValueMasker::class));
        });
        $this->app->singleton(MappingSuggestionApprovalService::class, static fn () => new MappingSuggestionApprovalService());

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
