<?php

declare(strict_types=1);

namespace App\Extensions\AiMusicPro\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\AiMusicPro\System\Http\Controllers\AiMusicProController;
use App\Extensions\AiMusicPro\System\Http\Controllers\AiMusicProSettingsController;
use App\Http\Middleware\CheckTemplateTypeAndPlan;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Author: MagicAI Team <info@liquid-themes.com>
 *
 * @note When you create a new service provider, make sure to add it to the "MarketplaceServiceProvider". Otherwise, your Laravel application won’t recognize this provider, and the related functions won’t work properly.
 * @note If you want to perform a specific action when an extension is uninstalled, you can use the UninstallExtensionServiceProviderInterface. By implementing this interface, you can define custom operations that will be triggered during the uninstallation of the extension.
 * @note The registerKey() method is used to provide a unique identifier for the extension, which is essential for the healthy check and other functionalities.
 */
class AiMusicProServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface, UninstallExtensionServiceProviderInterface
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
            ->publishAssets()
            ->registerComponents();

    }

    public function registerComponents(): static
    {
        //        $this->loadViewComponentsAs('ai-music-pro', []);

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            //            __DIR__ . '/../resources/assets/js' => public_path('vendor/ai-music-pro/js'),
            //            __DIR__ . '/../resources/assets/images' => public_path('vendor/ai-music-pro/images'),
        ], 'extension');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/aimusicpro.php', 'ai-music-pro');

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'ai-music-pro');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'ai-music-pro');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
                'prefix'     => 'dashboard/user/ai-music-pro/',
                'as'         => 'dashboard.user.ai-music-pro.',
                'controller' => AiMusicProController::class,
            ], function (Router $router) {
                $router->get('/', 'index')->name('index')->middleware(CheckTemplateTypeAndPlan::class);
                $router->post('/generate', 'generate')->name('generate');
                $router->get('/delete/{id}', 'delete')->name('delete');
            });

        $this->router()
            ->group([
                'prefix'     => LaravelLocalization::setLocale(),
                'middleware' => ['web', 'auth', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
            ], function (Router $router) {
                $router
                    ->controller(AiMusicProSettingsController::class)
                    ->prefix('dashboard/admin/ai-music-pro')
                    ->name('dashboard.admin.ai-music-pro.')
                    ->middleware('admin')
                    ->group(function (Router $router) {
                        $router->get('settings', 'index')->name('settings');
                        $router->post('settings', 'update')->name('settings.update');
                    });
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public static function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }

    public function registerKey(): string
    {
        return 'ai-music-pro';
    }
}
