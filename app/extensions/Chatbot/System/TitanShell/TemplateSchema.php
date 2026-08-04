<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanShell;

use RuntimeException;

final class TemplateSchema
{
    public const VERSION = '2.0.0';

    /**
     * Resolve a shell schema while preserving non-application vertical templates.
     * Legacy application slugs are migrated to one of the five canonical apps.
     */
    public static function resolve(?string $slug): array
    {
        $requestedSlug = trim((string) ($slug ?: 'titan-zero'));
        $canonicalSlug = PlatformApplicationRegistry::canonicalSlug($requestedSlug);
        $resolvedSlug = $canonicalSlug ?? $requestedSlug;
        $schema = self::read($resolvedSlug);

        if ($schema !== null) {
            if ($canonicalSlug !== null && $requestedSlug !== $canonicalSlug) {
                $schema['migration'] = [
                    'requested_slug' => $requestedSlug,
                    'canonical_slug' => $canonicalSlug,
                    'legacy' => true,
                ];
            }

            return $schema;
        }

        return self::generic($resolvedSlug);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return array_map(
            static fn (string $slug): array => self::resolve($slug),
            PlatformApplicationRegistry::slugs(),
        );
    }

    /**
     * Retain access to non-application template schemas for dedicated template
     * tooling without exposing them as top-level platform applications.
     *
     * @return list<array<string, mixed>>
     */
    public static function allTemplateSchemas(): array
    {
        $schemas = [];

        foreach (glob(self::directory() . DIRECTORY_SEPARATOR . '*.json') ?: [] as $path) {
            if (basename($path) === 'index.json') {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($path), true);
            if (! is_array($decoded)) {
                throw new RuntimeException('Invalid Titan template schema: ' . basename($path));
            }

            $schemas[] = $decoded;
        }

        return $schemas;
    }

    private static function read(string $slug): ?array
    {
        $path = self::directory() . DIRECTORY_SEPARATOR . $slug . '.json';
        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid Titan template schema: ' . $slug);
        }

        return $decoded;
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
            'permissions' => [],
            'privacy' => ['default_mode' => 'device-first'],
            'notifications' => [],
            'settings_sections' => ['privacy', 'device-security', 'offline-sync', 'appearance', 'diagnostics'],
            'preview_states' => ['mobile', 'desktop', 'offline'],
        ];
    }
}
