<?php

namespace App\Domains\WorkCore\Pricing;

use Illuminate\Support\ServiceProvider;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineService;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineRegistry;
use App\Domains\WorkCore\Calculating\Services\CalculatingEnginePipeline;
use App\Domains\WorkCore\Pricing\Services\PricingService;
use App\Domains\WorkCore\Pricing\Services\DemandAnalysisService;
use App\Domains\WorkCore\Pricing\Services\RevenueOptimizationService;
use App\Domains\WorkCore\Pricing\Algorithms\DemandPricingAlgorithm;
use App\Domains\WorkCore\Pricing\Algorithms\RevenueManagementAlgorithm;
use App\Domains\WorkCore\Pricing\Engines\DynamicPricingEngine;

class PricingServiceProvider extends ServiceProvider
{
    /**
     * Register pricing services
     */
    public function register(): void
    {
        // Register algorithms
        $this->app->singleton(DemandPricingAlgorithm::class, function ($app) {
            return new DemandPricingAlgorithm();
        });

        $this->app->singleton(RevenueManagementAlgorithm::class, function ($app) {
            return new RevenueManagementAlgorithm();
        });

        // Register calculating engine framework
        $this->app->singleton(CalculatingEngineRegistry::class, function ($app) {
            return new CalculatingEngineRegistry();
        });

        $this->app->singleton(CalculatingEnginePipeline::class, function ($app) {
            return new CalculatingEnginePipeline($app->make(CalculatingEngineRegistry::class));
        });

        $this->app->singleton(CalculatingEngineService::class, function ($app) {
            return new CalculatingEngineService(
                $app->make(CalculatingEngineRegistry::class),
                $app->make(CalculatingEnginePipeline::class)
            );
        });

        // Register pricing services
        $this->app->singleton(PricingService::class, function ($app) {
            return new PricingService($app->make(CalculatingEngineService::class));
        });

        $this->app->singleton(DemandAnalysisService::class, function ($app) {
            return new DemandAnalysisService($app->make(DemandPricingAlgorithm::class));
        });

        $this->app->singleton(RevenueOptimizationService::class, function ($app) {
            return new RevenueOptimizationService($app->make(RevenueManagementAlgorithm::class));
        });

        // Register dynamic pricing engine
        $this->registerDynamicPricingEngine();
    }

    /**
     * Bootstrap pricing services
     */
    public function boot(): void
    {
        // Register migrations
        $this->loadMigrationsFrom(
            __DIR__ . '/../../extensions/WorkCore_Platform/native-extensions/WorkCore/database/migrations'
        );

        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/pricing.php' => config_path('pricing.php'),
        ], 'workcore-pricing-config');
    }

    /**
     * Register the dynamic pricing engine
     */
    protected function registerDynamicPricingEngine(): void
    {
        $this->app->afterResolving(CalculatingEngineService::class, function ($service) {
            $engine = new DynamicPricingEngine(
                $this->app->make(DemandPricingAlgorithm::class),
                $this->app->make(RevenueManagementAlgorithm::class),
                config('pricing.dynamic_engine', [])
            );

            $service->registerEngine('dynamic_pricing', DynamicPricingEngine::class);
        });
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            CalculatingEngineRegistry::class,
            CalculatingEnginePipeline::class,
            CalculatingEngineService::class,
            PricingService::class,
            DemandAnalysisService::class,
            RevenueOptimizationService::class,
            DemandPricingAlgorithm::class,
            RevenueManagementAlgorithm::class,
        ];
    }
}
