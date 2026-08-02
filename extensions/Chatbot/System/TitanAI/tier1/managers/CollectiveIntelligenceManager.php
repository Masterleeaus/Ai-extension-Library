<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class CollectiveIntelligenceManager extends AbstractAIWorker
{
    public static function id(): string { return 'collective-intelligence-manager'; }
    public static function name(): string { return 'Collective Intelligence Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Synthesize multi-agent recommendations',
            'Aggregate stakeholder input',
            'Detect agreement and disagreement patterns',
        ];
    }

    public static function permissions(): array
    {
        return [
            'analytics.read',
            'reports.strategic',
            'governance.read',
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
