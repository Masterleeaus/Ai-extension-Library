<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class BusinessSingularityManager extends AbstractAIWorker
{
    public static function id(): string { return 'business-singularity-manager'; }
    public static function name(): string { return 'Enterprise Orchestration Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Coordinate strategic manager outputs',
            'Balance growth risk quality and sustainability',
            'Produce governed enterprise action plans',
        ];
    }

    public static function permissions(): array
    {
        return [
            'reports.strategic',
            'governance.read',
            'analytics.read',
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_2_assistants',
            'operational_authority' => 'workcore_api_only',
            'planning_only' => true,
            'requires_governed_execution' => true,
        ];
    }
}
