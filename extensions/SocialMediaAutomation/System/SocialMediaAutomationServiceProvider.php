<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\SocialMediaAutomation\System\Console\Commands\CleanupPendingAutomationsCommand;
use App\Extensions\SocialMediaAutomation\System\Console\Commands\ProcessPendingAutomationsCommand;
use App\Extensions\SocialMediaAutomation\System\Http\Controllers\AutomationBuilderController;
use App\Extensions\SocialMediaAutomation\System\Http\Controllers\AutomationController;
use App\Extensions\SocialMediaAutomation\System\Http\Controllers\WebhookController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SocialMediaAutomationServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface, UninstallExtensionServiceProviderInterface
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->registerCommands();
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', $this->registerKey());

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], $this->registerKey());

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
            ], function (Router $router) {
                $router
                    ->name('dashboard.user.social-media.automation.')
                    ->prefix('dashboard/user/social-media/automation')
                    ->group(function (Router $router) {
                        Route::get('', [AutomationController::class, 'index'])->name('index');
                        Route::get('create', [AutomationController::class, 'create'])->name('create');
                        Route::get('{automation}/edit', [AutomationController::class, 'edit'])->name('edit');
                        Route::post('', [AutomationController::class, 'store'])->name('store');
                        Route::put('{automation}', [AutomationController::class, 'update'])->name('update');
                        Route::patch('{automation}/status', [AutomationController::class, 'toggleStatus'])->name('toggle-status');
                        Route::delete('{automation}', [AutomationController::class, 'destroy'])->name('destroy');

                        // Builder AJAX endpoints
                        Route::get('posts', [AutomationBuilderController::class, 'fetchPosts'])->name('posts');
                        Route::post('upload-image', [AutomationBuilderController::class, 'uploadImage'])->name('upload-image');
                    });

                // Webhook routes (no auth required)
                $router
                    ->name('social-media.automation.webhook.')
                    ->prefix('social-media/automation/webhook')
                    ->withoutMiddleware('auth')
                    ->group(function (Router $router) {
                        Route::any('x', [WebhookController::class, 'x'])->name('x');
                        Route::any('tiktok', [WebhookController::class, 'tiktok'])->name('tiktok');
                        Route::any('linkedin', [WebhookController::class, 'linkedin'])->name('linkedin');
                        Route::any('facebook', [WebhookController::class, 'facebook'])->name('facebook');
                        Route::any('instagram', [WebhookController::class, 'instagram'])->name('instagram');
                    });
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    private function registerCommands(): static
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessPendingAutomationsCommand::class,
                CleanupPendingAutomationsCommand::class,
            ]);

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('social-media-automation:process-pending')->everyMinute();
                $schedule->command('social-media-automation:cleanup-pending')->daily();
            });
        }

        return $this;
    }

    public static function uninstall(): void {}

    public function registerKey(): string
    {
        return 'social-media-automation';
    }
}
