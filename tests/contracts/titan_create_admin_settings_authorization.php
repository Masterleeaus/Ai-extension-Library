<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$providers = [
    'CreativeSuite' => $root . '/app/extensions/CreativeSuite/System/CreativeSuiteServiceProvider.php',
    'AdvancedImage' => $root . '/app/extensions/AdvancedImage/System/AdvancedImageServiceProvider.php',
    'ProductPhotography' => $root . '/app/extensions/ProductPhotography/System/ProductPhotographyServiceProvider.php',
    'AIRealtimeImage' => $root . '/app/extensions/AIRealtimeImage/System/AIRealtimeImageServiceProvider.php',
];

function failAdminSettingsContract(string $message): never
{
    fwrite(STDERR, "Titan Create admin settings authorization contract failed: {$message}\n");
    exit(1);
}

function sourceFor(string $label, string $path): string
{
    $source = is_file($path) ? file_get_contents($path) : false;

    if (! is_string($source)) {
        failAdminSettingsContract("unable to read {$label} service provider");
    }

    return $source;
}

function excerptAround(string $source, string $anchor, int $before = 260, int $after = 420): string
{
    $position = strpos($source, $anchor);

    if ($position === false) {
        failAdminSettingsContract("missing route anchor {$anchor}");
    }

    $start = max(0, $position - $before);

    return substr($source, $start, $before + strlen($anchor) + $after);
}

function assertAdminGuard(string $source, string $anchor, string $label): void
{
    $excerpt = excerptAround($source, $anchor);
    $hasAdminGuard = str_contains($excerpt, "->middleware('admin')")
        || str_contains($excerpt, '->middleware([\'auth\', \'admin\'])')
        || str_contains($excerpt, "'middleware' => ['web', 'auth', 'admin']");

    if (! $hasAdminGuard) {
        failAdminSettingsContract("{$label} is not protected by the host admin middleware");
    }
}

$creativeSuite = sourceFor('CreativeSuite', $providers['CreativeSuite']);
$advancedImage = sourceFor('AdvancedImage', $providers['AdvancedImage']);
$productPhotography = sourceFor('ProductPhotography', $providers['ProductPhotography']);
$realtimeImage = sourceFor('AIRealtimeImage', $providers['AIRealtimeImage']);

assertAdminGuard(
    $creativeSuite,
    "'prefix'     => 'dashboard/admin/creative-suite'",
    'CreativeSuite admin settings'
);

foreach ([
    'NovitaSettingController::class' => 'AdvancedImage Novita settings',
    'FreepikSettingController::class' => 'AdvancedImage Freepik settings',
    'ClipdropSettingController::class' => 'AdvancedImage Clipdrop settings',
    'AdvancedImageSettingController::class' => 'AdvancedImage global settings',
] as $anchor => $label) {
    assertAdminGuard($advancedImage, $anchor, $label);
}

assertAdminGuard(
    $productPhotography,
    'PebblelySettingController::class',
    'ProductPhotography Pebblely settings'
);

assertAdminGuard(
    $realtimeImage,
    'TogetherSettingController::class',
    'AIRealtimeImage Together settings'
);

// The fix must stay scoped to admin settings. Shared user route groups must remain
// usable by ordinary authenticated customers.
foreach ([
    'CreativeSuite' => [
        $creativeSuite,
        "'prefix'     => 'dashboard/user/creative-suite'",
    ],
    'AdvancedImage' => [
        $advancedImage,
        "'middleware' => ['web', 'auth'],",
    ],
    'ProductPhotography' => [
        $productPhotography,
        "'middleware' => ['web', 'auth'],",
    ],
] as $label => [$source, $anchor]) {
    $excerpt = excerptAround($source, $anchor, 80, 180);

    if (str_contains($excerpt, "['web', 'auth', 'admin']")) {
        failAdminSettingsContract("{$label} shared user route group was incorrectly made admin-only");
    }
}

$realtimeOuter = excerptAround(
    $realtimeImage,
    "'middleware' => ['web', 'auth', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']",
    80,
    180
);

if (str_contains($realtimeOuter, "['web', 'auth', 'admin'")) {
    failAdminSettingsContract('AIRealtimeImage shared user route group was incorrectly made admin-only');
}

echo "Titan Create admin settings authorization contract passed.\n";
