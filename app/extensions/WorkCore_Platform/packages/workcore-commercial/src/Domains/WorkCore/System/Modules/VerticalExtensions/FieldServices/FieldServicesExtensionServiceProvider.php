<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\FieldWorkerSkills;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\JobSite;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\ServiceChecklist;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\ServiceVisit;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\ServiceVisitPhoto;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Services\PhotoDocumentationService;
use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Services\RouteOptimizationService;
use Illuminate\Support\ServiceProvider;

class FieldServicesExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services
        $this->app->singleton(RouteOptimizationService::class, function () {
            return new RouteOptimizationService();
        });

        $this->app->singleton(PhotoDocumentationService::class, function () {
            return new PhotoDocumentationService();
        });
    }

    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerConfig();
        $this->registerRoutes();
    }

    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }

    private function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('verticals/field-services.php'),
        ], 'field-services-config');
    }

    private function registerRoutes(): void
    {
        // Routes will be registered in Http/routes.php
    }
}
