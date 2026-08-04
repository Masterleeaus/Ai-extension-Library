#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Extensions\SystemAIChatSkills\System\Support\ExtensionManifestValidator;

require dirname(__DIR__) . '/System/Support/ExtensionManifestValidator.php';

$packageDirectory = $argv[1] ?? getcwd();
$packageDirectory = rtrim((string) $packageDirectory, DIRECTORY_SEPARATOR);
$manifestPath = $packageDirectory . DIRECTORY_SEPARATOR . 'extension.json';

if (! is_file($manifestPath)) {
    fwrite(STDERR, "extension.json was not found in {$packageDirectory}.\n");
    exit(1);
}

try {
    $manifest = json_decode(
        (string) file_get_contents($manifestPath),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
} catch (JsonException $exception) {
    fwrite(STDERR, "extension.json is not valid JSON: {$exception->getMessage()}\n");
    exit(1);
}

if (! is_array($manifest)) {
    fwrite(STDERR, "extension.json must contain a JSON object.\n");
    exit(1);
}

$errors = ExtensionManifestValidator::validate($manifest);
$provider = $manifest['entry_provider'] ?? null;

if (is_string($provider) && $provider !== '') {
    $providerPath = $packageDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $provider);

    if (! is_file($providerPath)) {
        $errors[] = "entry_provider not found: {$provider}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "Extension package {$manifest['key']} {$manifest['version']} is valid.\n";
