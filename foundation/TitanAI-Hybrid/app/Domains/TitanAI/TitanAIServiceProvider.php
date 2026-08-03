<?php

declare(strict_types=1);

namespace App\Domains\TitanAI;

use App\Domains\TitanAI\Console\PurgeExpiredMemoriesCommand;
use App\Domains\TitanAI\Diagnostics\TitanAIDiagnostics;
use App\Domains\TitanAI\Events\TitanAIEventBus;
use App\Domains\TitanAI\Memory\Services\UnifiedMemoryRepository;
use App\Domains\TitanAI\Orchestration\CrossExtensionOrchestrator;
use App\Domains\TitanAI\Registries\UnifiedRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class TitanAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = $this->projectPath('config/titanai.php');
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
        $migrationPath = $this->projectPath('database/migrations');
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

    private function projectPath(string $relative): string
    {
        return dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
    }
}
