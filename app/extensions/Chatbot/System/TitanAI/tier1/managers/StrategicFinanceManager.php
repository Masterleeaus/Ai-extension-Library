<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class StrategicFinanceManager extends AbstractAIWorker
{
    public static function id(): string { return 'strategic-finance-manager'; }
    public static function name(): string { return 'Strategic Finance Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Cash-flow forecasting',
            'Margin and cost analysis',
            'Financial risk scenarios',
        ];
    }

    public static function permissions(): array
    {
        return [
            'finance.read',
            'analytics.finance',
            'risk.read',
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
