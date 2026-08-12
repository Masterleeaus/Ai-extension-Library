<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$providerPath = $root . '/app/extensions/NanoBanana/System/NanoBananaServiceProvider.php';

function failNanoBananaProviderIntegrity(string $message): never
{
    fwrite(STDERR, "NanoBanana provider integrity contract failed: {$message}\n");
    exit(1);
}

$provider = is_file($providerPath) ? file_get_contents($providerPath) : false;

if (! is_string($provider)) {
    failNanoBananaProviderIntegrity('unable to read NanoBananaServiceProvider.php');
}

foreach (['<<<<<<<', '=======', '>>>>>>>'] as $marker) {
    if (str_contains($provider, $marker)) {
        failNanoBananaProviderIntegrity("unresolved Git conflict marker remains: {$marker}");
    }
}

if (! str_contains($provider, '$path = public_path("vendor/nanobanana");')) {
    failNanoBananaProviderIntegrity('uninstall target is not vendor/nanobanana');
}

if (! str_contains($provider, "return 'nano-banana';")) {
    failNanoBananaProviderIntegrity('extension registration key changed unexpectedly');
}

if (! str_contains($provider, "generator/webhook/fal-ai")) {
    failNanoBananaProviderIntegrity('existing webhook route changed unexpectedly');
}

echo "NanoBanana provider integrity contract passed.\n";
