<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class SafetyComplianceAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'safety-compliance-assistant'; }
    public static function name(): string { return 'Safety and Compliance Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Assess hazards and incidents',
            'Prepare safety records',
            'Coordinate compliance actions'
        ];
    }

    public static function permissions(): array
    {
        return [
            'incidents.read',
            'compliance.read',
            'evidence.read'
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
