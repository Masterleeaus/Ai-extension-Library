<?php
declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\WorkCoreApps;

use Illuminate\Support\ServiceProvider;

final class WorkCoreAppsIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $runtimeClass = \App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient::class;

        $this->app->singleton(WorkCoreAppBridge::class, function ($app): WorkCoreAppBridge {
            $runtimeClass = \App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient::class;
            $runtime = class_exists($runtimeClass) && $app->bound($runtimeClass)
                ? $app->make($runtimeClass)
                : null;

            return new WorkCoreAppBridge($runtime);
        });

        $this->app->alias(WorkCoreAppBridge::class, 'chatbot.workcore.apps');
        $this->app->singleton(TitanAppWorkCoreMap::class);
        $this->app->alias(TitanAppWorkCoreMap::class, 'chatbot.workcore.app-map');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config' => config_path('chatbot/workcore-apps'),
        ], 'chatbot-workcore-app-config');
    }
}
