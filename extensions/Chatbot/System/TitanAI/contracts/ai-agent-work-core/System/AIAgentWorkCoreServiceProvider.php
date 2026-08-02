<?php

namespace App\Extensions\AIAgentWorkCore\System;

use Illuminate\Support\ServiceProvider;

class AIAgentWorkCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkCoreAccessService::class);
    }

    public function boot(): void
    {
        $this->loadMigrations();
        $this->loadRoutesFrom(__DIR__ . '/../routes/work-core.php');
        $this->publishes([
            __DIR__ . '/../config' => config_path('extensions/ai-agent-work-core'),
        ], 'ai-agent-work-core-config');
    }

    private function loadMigrations(): void
    {
        if (file_exists(__DIR__ . '/../database/migrations')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}
