<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class RecordInspectionAgent extends AbstractAIWorker
{
    public static function id(): string { return 'record-inspection-agent'; }
    public static function name(): string { return 'Record Inspection Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }

    public static function capabilities(): array
    {
        return [
            'Record one quality inspection result'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'inspections.create'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_4_tools',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
