<?php

declare(strict_types=1);

namespace App\Extensions\VoiceIsolator\System;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use App\Extensions\VoiceIsolator\System\Support\RegistersSystemAIChatCapability;

class VoiceIsolatorServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(Kernel $kernel): void
    {
        RegistersSystemAIChatCapability::register();
        $this
            ->registerViews();

    }

    public function registerViews(): void
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'ai-voice-isolator');
    }
}
