<?php

declare(strict_types=1);

namespace App\Extensions\FluxPro\System;

use App\Extensions\FluxPro\System\Http\Controllers\FalAIWebhookController;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FluxProServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(Kernel $kernel): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes();

    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'flux-pro');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'flux-pro');

        return $this;
    }

    private function registerRoutes(): void
    {
        // Register unified FAL webhook endpoint
        // This consolidates all FAL provider webhooks (FluxPro, NanoBanana, SeeDreamV4)
        $this->router()
            ->group([
                'middleware' => ['api'],
                'prefix'     => 'api/webhooks',
            ], function (Router $router) {
                // POST only (not ANY) for security
                $router->post('fal/{provider}', [\App\Extensions\FluxPro\System\Http\Controllers\UnifiedFalWebhookController::class, 'handle'])
                    ->name('webhook.fal');
            });

        // Legacy route adapter for backward compatibility (temporary)
        // TODO: Remove after migration period (set removal date)
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $router) {
                $router->any('generator/webhook/fal-ai', [\App\Extensions\FluxPro\System\Http\Controllers\LegacyFalWebhookAdapter::class, 'handle'])
                    ->name('generator.webhook.fal-ai')
                    ->withoutMiddleware(['web', 'auth']);
            });
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }
}
