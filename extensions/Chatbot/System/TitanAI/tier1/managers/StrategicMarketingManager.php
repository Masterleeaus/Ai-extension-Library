<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class StrategicMarketingManager extends AbstractAIWorker
{
    public static function id(): string { return 'strategic-marketing-manager'; }
    public static function name(): string { return 'Strategic Marketing Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Campaign portfolio planning',
            'Segment analysis',
            'Brand and reputation strategy',
        ];
    }

    public static function permissions(): array
    {
        return [
            'marketing.read',
            'campaigns.read',
            'analytics.marketing',
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
