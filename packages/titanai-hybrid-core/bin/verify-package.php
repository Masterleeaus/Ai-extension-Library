#!/usr/bin/env php
<?php

declare(strict_types=1);
$root = dirname(__DIR__); $failures = []; $checks = 0;
$assert = static function (bool $ok, string $message) use (&$failures, &$checks): void { $checks++; if (! $ok) $failures[] = $message; };
$composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$assert(($composer['name'] ?? null) === 'titanai/hybrid-core', 'Invalid package name.');
$assert(($composer['extra']['laravel']['providers'][0] ?? null) === 'TitanAI\Hybrid\TitanAIServiceProvider', 'Missing Laravel provider discovery.');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));
foreach ($iterator as $file) { if (! $file->isFile() || $file->getExtension() !== 'php') continue; $checks++; $output=[]; exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $code); if ($code !== 0) $failures[] = implode("
", $output); }
$checks++; $output=[]; exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/standalone/core-smoke.php') . ' 2>&1', $output, $code); if ($code !== 0) $failures[] = implode("
", $output);
if ($failures) { fwrite(STDERR, 'TitanAI package verification FAILED (' . count($failures) . ' failures across ' . $checks . " checks)
"); foreach ($failures as $failure) fwrite(STDERR, ' - ' . $failure . "
"); exit(1); }
echo 'TitanAI package verification PASSED (' . $checks . " checks)
";
