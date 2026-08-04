<?php

declare(strict_types=1);

namespace App\Extensions\VoiceIsolator\System\Support;

use App\Extensions\SystemAIChat\System\Capabilities\CapabilityDefinition;
use App\Extensions\SystemAIChat\System\Capabilities\CapabilityRegistry;
use Illuminate\Contracts\Container\BindingResolutionException;

final class RegistersSystemAIChatCapability
{
    public static function register(string $version = RuntimeDescriptor::VERSION): void
    {
        if (! class_exists(CapabilityRegistry::class) || ! class_exists(CapabilityDefinition::class)) {
            return;
        }

        try {
            $registry = app(CapabilityRegistry::class);
            if ($registry->has('system-ai-chat.voice-isolator')) {
                return;
            }
            $registry->register(new CapabilityDefinition(
                key: 'system-ai-chat.voice-isolator',
                label: 'AI Voice Isolator',
                description: 'Voice isolation interface with capability registration and upload safety configuration.',
                version: $version,
                dependencies: RuntimeDescriptor::dependencies(),
                metadata: RuntimeDescriptor::metadata(),
            ));
        } catch (BindingResolutionException) {
            // SystemAIChat is not booted yet; extension remains backward compatible.
        }
    }
}
