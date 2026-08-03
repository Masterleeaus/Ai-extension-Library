<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanShell;

use RuntimeException;

final class TemplateSchema
{
    public const VERSION = '1.0.0';

    public static function resolve(?string $slug): array
    {
        $slug = $slug ?: 'titan-zero';
        $path = self::directory() . DIRECTORY_SEPARATOR . $slug . '.json';
        if (! is_file($path)) {
            return self::generic($slug);
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid Titan template schema: ' . $slug);
        }
        return $decoded;
    }

    public static function all(): array
    {
        $decoded = json_decode((string) file_get_contents(self::directory() . DIRECTORY_SEPARATOR . 'index.json'), true);
        return is_array($decoded['templates'] ?? null) ? $decoded['templates'] : [];
    }

    private static function directory(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'titan-apps' . DIRECTORY_SEPARATOR . 'TemplateSchemas';
    }

    private static function generic(string $slug): array
    {
        return [
            'schema_version' => self::VERSION,
            'identity' => ['name' => 'Chatbot', 'slug' => $slug, 'icon' => 'message', 'accent' => 'var(--lqd-ext-chat-primary)'],
            'navigation' => ['default_view' => 'home', 'primary' => [['id' => 'home', 'label' => 'Home', 'icon' => 'home', 'offline' => true]], 'drawer' => [], 'header_actions' => ['settings']],
            'home' => ['widgets' => [], 'quick_actions' => []],
            'chat' => ['persistent' => true, 'role' => 'Chatbot', 'suggested_prompts' => [], 'context_policy' => ['minimum_scope' => true]],
            'workcore' => ['domains' => [], 'commands' => [], 'read_models' => []],
            'offline' => ['records' => [], 'packs' => [], 'retention' => ['completed_days' => 0], 'conflict_rules' => ['server_authoritative' => true]],
            'permissions' => [], 'privacy' => ['default_mode' => 'device-first'], 'notifications' => [],
            'settings_sections' => ['privacy', 'device-security', 'offline-sync', 'appearance', 'diagnostics'],
            'preview_states' => ['mobile', 'desktop', 'offline'],
        ];
    }
}
