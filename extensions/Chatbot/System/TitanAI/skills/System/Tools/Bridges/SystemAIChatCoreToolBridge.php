<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools\Bridges;

use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolDefinition;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolExecutionContext;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolRegistry;
use RuntimeException;

final class SystemAIChatCoreToolBridge
{
    private const CORE_TOOL_SERVICE = 'App\\Extensions\\SystemAIChat\\System\\Services\\SystemAIChatService';

    public function register(SkillToolRegistry $registry): void
    {
        if (! class_exists(self::CORE_TOOL_SERVICE)) {
            return;
        }

        foreach ($registry->all() as $definition) {
            if ($definition->source !== 'core') {
                continue;
            }

            $registry->register($definition, static function (
                array $arguments,
                SkillToolExecutionContext $context,
                SkillToolDefinition $tool,
            ): array {
                $serviceClass = self::CORE_TOOL_SERVICE;
                $result = $serviceClass::callFunction(
                    $tool->functionName,
                    json_encode($arguments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                );

                if ($result === null) {
                    throw new RuntimeException("SystemAIChat core tool [{$tool->key}] is unavailable.");
                }

                return [
                    'content' => $result,
                    'function_name' => $tool->functionName,
                ];
            });
        }
    }
}
