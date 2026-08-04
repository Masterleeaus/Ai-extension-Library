<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class CreateInvoiceAgent extends AbstractAIWorker
{
    public static function id(): string { return 'create-invoice-agent'; }
    public static function name(): string { return 'Create Invoice Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }

    public static function capabilities(): array
    {
        return [
            'Create one invoice from approved job charges'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'invoices.create'
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
