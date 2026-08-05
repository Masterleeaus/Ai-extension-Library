<?php

namespace App\Domains\WorkCore\Calculating;

use App\Domains\WorkCore\Calculating\Engines\DiscountCalculatingEngine;
use App\Domains\WorkCore\Calculating\Engines\DynamicPricingEngine;
use App\Domains\WorkCore\Calculating\Engines\LoyaltyCalculatingEngine;
use App\Domains\WorkCore\Calculating\Engines\PromotionCalculatingEngine;
use App\Domains\WorkCore\Calculating\Engines\SubscriptionCalculatingEngine;
use App\Domains\WorkCore\Calculating\Engines\TaxCalculatingEngine;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineRegistry;
use App\Domains\WorkCore\Calculating\Services\CalculatingEnginePipeline;
use App\Domains\WorkCore\Calculating\Services\CalculatingEngineService;
use Illuminate\Support\ServiceProvider;

/**
 * Bootstrap the Calculating Engine Framework
 *
 * This service provider registers all calculating engines and configures
 * the pipeline for executing pricing calculations across multiple engines.
 */
class CalculatingEnginesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register singleton services
        $this->app->singleton(CalculatingEngineRegistry::class, function ($app) {
            return new CalculatingEngineRegistry();
        });

        $this->app->singleton(CalculatingEnginePipeline::class, function ($app) {
            return new CalculatingEnginePipeline(
                $app->make(CalculatingEngineRegistry::class)
            );
        });

        $this->app->singleton(CalculatingEngineService::class, function ($app) {
            return new CalculatingEngineService(
                $app->make(CalculatingEngineRegistry::class),
                $app->make(CalculatingEnginePipeline::class)
            );
        });

        // Register engine classes
        $this->registerEngines();
    }

    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../../../config/calculating-engines.php' => config_path('calculating-engines.php'),
        ], 'calculating-engines');

        // Register engines from configuration
        $this->registerEnginesFromConfig();
    }

    private function registerEngines(): void
    {
        $registry = $this->app->make(CalculatingEngineRegistry::class);

        // Register all available engines
        $registry->registerEngineClass('discount_engine', DiscountCalculatingEngine::class);
        $registry->registerEngineClass('dynamic_pricing', DynamicPricingEngine::class);
        $registry->registerEngineClass('tax_engine', TaxCalculatingEngine::class);
        $registry->registerEngineClass('loyalty_engine', LoyaltyCalculatingEngine::class);
        $registry->registerEngineClass('promotion_engine', PromotionCalculatingEngine::class);
        $registry->registerEngineClass('subscription_engine', SubscriptionCalculatingEngine::class);
    }

    private function registerEnginesFromConfig(): void
    {
        $registry = $this->app->make(CalculatingEngineRegistry::class);
        $engines = config('calculating-engines.engines', []);

        foreach ($engines as $engineId => $engineConfig) {
            if ($engineConfig['enabled'] ?? false) {
                $engineClass = $engineConfig['class'] ?? null;

                if ($engineClass && class_exists($engineClass)) {
                    $registry->registerEngineClass($engineId, $engineClass);
                }
            }
        }
    }
}
