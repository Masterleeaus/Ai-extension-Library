<?php

declare(strict_types=1);

namespace App\Services\AI\Tier4\Tools\Contracts;

abstract class AbstractTool implements ToolContract
{
    public static function definition(): array
    {
        return ['id'=>static::id(),'name'=>static::name(),'operation'=>static::operation(),'requires_approval'=>static::requiresApproval(),'permissions'=>static::permissions(),'runtime'=>'workcore-native-ai-runtime'];
    }
}
