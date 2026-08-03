<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools\Bridges;

use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolDefinition;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolRegistry;
use Throwable;

final class AIAgentToolCatalogBridge
{
    private const ACTION_REGISTRY = 'App\\Extensions\\AIAgent\\System\\Engine\\AIAgentActionRegistry';

    public function register(SkillToolRegistry $registry): void
    {
        if (! class_exists(self::ACTION_REGISTRY)) {
            return;
        }

        try {
            $actionRegistryClass = self::ACTION_REGISTRY;
            $groups = app($actionRegistryClass)->all();
        } catch (Throwable) {
            return;
        }

        if (! is_array($groups)) {
            return;
        }

        foreach ($groups as $actions) {
            if (! is_array($actions)) {
                continue;
            }

            foreach ($actions as $action) {
                if (! is_array($action) || ! is_string($action['key'] ?? null)) {
                    continue;
                }

                $actionKey = trim($action['key']);
                if ($actionKey === '') {
                    continue;
                }

                $toolKey = 'ai-agent.' . str_replace('_', '-', $actionKey);
                $schema = is_array($action['config_schema'] ?? null)
                    ? $action['config_schema']
                    : [];
                $schema['type'] ??= 'object';
                $schema['properties'] = is_array($schema['properties'] ?? null)
                    ? $schema['properties']
                    : [];

                $registry->register(SkillToolDefinition::fromArray([
                    'key' => $toolKey,
                    'function_name' => 'ai_agent_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $actionKey),
                    'description' => is_string($action['description'] ?? null) && trim($action['description']) !== ''
                        ? trim($action['description'])
                        : (string) ($action['label'] ?? $actionKey),
                    'source' => 'ai-agent',
                    'input_schema' => $schema,
                    'capabilities' => ['ai-agent.action.' . $actionKey],
                    'approval' => $this->approvalFor($actionKey),
                    'metadata' => [
                        'action_key' => $actionKey,
                        'category' => $action['category'] ?? 'utilities',
                        'icon' => $action['icon'] ?? 'tabler-bolt',
                    ],
                ]));
            }
        }
    }

    private function approvalFor(string $actionKey): string
    {
        foreach (['send', 'message', 'publish', 'create', 'delete', 'schedule', 'book', 'marketing', 'social'] as $writeSignal) {
            if (str_contains($actionKey, $writeSignal)) {
                return 'required';
            }
        }

        return 'conditional';
    }
}
