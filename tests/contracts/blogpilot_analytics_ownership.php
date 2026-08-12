<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$providerPath = $root . '/app/extensions/BlogPilot/System/BlogPilotServiceProvider.php';
$controllerPath = $root . '/app/extensions/BlogPilot/System/Http/Controllers/BlogPilotAnalyticsController.php';

function failBlogPilotAnalyticsOwnership(string $message): never
{
    fwrite(STDERR, "BlogPilot analytics ownership contract failed: {$message}\n");
    exit(1);
}

function readBlogPilotAnalyticsFile(string $label, string $path): string
{
    $contents = is_file($path) ? file_get_contents($path) : false;

    if (! is_string($contents)) {
        failBlogPilotAnalyticsOwnership("unable to read {$label}");
    }

    return $contents;
}

$provider = readBlogPilotAnalyticsFile('BlogPilotServiceProvider.php', $providerPath);
$controller = readBlogPilotAnalyticsFile('BlogPilotAnalyticsController.php', $controllerPath);

foreach ([
    "BlogPilotPost::query()" => 'analytics controller does not load posts',
    "->where('user_id', $userId)" => 'analytics post data is not scoped to the authenticated owner',
    "BlogPilot::query()->where('user_id', $userId)->get()" => 'analytics agents are not scoped to the authenticated owner',
    "view('blogpilot::analytics.index'" => 'analytics view contract changed unexpectedly',
    'buildMonthRange(12)' => 'analytics 12-month range changed unexpectedly',
    'buildPublishedPostsChartData($userId, $agents, $monthRange)' => 'analytics chart no longer uses the owner-scoped agent collection',
    'buildAnalyticsNews($userId, $stats)' => 'analytics news feed contract changed unexpectedly',
] as $fragment => $failure) {
    if (! str_contains($controller, $fragment)) {
        failBlogPilotAnalyticsOwnership($failure);
    }
}

if (str_contains($controller, 'BlogPilot::query()->get()')) {
    failBlogPilotAnalyticsOwnership('analytics controller still performs a global agent query');
}

if (! str_contains($provider, "Route::get('analytics', BlogPilotAnalyticsController::class)->name('analytics');")) {
    failBlogPilotAnalyticsOwnership('analytics route does not use the owner-scoped analytics controller');
}

if (str_contains($provider, "Route::get('analytics', [BlogPilotController::class, 'analytics'])")) {
    failBlogPilotAnalyticsOwnership('analytics route still targets the legacy unscoped method');
}

echo "BlogPilot analytics ownership contract passed.\n";
