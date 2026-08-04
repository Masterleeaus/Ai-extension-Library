<?php

declare(strict_types=1);

namespace App\Extensions\SharedChat\System;

use App\Extensions\SystemAIChat\System\Capabilities\CapabilityDefinition;
use App\Extensions\SystemAIChat\System\Capabilities\CapabilityRegistry;
use App\Extensions\SharedChat\System\Http\Controllers\ShareController;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class SharedChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/shared-chat.php', 'shared-chat');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'shared-chat');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'shared-chat');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([__DIR__ . '/../config/shared-chat.php' => config_path('shared-chat.php')], 'shared-chat-config');
        $this->registerRoutes();
        $this->registerCapability();
    }

    private function registerRoutes(): void
    {
        $this->app['router']->middleware('web')->group(function (Router $router): void {
            $router->get('share/{category}/{chat}/{message}', [ShareController::class, 'share'])->name('shared-chat.show');
            $router->middleware(['auth', 'throttle:' . config('shared-chat.rate_limit', '20,1')])->group(function (Router $router): void {
                $router->post('share/link', [ShareController::class, 'createLink'])->name('make.link');
                $router->delete('share/link/{shareLink}', [ShareController::class, 'revoke'])->name('shared-chat.revoke');
            });
        });
    }

    private function registerCapability(): void
    {
        if (! $this->app->bound(CapabilityRegistry::class)) return;
        $this->app->make(CapabilityRegistry::class)->register(new CapabilityDefinition(
            key: 'system-ai-chat.shared-chat',
            label: 'Chat Share',
            description: 'Controlled, expiring and revocable read-only conversation sharing.',
            version: '2.8.0',
            dependencies: ['system-ai-chat.runtime'],
            metadata: ['expiring_links' => true, 'revocation' => true, 'access_tracking' => true],
        ));
    }
}
