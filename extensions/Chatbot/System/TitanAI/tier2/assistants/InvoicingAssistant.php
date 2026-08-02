<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class InvoicingAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'invoicing-assistant'; }
    public static function name(): string { return 'Invoicing Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Validate completed work and chargeable items',
            'Prepare invoices',
            'Coordinate invoice delivery'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'invoices.read',
            'pricing.read'
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
