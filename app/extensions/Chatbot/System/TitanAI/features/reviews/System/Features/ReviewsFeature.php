<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Features;

final class ReviewsFeature
{
    public static function id(): string { return 'reviews'; }
    public static function systemChat(): string { return 'system-ai-chat'; }
    public static function enabledForCleaning(): bool { return true; }
    public static function definition(): array
    {
        return ['id'=>static::id(),'system_chat'=>static::systemChat(),'cleaning_only'=>true,'operational_authority'=>'workcore_api_only'];
    }
}
