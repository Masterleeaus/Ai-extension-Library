<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class RequestReviewAgent extends AbstractAIWorker
{
    public static function id(): string { return 'request-review-agent'; }
    public static function name(): string { return 'Request Review Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }

    public static function capabilities(): array
    {
        return [
            'Send one review request after an eligible job'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'customers.read',
            'communications.send'
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
