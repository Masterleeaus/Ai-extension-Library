<?php

declare(strict_types=1);

namespace TitanAI\Hybrid;

use TitanAI\Hybrid\Console\PurgeExpiredMemoriesCommand;
use TitanAI\Hybrid\Diagnostics\TitanAIDiagnostics;
use TitanAI\Hybrid\Events\TitanAIEventBus;
use TitanAI\Hybrid\Memory\Services\UnifiedMemoryRepository;
use TitanAI\Hybrid\Orchestration\CrossExtensionOrchestrator;
use TitanAI\Hybrid\Registries\UnifiedRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class TitanAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = $this->packagePath('config/titanai.php');
        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'titanai');
        }

        $this->app->singleton(UnifiedRegistry::class, static function (): UnifiedRegistry {
            return new UnifiedRegistry(
                allowOverrides: (bool) config('titanai.registry.allow_overrides', false),
            );
        });
        $this->app->alias(UnifiedRegistry::class, 'titanai.registry');

        $this->app->singleton(TitanAIDiagnostics::class, static fn (): TitanAIDiagnostics => new TitanAIDiagnostics(
            limit: (int) config('titanai.diagnostics.recent_limit', 50),
        ));
        $this->app->alias(TitanAIDiagnostics::class, 'titanai.diagnostics');

        $this->app->singleton(TitanAIEventBus::class);
        $this->app->alias(TitanAIEventBus::class, 'titanai.events');

        $this->app->singleton(UnifiedMemoryRepository::class, static fn ($app): UnifiedMemoryRepository => new UnifiedMemoryRepository(
            table: 'unified_memories',
            events: $app->make(TitanAIEventBus::class),
            diagnostics: $app->make(TitanAIDiagnostics::class),
        ));
        $this->app->alias(UnifiedMemoryRepository::class, 'titanai.memory');

        $this->app->singleton(CrossExtensionOrchestrator::class);
        $this->app->alias(CrossExtensionOrchestrator::class, 'titanai.orchestrator');
    }

    public function boot(): void
    {
        $migrationPath = $this->packagePath('database/migrations');
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([PurgeExpiredMemoriesCommand::class]);

            if ((bool) config('titanai.memory.auto_cleanup', true)) {
                $this->app->booted(function (): void {
                    $this->app->make(Schedule::class)
                        ->command('titanai:memory:purge')
                        ->hourly()
                        ->withoutOverlapping();
                });
            }
        }
    }

    private function packagePath(string $relative): string
    {
        return dirname(__DIR__) . '/' . ltrim($relative, '/');
    }
}
