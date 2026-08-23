<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/AIRealtimeImage/System/Http/Controllers/AIRealtimeImageController.php';
$providerPath = $root . '/app/extensions/AIRealtimeImage/System/AIRealtimeImageServiceProvider.php';

function failRealtimeGalleryOwnershipContract(string $message): never
{
    fwrite(STDERR, "AIRealtimeImage gallery ownership contract failed: {$message}\n");
    exit(1);
}

$controller = is_file($controllerPath) ? file_get_contents($controllerPath) : false;
$provider = is_file($providerPath) ? file_get_contents($providerPath) : false;

if (! is_string($controller)) {
    failRealtimeGalleryOwnershipContract('unable to read AIRealtimeImageController.php');
}

if (! is_string($provider)) {
    failRealtimeGalleryOwnershipContract('unable to read AIRealtimeImageServiceProvider.php');
}

if (! preg_match('/public function gallery\(\)\s*\{(?<body>.*?)\n\s*\}\n\n\s*public function destroy/s', $controller, $match)) {
    failRealtimeGalleryOwnershipContract('unable to isolate gallery method');
}

$gallery = $match['body'];

foreach ([
    "->where('user_id', auth()->id())" => 'gallery is not scoped to the authenticated owner',
    "->where('status', Status::success->value)" => 'gallery no longer filters successful images',
    "->orderBy('created_at', 'desc')" => 'gallery no longer preserves newest-first ordering',
    '->paginate(15)' => 'gallery no longer preserves pagination size',
] as $fragment => $failure) {
    if (! str_contains($gallery, $fragment)) {
        failRealtimeGalleryOwnershipContract($failure);
    }
}

if (! str_contains($provider, "'middleware' => ['web', 'auth', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']")) {
    failRealtimeGalleryOwnershipContract('AIRealtimeImage dashboard route group is no longer authenticated');
}

if (! str_contains($provider, "->get('ai-realtime-image/gallery', [AIRealtimeImageController::class, 'gallery'])")) {
    failRealtimeGalleryOwnershipContract('gallery route contract changed unexpectedly');
}

// The ordinary index is the canonical donor pattern for owner isolation. Keep it
// owner-scoped too so a future refactor cannot fix gallery by weakening index.
if (substr_count($controller, "->where('user_id', auth()->id())") < 2) {
    failRealtimeGalleryOwnershipContract('index and gallery must both enforce owner scope');
}

echo "AIRealtimeImage gallery ownership contract passed.\n";
