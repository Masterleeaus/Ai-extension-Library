<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class NotifyCleanerAgent extends AbstractAIWorker
{
    public static function id(): string { return 'notify-cleaner-agent'; }
    public static function name(): string { return 'Notify Cleaner Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }

    public static function capabilities(): array
    {
        return [
            'Send one operational notification to a cleaner'
        ];
    }

    public static function permissions(): array
    {
        return [
            'workers.read',
            'communications.send'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_4_tools',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
