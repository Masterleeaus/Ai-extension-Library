<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatFileChat\System\Support;

final class RuntimeDescriptor
{
    public const VERSION = '1.11.0';
    public const CAPABILITY = 'system-ai-chat.file-chat';

    public static function dependencies(): array
    {
        return ['system-ai-chat.runtime'];
    }

    public static function tools(): array
    {
        return ['file_chat.attach', 'file_chat.extract'];
    }

    public static function healthChecks(): array
    {
        return ['config:system-ai-chat-file-chat'];
    }

    public static function metadata(): array
    {
        return [
            'extension' => 'system-ai-chat-file-chat',
            'mode' => 'ingestion',
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
