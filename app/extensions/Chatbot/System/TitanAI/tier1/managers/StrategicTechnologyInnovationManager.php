<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class StrategicTechnologyInnovationManager extends AbstractAIWorker
{
    public static function id(): string { return 'strategic-technology-innovation-manager'; }
    public static function name(): string { return 'Strategic Technology & Innovation Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Technology roadmap planning',
            'Automation opportunity assessment',
            'Innovation portfolio analysis',
        ];
    }

    public static function permissions(): array
    {
        return [
            'technology.read',
            'innovation.read',
            'ai.read',
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
