<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAnnotations\System;

use App\Extensions\CreativeSuiteAnnotations\System\Http\Controllers\Admin\CreativeSuiteAnnotationsSettingsController;
use App\Extensions\CreativeSuiteAnnotations\System\Http\Controllers\CreativeSuiteAnnotationsAIController;
use App\Http\Middleware\CheckTemplateTypeAndPlan;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CreativeSuiteAnnotationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(Kernel $kernel): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->publishAssets();
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/creative-suite-annotations.php',
            'creative-suite-annotations'
        );

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'creative-suite-annotations');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'creative-suite-annotations');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/assets/img' => public_path('vendor/creative-suite-annotations/img'),
        ], 'extension');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $router) {
                $router
                    ->prefix('dashboard/user/creative-suite-annotations')
                    ->name('dashboard.user.creative-suite-annotations.')
                    ->middleware(CheckTemplateTypeAndPlan::class)
                    ->group(function (Router $router) {
                        $router->post('edit', [CreativeSuiteAnnotationsAIController::class, 'edit'])->name('edit');
                        $router->get('edit/{task}/status', [CreativeSuiteAnnotationsAIController::class, 'status'])->name('edit.status');
                        $router->post('analyze', [CreativeSuiteAnnotationsAIController::class, 'analyze'])->name('analyze');
                    });

                $router
                    ->prefix('dashboard/admin/creative-suite-annotations')
                    ->name('dashboard.admin.creative-suite-annotations.')
                    ->middleware('admin')
                    ->controller(CreativeSuiteAnnotationsSettingsController::class)
                    ->group(function (Router $router) {
                        $router->post('settings', 'update')->name('settings.update');
                    });
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }
}
