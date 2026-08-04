<?php

declare(strict_types=1);

namespace App\Extensions\CreativeSuiteAITemplate\System;

use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\CreativeSuiteAITemplate\System\Http\Controllers\CreativeSuiteAITemplateController;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class CreativeSuiteAITemplateServiceProvider extends ServiceProvider implements UninstallExtensionServiceProviderInterface
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/creative-suite-ai-template.php', 'creative-suite-ai-template');
    }

    public function boot(): void
    {
        $this->registerViews()
            ->registerRoutes();
    }

    private function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'creative-suite-ai-template');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
                'prefix'     => 'dashboard/user/creative-suite',
                'as'         => 'dashboard.user.creative-suite.',
            ], function (Router $router) {
                $router->post('ai/generate-template', [CreativeSuiteAITemplateController::class, 'generateTemplate'])
                    ->name('ai.generate-template');
                $router->get('ai/generate-template/{taskId}/status', [CreativeSuiteAITemplateController::class, 'templateStatus'])
                    ->name('ai.generate-template.status');
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public static function uninstall(): void
    {
        //
    }
}
