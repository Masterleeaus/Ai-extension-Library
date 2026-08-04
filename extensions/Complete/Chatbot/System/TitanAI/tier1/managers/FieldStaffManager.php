<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class FieldStaffManager extends AbstractAIWorker
{
    public static function id(): string { return 'field-staff-manager'; }
    public static function name(): string { return 'Field Staff Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Cleaner and contractor oversight',
            'Availability, skills and workload',
            'Attendance and performance oversight'
        ];
    }

    public static function permissions(): array
    {
        return [
            'workers.read',
            'availability.read',
            'skills.read',
            'assignments.read'
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
