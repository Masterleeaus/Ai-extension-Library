<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$source = static function (string $relative) use ($root): string {
    $path = $root . '/' . ltrim($relative, '/');

    return is_file($path) ? (string) file_get_contents($path) : '';
};

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$contains = static function (string $needle, string $haystack, string $message) use ($assert): void {
    $assert(str_contains($haystack, $needle), $message);
};

$notContains = static function (string $needle, string $haystack, string $message) use ($assert): void {
    $assert(! str_contains($haystack, $needle), $message);
};

$verticals = $source('config/vertical-distribution.php');
$provider = $source('System/SocialMediaServiceProvider.php');
$controller = $source('System/Http/Controllers/ReachWorkspaceController.php');
$workspace = $source('resources/views/workspace.blade.php');
$navigation = $source('resources/views/components/reach-navigation.blade.php');
$overview = $source('resources/views/index.blade.php');
$organic = $source('resources/views/post/index.blade.php');
$connections = $source('resources/views/platforms.blade.php');
$adminSettings = $source('resources/views/setting/index.blade.php');
$fileMap = $source('docs/TITAN-REACH-UNIVERSAL-UI-FILE-MAP.md');

$canonical = [
    'field-home-services',
    'accommodation',
    'real-estate',
    'salons-personal-care',
    'fitness-membership',
    'automotive-services',
    'ecommerce-retail',
    'hire-rental',
    'booking-capacity',
];

foreach ($canonical as $slug) {
    $contains("'{$slug}' => [", $verticals, "Missing canonical vertical {$slug}.");
}
$contains("'facilities-maintenance'", $verticals, 'Facilities maintenance must remain a subtype.');
$notContains("'facilities-management' => [", $verticals, 'Facilities management must not become a tenth vertical.');

$contains('class ReachWorkspaceController', $controller, 'Missing ReachWorkspaceController.');
foreach (['function show', 'function storeDraft'] as $method) {
    $contains($method, $controller, "ReachWorkspaceController missing {$method}.");
}
foreach (['resolveVerticalProfile', 'suitabilityFor', 'generic-business', 'DistributionItem', "where('user_id', Auth::id())", "'settings'"] as $needle) {
    $contains($needle, $controller, "Workspace controller missing required boundary {$needle}.");
}
$contains("content_type === DistributionItem::TYPE_SOCIAL_POST", $controller, 'Universal draft creation must refuse canonical social posts.');
$notContains('->publish(', $controller, 'Workspace controller must not publish directly.');
$notContains('ads_management', $controller, 'Workspace controller must not self-authorise ad spend.');

$contains('workspace/{section}', $provider, 'Missing universal workspace route.');
$contains('workspace/create/draft', $provider, 'Missing universal draft route.');
$contains("->name('workspace')", $provider, 'Workspace route must remain in existing SocialMedia route namespace.');
$contains("->name('workspace.draft.store')", $provider, 'Draft route must remain in existing SocialMedia route namespace.');

$assert($workspace !== '', 'Missing workspace Blade.');
$assert($navigation !== '', 'Missing Reach navigation Blade.');

$labels = [
    'Overview', 'Create', 'Distribute', 'Listings', 'Organic Social', 'Paid Media',
    'Creative Studio', 'Catalogues', 'Inbox', 'Connections', 'Analytics', 'Settings',
];
foreach ($labels as $label) {
    $contains($label, $navigation, "Reach navigation missing {$label}.");
}

foreach ([$workspace, $overview, $organic, $connections] as $view) {
    $contains('social-media::components.reach-navigation', $view, 'A native Reach surface is missing shared navigation.');
}
$notContains('social-media::components.reach-navigation', $adminSettings, 'Admin credential settings must not be exposed as the customer workspace Settings surface.');
$contains("'section' => 'settings'", $navigation, 'Customer Settings must route through the authenticated Reach workspace.');

foreach (['direct', 'partner', 'assisted', 'export_only'] as $mode) {
    $contains($mode, $workspace, "Workspace does not visibly represent {$mode} destination mode.");
}
foreach (['not-applicable', 'disabled', 'aria-disabled'] as $needle) {
    $contains($needle, $workspace, "Workspace missing suitability/disabled-state marker {$needle}.");
}
foreach (['profile_version', 'profile_provenance', 'resolved_subtype', 'compliance_warnings', 'handoff_targets'] as $needle) {
    $contains($needle, $workspace . $controller, "Workspace missing vertical context {$needle}.");
}

$contains('resources/views/index.blade.php', $fileMap, 'Workspace native parent provenance missing.');
$contains('resources/views/components/home/tools.blade.php', $fileMap, 'Navigation native parent provenance missing.');
$contains('workspace.blade.php', $fileMap, 'Workspace Blade provenance missing.');
$contains('reach-navigation.blade.php', $fileMap, 'Navigation Blade provenance missing.');
$contains('admin-only', mb_strtolower($fileMap), 'File map must document the admin-only settings boundary.');

if ($failures !== []) {
    fwrite(STDERR, "Issue #274 contract failed:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "Issue #274 universal Reach UI contract passed.\n");
