<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class StrategicRiskComplianceManager extends AbstractAIWorker
{
    public static function id(): string { return 'strategic-risk-compliance-manager'; }
    public static function name(): string { return 'Strategic Risk & Compliance Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Enterprise risk assessment',
            'Regulatory-change monitoring',
            'Business continuity planning',
        ];
    }

    public static function permissions(): array
    {
        return [
            'risk.read',
            'compliance.read',
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
