<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanShell;

final class TemplateNavigation
{
    private const SHARED_DRAWER = [
        ['id' => 'search', 'label' => 'Search', 'icon' => 'search'],
        ['id' => 'recent', 'label' => 'Recent', 'icon' => 'history'],
        ['id' => 'knowledge', 'label' => 'Knowledge', 'icon' => 'book'],
        ['id' => 'files', 'label' => 'Files', 'icon' => 'files'],
        ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell'],
        ['id' => 'approvals', 'label' => 'Approvals', 'icon' => 'checks'],
        ['id' => 'offline-queue', 'label' => 'Offline queue', 'icon' => 'cloud-off'],
    ];

    public static function resolve(?string $slug): array
    {
        $schema = TemplateSchema::resolve($slug);
        $identity = $schema['identity'];
        $navigation = $schema['navigation'];

        return [
            'slug' => $identity['slug'],
            'name' => $identity['name'],
            'default_view' => $navigation['default_view'],
            'primary' => $navigation['primary'],
            'drawer' => array_merge($navigation['drawer'], self::SHARED_DRAWER),
            'schema' => $schema,
            'settings' => [
                'ai-providers' => 'AI & BYO Providers',
                'privacy' => 'Privacy & Data',
                'device-security' => 'Device & Security',
                'offline-sync' => 'Offline & Sync',
                'workcore' => 'WorkCore Connection',
                'permissions' => 'Permissions',
                'notifications' => 'Notifications',
                'channels' => 'Channels',
                'appearance' => 'Appearance',
                'accessibility' => 'Language & Accessibility',
                'diagnostics' => 'Diagnostics',
            ],
        ];
    }

}
