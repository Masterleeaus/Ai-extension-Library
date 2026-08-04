<?php

namespace App\Extensions\Chatbot\System;

use App\Extensions\Chatbot\System\Services\Offline\ChatbotOfflineIntegration;
use App\Extensions\Chatbot\System\Services\Offline\InteractionEngineImprover;
use App\Extensions\Chatbot\System\Services\Offline\OfflineAIAgentAdapter;
use App\Extensions\Chatbot\System\Services\Offline\OfflineMemoryOptimizer;
use App\Extensions\Chatbot\System\Services\Offline\OfflineWorkCoreAdapter;
use App\Extensions\Chatbot\System\Services\Offline\OnlineOfflineSwitcher;
use App\Extensions\Chatbot\System\Services\Offline\WorkCoreSyncService;
use Illuminate\Support\ServiceProvider;
use TitanZero\Interaction\LocalIntelligence\LocalBrain;

class OfflineIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register LocalBrain singleton with default fallback
        $this->app->singleton(LocalBrain::class, function () {
            return LocalBrain::createDefault();
        });

        // Register online/offline switcher for intelligent AI selection
        $this->app->singleton(OnlineOfflineSwitcher::class, function ($app) {
            return new OnlineOfflineSwitcher($app->make(LocalBrain::class));
        });

        // Register memory optimizer
        $this->app->singleton(OfflineMemoryOptimizer::class, function ($app) {
            return new OfflineMemoryOptimizer($app->make(LocalBrain::class));
        });

        // Register WorkCore sync service
        $this->app->singleton(WorkCoreSyncService::class, function ($app) {
            return new WorkCoreSyncService($app->make(LocalBrain::class));
        });

        // Register interaction engine improver for cloud-driven enhancements
        $this->app->singleton(InteractionEngineImprover::class, function ($app) {
            return new InteractionEngineImprover(
                $app->make(LocalBrain::class),
                $app->make(OnlineOfflineSwitcher::class)
            );
        });

        // Register offline adapters
        $this->app->singleton(ChatbotOfflineIntegration::class, function ($app) {
            return new ChatbotOfflineIntegration(
                $app->make(LocalBrain::class),
                $app->make(OfflineMemoryOptimizer::class),
                $app->make(OnlineOfflineSwitcher::class)
            );
        });

        $this->app->singleton(OfflineWorkCoreAdapter::class, function ($app) {
            return new OfflineWorkCoreAdapter(
                $app->make(LocalBrain::class),
                $app->make(WorkCoreSyncService::class)
            );
        });

        $this->app->singleton(OfflineAIAgentAdapter::class, function ($app) {
            return new OfflineAIAgentAdapter($app->make(LocalBrain::class));
        });
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
