<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class FractalBusinessGrowthManager extends AbstractAIWorker
{
    public static function id(): string { return 'fractal-business-growth-manager'; }
    public static function name(): string { return 'Pattern-Based Growth Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Identify repeatable growth patterns',
            'Scale proven operating playbooks',
            'Analyse complexity across organisational levels',
        ];
    }

    public static function permissions(): array
    {
        return [
            'growth.read',
            'analytics.read',
            'performance.read',
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
