<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$package = json_decode((string) file_get_contents($root . '/package.json'), true, 512, JSON_THROW_ON_ERROR);

assert(($composer['require']['titanzero/interaction-engine'] ?? null) === '^1.0');
assert(isset($package['dependencies']['@titanzero/interaction-engine-offline']));
assert(count(glob($root . '/**/*.php') ?: []) <= 2);

echo "PASS thin Interaction Engine Chatbot bridge
";
