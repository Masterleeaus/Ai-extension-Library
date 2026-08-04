<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class TrainingAndOnboardingAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'training-onboarding-assistant'; }
    public static function name(): string { return 'Training and Onboarding Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Coordinate new technician onboarding process',
            'Track onboarding progress and completion',
            'Ensure required certifications and documents',
            'Schedule orientation and training sessions',
            'Provide onboarding status reporting',
            'Design and manage training programs',
            'Coordinate technical and safety training',
            'Track training attendance and completion',
            'Manage training materials and resources',
            'Schedule refresher training as needed',
            'Track certification requirements and deadlines',
            'Schedule certification renewal reminders',
            'Manage certification documentation',
            'Support continuing education credits',
            'Track certification compliance status',
            'Identify skill development opportunities',
            'Recommend training based on skill gaps',
            'Track skill progression and advancement',
            'Support career development planning',
            'Coordinate mentorship programs',
            'Track training effectiveness and ROI',
            'Analyze training completion patterns',
            'Measure impact on performance metrics',
            'Generate training and development reports',
            'Provide continuous learning recommendations'
        ];
    }

    public static function permissions(): array
    {
        return [
            'training.read',
            'training.write',
            'training.schedule',
            'certifications.read',
            'certifications.write',
            'technicians.read',
            'skills.read',
            'skills.write',
            'analytics.training',
            'reports.create'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'learning_management' => true,
            'automation' => true
        ];
    }
}
