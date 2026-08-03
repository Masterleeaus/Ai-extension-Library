<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Support;

final class RuntimeDescriptor
{
    public const VERSION = '1.12.0';
    public const CAPABILITY = 'system-ai-chat.memory';

    public static function dependencies(): array
    {
        return ['system-ai-chat.runtime'];
    }

    public static function tools(): array
    {
        return ['memory.get_instructions', 'memory.save_instructions', 'memory.clear_instructions'];
    }

    public static function healthChecks(): array
    {
        return ['config:system-ai-chat-memory', 'routes:system-ai-chat-memory.get-instructions'];
    }

    public static function metadata(): array
    {
        return [
            'extension' => 'system-ai-chat-memory',
            'mode' => 'context',
            'tools' => self::tools(),
            'health_checks' => self::healthChecks(),
            'contract_version' => '1.0',
            'performance_profile' => 'bounded-v1',
            'operations_profile' => 'maintained-v1',
            'diagnostics' => [
                'manifest' => 'extension.json',
                'verification_script' => 'tools/verify-package.php',
            ],
        ];
    }
}
