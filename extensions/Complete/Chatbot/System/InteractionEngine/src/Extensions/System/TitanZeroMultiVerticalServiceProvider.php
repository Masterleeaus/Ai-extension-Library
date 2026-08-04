<?php

namespace App\Extensions\TitanZeroMultiVertical\System;

use Illuminate\Support\ServiceProvider;
use TitanZero\Interaction\Domain\Vertical\VerticalRegistry;
use App\Extensions\TitanZeroMultiVertical\Wizard\GeneralizedCommandMapper;
use App\Extensions\TitanZeroMultiVertical\Wizard\GeneralizedCommandBus;

/**
 * TitanZeroMultiVerticalServiceProvider
 * 
 * Bootstraps the multi-vertical ERP system.
 * Loads vertical manifests, registers adapters, and hooks into Interaction Engine.
 */
class TitanZeroMultiVerticalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register VerticalRegistry as singleton
        $this->app->singleton(VerticalRegistry::class, function ($app) {
            $manifests = $this->loadManifests();
            $activeVertical = config('titan-zero-multi-vertical.active_vertical', 'workcore');
            return new VerticalRegistry($manifests, $activeVertical);
        });

        // Replace Interaction Engine's CommandMapper
        $this->app->bind(
            \TitanZero\Interaction\Wizard\Command\CommandMapper::class,
            GeneralizedCommandMapper::class
        );

        // Replace Interaction Engine's CommandBus
        $this->app->bind(
            \TitanZero\Interaction\Command\CommandBus::class,
            GeneralizedCommandBus::class
        );

        // Register adapters
        $this->registerAdapters();
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/titan-zero-multi-vertical.php' => config_path('titan-zero-multi-vertical.php'),
        ], 'config');

        // Publish manifest files
        $this->publishes([
            __DIR__ . '/../../../storage/app/multi-vertical/config' => storage_path('app/multi-vertical/config'),
        ], 'manifests');

        // Register routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Get registry and initialize
        $this->initializeVerticalRegistry();
    }

    /**
     * Load YAML manifests from config directory
     */
    private function loadManifests(): array
    {
        $manifests = [];
        $configPath = storage_path('app/multi-vertical/config');

        if (!is_dir($configPath)) {
            return $manifests;
        }

        foreach (glob($configPath . '/*.yaml') as $file) {
            $verticalId = basename($file, '.yaml');
            // Parse YAML - requires symfony/yaml package
            if (function_exists('yaml_parse_file')) {
                $manifests[$verticalId] = yaml_parse_file($file);
            }
        }

        return $manifests;
    }

    /**
     * Register vertical adapters
     */
    private function registerAdapters(): void
    {
        // This is where you'd register your vertical adapters
        // For now, this is a template showing the pattern
        // 
        // $this->app->bind(RealEstateAdapter::class, function ($app) {
        //     return new RealEstateAdapter($app->make(VerticalRegistry::class));
        // });
    }

    /**
     * Initialize the vertical registry after boot
     */
    private function initializeVerticalRegistry(): void
    {
        try {
            $registry = $this->app->make(VerticalRegistry::class);
            
            // Validate that at least one vertical is registered
            if (empty($registry->getVerticals())) {
                \Illuminate\Support\Facades\Log::warning('TitanZeroMultiVertical: No verticals registered');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TitanZeroMultiVertical initialization failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
