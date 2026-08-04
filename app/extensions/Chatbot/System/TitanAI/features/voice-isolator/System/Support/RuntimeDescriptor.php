<?php

declare(strict_types=1);

namespace App\Extensions\VoiceIsolator\System\Support;

final class RuntimeDescriptor
{
    public const VERSION = '2.10.0';
    public const CAPABILITY = 'system-ai-chat.voice-isolator';

    public static function dependencies(): array
    {
        return ['system-ai-chat.runtime'];
    }

    public static function tools(): array
    {
        return ['voice_isolator.process'];
    }

    public static function healthChecks(): array
    {
        return ['views:ai-voice-isolator'];
    }

    public static function metadata(): array
    {
        return [
            'extension' => 'voice-isolator',
            'mode' => 'media',
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
