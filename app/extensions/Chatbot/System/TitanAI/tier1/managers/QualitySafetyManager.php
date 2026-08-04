<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class QualitySafetyManager extends AbstractAIWorker
{
    public static function id(): string { return 'quality-safety-manager'; }
    public static function name(): string { return 'Quality and Safety Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Quality standards',
            'Inspection and complaint oversight',
            'Safety, incidents and compliance'
        ];
    }

    public static function permissions(): array
    {
        return [
            'inspections.read',
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
            'delegates_to' => 'tier_2_assistants',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
