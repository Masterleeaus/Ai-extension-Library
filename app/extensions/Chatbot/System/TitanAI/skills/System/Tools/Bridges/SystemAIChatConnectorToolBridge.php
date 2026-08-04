<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools\Bridges;

use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolDefinition;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolExecutionContext;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolRegistry;
use RuntimeException;

final class SystemAIChatConnectorToolBridge
{
    private const CONNECTOR_MODEL = 'App\\Extensions\\SystemAIChat\\System\\Connectors\\Models\\SystemAIChatConnector';
    private const CONNECTOR_TOOL_SERVICE = 'App\\Extensions\\SystemAIChat\\System\\Connectors\\ConnectorToolService';

    public function register(SkillToolRegistry $registry): void
    {
        if (! class_exists(self::CONNECTOR_MODEL) || ! class_exists(self::CONNECTOR_TOOL_SERVICE)) {
            return;
        }

        foreach ($registry->all() as $definition) {
            if ($definition->source !== 'connector') {
                continue;
            }

            $registry->register($definition, function (
                array $arguments,
                SkillToolExecutionContext $context,
                SkillToolDefinition $tool,
            ): array {
                if ($context->userId === null) {
                    throw new RuntimeException('Connector tool execution requires an authenticated user context.');
                }

                $modelClass = self::CONNECTOR_MODEL;
                $connectors = $modelClass::query()
                    ->where('user_id', $context->userId)
                    ->where('is_active', true)
                    ->where('is_paused', false)
                    ->get();

                $serviceClass = self::CONNECTOR_TOOL_SERVICE;
                $result = app($serviceClass)->handle($tool->functionName, $arguments, $connectors);

                if (! is_array($result)) {
                    throw new RuntimeException("Connector tool [{$tool->key}] is unavailable or not connected.");
                }

                return $result;
            });
        }
    }
}
