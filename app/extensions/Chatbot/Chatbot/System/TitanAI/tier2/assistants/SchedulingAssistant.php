<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class SchedulingAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'scheduling-assistant'; }
    public static function name(): string { return 'Scheduling Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Evaluate availability and constraints',
            'Propose and coordinate appointment times',
            'Prepare booking and rescheduling actions'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'availability.read',
            'schedules.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
