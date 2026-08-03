<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatFileChat\System\Support;

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
            if ($registry->has('system-ai-chat.file-chat')) {
                return;
            }
            $registry->register(new CapabilityDefinition(
                key: 'system-ai-chat.file-chat',
                label: 'SystemAIChat File Chat',
                description: 'File-assisted chat integration with bounded processing configuration and capability registration.',
                version: $version,
                dependencies: RuntimeDescriptor::dependencies(),
                metadata: RuntimeDescriptor::metadata(),
            ));
        } catch (BindingResolutionException) {
            // SystemAIChat is not booted yet; extension remains backward compatible.
        }
    }
}
