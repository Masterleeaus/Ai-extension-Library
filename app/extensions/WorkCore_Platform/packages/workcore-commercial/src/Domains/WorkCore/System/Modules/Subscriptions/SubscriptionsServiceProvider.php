<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions;

use Illuminate\Support\ServiceProvider;
use WorkCore\Subscriptions\Application\Services\AccessControlService;
use WorkCore\Subscriptions\Application\Services\AnalyticsService;
use WorkCore\Subscriptions\Application\Services\BillingService;
use WorkCore\Subscriptions\Application\Services\PaymentProcessingService;
use WorkCore\Subscriptions\Application\Services\SubscriptionService;

class SubscriptionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->publishMigrations();
        $this->registerRoutes();
    }

    private function registerServices(): void
    {
        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService();
        });

        $this->app->singleton(BillingService::class, function ($app) {
            return new BillingService();
        });

        $this->app->singleton(PaymentProcessingService::class, function ($app) {
            return new PaymentProcessingService();
        });

        $this->app->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService();
        });

        $this->app->singleton(AccessControlService::class, function ($app) {
            return new AccessControlService();
        });
    }

    private function publishMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../../../../../database/migrations' => database_path('migrations'),
            ], 'workcore-subscriptions-migrations');
        }
    }

    private function registerRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }
    }
}
