<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class AIConsciousnessOrchestrator extends AbstractAIWorker
{
    public static function id(): string { return 'ai-consciousness-orchestrator'; }
    public static function name(): string { return 'Organisational Awareness Orchestrator'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Summarise organisational state',
            'Synthesize workforce and customer sentiment',
            'Support reflective decision review',
        ];
    }

    public static function permissions(): array
    {
        return [
            'analytics.read',
            'feedback.read',
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
