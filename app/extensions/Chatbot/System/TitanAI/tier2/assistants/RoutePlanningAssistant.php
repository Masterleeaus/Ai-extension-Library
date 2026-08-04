<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class RoutePlanningAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'route-planning-assistant'; }
    public static function name(): string { return 'Route Planning Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Sequence jobs by location and constraints',
            'Estimate travel impacts',
            'Prepare route updates'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'routes.read',
            'workers.read'
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
