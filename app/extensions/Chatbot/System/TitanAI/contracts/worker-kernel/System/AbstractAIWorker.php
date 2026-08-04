<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

abstract class AbstractAIWorker implements AIWorker
{
    public static function definition(): array
    {
        return [
            'id' => static::id(),
            'name' => static::name(),
            'tier' => static::tier(),
            'role' => static::role(),
            'industry' => 'cleaning',
            'capabilities' => static::capabilities(),
            'permissions' => static::permissions(),
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
