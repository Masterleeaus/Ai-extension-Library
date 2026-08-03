#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$executed = 0;

$run = static function (string $label, string $command, ?string $cwd = null) use (&$failures, &$executed): void {
    $executed++;
    $prefix = $cwd === null ? '' : 'cd ' . escapeshellarg($cwd) . ' && ';
    exec($prefix . $command . ' 2>&1', $output, $code);
    if ($code !== 0) {
        $failures[] = $label . " (exit {$code}): " . implode("\n", $output);
    }
};

$run('upgrade architecture gate', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/bin/verify-titanai-upgrade.php'));
$run('foundation smoke', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/standalone/titanai-foundation-smoke.php'));
$run('pass 2 hardening smoke', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/standalone/titanai-pass2-hardening.php'));

$phpContracts = [
    'app/Extensions/Chatbot/tests/Runtime/runtime_wiring_pass2_contract.php',
    'app/Extensions/Chatbot/tests/Runtime/shared_ai_convergence_contract.php',
    'app/Extensions/Chatbot/tests/Runtime/tier3-agent-restoration-pass4.php',
    'app/Extensions/Chatbot/tests/TitanAI/intent_gateway_test.php',
    'app/Extensions/Chatbot/tests/contracts/runtime-wiring-pass9.php',
    'app/Extensions/Chatbot/tests/runtime_wiring_pass6_contract.php',
    'app/Extensions/Chatbot/tests/titan-ai/runtime_wiring_contract.php',
];
foreach ($phpContracts as $contract) {
    $run($contract, escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/' . $contract));
}

foreach ([
    'app/Extensions/Chatbot/tests/generative-ui/verify_integration.py',
    'app/Extensions/Chatbot/tests/integration/test_team_chat_wiring.py',
] as $script) {
    $run($script, 'python3 ' . escapeshellarg($root . '/' . $script));
}

$chatbotRoot = $root . '/app/Extensions/Chatbot';
$nodeTests = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chatbotRoot . '/tests', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (! $file->isFile()) continue;
    $name = $file->getFilename();
    if (str_ends_with($name, '.test.js') || str_ends_with($name, '.test.mjs')) {
        $nodeTests[] = $file->getPathname();
    }
}
sort($nodeTests);
foreach ($nodeTests as $test) {
    $relative = substr($test, strlen($chatbotRoot) + 1);
    $run($relative, 'node --test ' . escapeshellarg($relative), $chatbotRoot);
}

$jsonFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'json') {
        $jsonFiles[] = $file->getPathname();
    }
}
foreach ($jsonFiles as $file) {
    $executed++;
    try {
        json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $failures[] = 'Invalid JSON ' . substr($file, strlen($root) + 1) . ': ' . $exception->getMessage();
    }
}

if ($failures !== []) {
    fwrite(STDERR, "TitanAI pass 2 verification FAILED (" . count($failures) . " failures across {$executed} checks)\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "TitanAI pass 2 verification PASSED ({$executed} checks; " . count($nodeTests) . " Node files; " . count($jsonFiles) . " JSON files)\n";
