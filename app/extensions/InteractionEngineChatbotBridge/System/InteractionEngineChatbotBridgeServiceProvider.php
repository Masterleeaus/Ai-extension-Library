<?php

declare(strict_types=1);

namespace TitanZero\InteractionBridge\System;

use Illuminate\Support\ServiceProvider;

final class InteractionEngineChatbotBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! class_exists(\TitanZero\Interaction\Providers\InteractionServiceProvider::class)) {
            throw new \RuntimeException(
                'InteractionEngineChatbotBridge requires titanzero/interaction-engine to be installed through Composer.'
            );
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../resources/js' => public_path('vendor/titan-interaction-engine'),
        ], 'titan-interaction-engine-pwa');
    }
}
