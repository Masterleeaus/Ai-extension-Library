<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class OperationsManager extends AbstractAIWorker
{
    public static function id(): string { return 'operations-manager'; }
    public static function name(): string { return 'Operations Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Daily cleaning operations',
            'Job and recurring service oversight',
            'Scheduling and dispatch coordination'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'schedules.read',
            'dispatch.read',
            'recurrence.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_2_assistants',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
