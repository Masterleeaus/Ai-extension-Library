<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class QualityInspectionAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'quality-inspection-assistant'; }
    public static function name(): string { return 'Quality Inspection Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Evaluate completion evidence',
            'Coordinate inspections and rework',
            'Prepare quality records'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'evidence.read',
            'inspections.read'
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
