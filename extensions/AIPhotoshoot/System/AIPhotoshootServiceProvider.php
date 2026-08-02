<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\AIPhotoshootController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\AIPhotoshootSettingController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\BackgroundController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\CreateVideoController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\CustomPhotoshootController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\EditImageController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\EditorController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\PhotoShootController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\ProductController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\ReplaceBackgroundController;
use App\Extensions\AIPhotoshoot\System\Http\Controllers\TemplatePhotoshootController;
use App\Http\Middleware\CheckTemplateTypeAndPlan;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Author: MagicAI Team <info@liquid-themes.com>
 *
 * @note When you create a new service provider, make sure to add it to the "MarketplaceServiceProvider". Otherwise, your Laravel application won't recognize this provider, and the related functions won't work properly.
 * @note If you want to perform a specific action when an extension is uninstalled, you can use the UninstallExtensionServiceProviderInterface. By implementing this interface, you can define custom operations that will be triggered during the uninstallation of the extension.
 * @note The registerKey() method is used to provide a unique identifier for the extension, which is essential for the healthy check and other functionalities.
 */
class AIPhotoshootServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface, UninstallExtensionServiceProviderInterface
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
        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/assets/images' => public_path('vendor/ai-photoshoot/images'),
        ], 'extension');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-photoshoot.php', 'ai-photoshoot');

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'ai-photoshoot');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'ai-photoshoot');

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
                'prefix'     => 'dashboard/user/ai-photoshoot',
                'as'         => 'dashboard.user.ai-photoshoot.',
            ], function (Router $router) {
                $router->get('/', AIPhotoshootController::class)->name('index')->middleware(CheckTemplateTypeAndPlan::class);

                $router->group([
                    'prefix'     => 'custom',
                    'as'         => 'custom.',
                    'controller' => CustomPhotoshootController::class,
                ], function (Router $router) {
                    $router->get('/', 'index')->name('index')->middleware(CheckTemplateTypeAndPlan::class);
                    $router->post('/generate', 'generate')->name('generate');
                });

                $router->group([
                    'prefix'     => 'templates',
                    'as'         => 'templates.',
                    'controller' => TemplatePhotoshootController::class,
                ], function (Router $router) {
                    $router->get('/', 'index')->name('index')->middleware(CheckTemplateTypeAndPlan::class);
                    $router->post('/generate', 'generate')->name('generate');
                });

                $router->group([
                    'prefix'     => 'replace-background',
                    'as'         => 'replace-background.',
                    'controller' => ReplaceBackgroundController::class,
                ], function (Router $router) {
                    $router->get('/', 'index')->name('index')->middleware(CheckTemplateTypeAndPlan::class);
                    $router->post('/generate', 'generate')->name('generate');
                });

                $router->group([
                    'prefix'     => 'photo_shoots',
                    'as'         => 'photo_shoots.',
                    'controller' => PhotoShootController::class,
                ], function (Router $router) {
                    $router->get('/my-photoshoots', 'myPhotoshoots')->name('my');
                    $router->get('/images', 'loadImages')->name('images.load');
                    $router->get('/status/{id}', 'status')->name('status');
                    $router->post('/images/crop', 'cropImage')->name('images.crop');
                    $router->post('/images/remove', 'removeImage')->name('images.remove');
                    $router->delete('/{id}', 'destroy')->name('destroy');
                });

                $router->group([
                    'prefix'     => 'edit_image',
                    'as'         => 'edit_image.',
                    'controller' => EditImageController::class,
                ], function (Router $router) {
                    $router->get('/', 'index')->name('index')->middleware(CheckTemplateTypeAndPlan::class);
                    $router->post('/generate', 'generate')->name('generate');
                    $router->get('/status/{id?}', 'status')->name('status');
                });

                $router->group([
                    'prefix'     => 'editor',
                    'as'         => 'editor.',
                    'controller' => EditorController::class,
                ], function (Router $router) {
                    $router->get('/', 'index')->name('index')->middleware(CheckTemplateTypeAndPlan::class);
                    $router->get('/completed-images', 'completedImages')->name('completed-images');
                    $router->post('/delete-edit-record', 'deleteEditRecord')->name('delete-edit-record');
                });

                $router->group([
                    'prefix'     => 'create_video',
                    'as'         => 'create_video.',
                    'controller' => CreateVideoController::class,
                ], function (Router $router) {
                    $router->get('/{image_id?}', 'createVideo')->name('index')->middleware(CheckTemplateTypeAndPlan::class);
                    $router->post('/generate', 'generateVideo')->name('generate');
                    $router->get('/status/{id?}', 'videoStatus')->name('status');
                });

                $router->group([
                    'prefix'     => 'products',
                    'as'         => 'product.',
                    'controller' => ProductController::class,
                ], function (Router $router) {
                    $router->get('/load', 'load')->name('load');
                    $router->post('/upload', 'upload')->name('upload');
                    $router->delete('/delete/{productId}', 'delete')->name('delete');
                });

                $router->group([
                    'prefix'     => 'backgrounds',
                    'as'         => 'background.',
                    'controller' => BackgroundController::class,
                ], function (Router $router) {
                    $router->get('/load', 'load')->name('load');
                    $router->post('/upload', 'upload')->name('upload');
                    $router->post('/create', 'create')->name('create');
                    $router->get('/load/status/{id}', 'checkStatus')->name('status');
                    $router->delete('/delete/{backgroundId}', 'delete')->name('delete');
                });

            });

        // Admin routes for AI Photoshoot settings
        $this->router()
            ->group([
                'middleware' => ['web', 'auth', 'admin'],
                'prefix'     => 'dashboard/admin/ai-photoshoot',
                'as'         => 'dashboard.admin.ai-photoshoot.',
            ], function (Router $router) {
                $router->get('/settings', [AIPhotoshootSettingController::class, 'index'])->name('settings');
                $router->post('/settings', [AIPhotoshootSettingController::class, 'update'])->name('settings.update');
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
        return 'ai-photoshoot';
    }
}
