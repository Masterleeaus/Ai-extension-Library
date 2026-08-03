<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$commands = [
    ['PHP verification', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/run.php')],
    ['Template catalogue verification', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/template_catalogue_run.php')],
    ['Assurance workflow verification', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/assurance_workflows_run.php')],
    ['Commerce and vertical workflow verification', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/commerce_vertical_workflows_run.php')],
    ['LocalBrain v2 verification', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/localbrain_v2_run.php')],
];


foreach ($commands as [$label, $command]) {
    echo "\n== {$label} ==\n";
    passthru('cd ' . escapeshellarg($root) . ' && ' . $command, $status);
    if ($status !== 0) {
        fwrite(STDERR, "{$label} failed with status {$status}.\n");
        exit($status);
    }
}

echo "\nVerification complete.\n";
