<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$registry = file_get_contents($root.'/System/TitanAI/Runtime/WorkerRouteRegistry.php');
$orchestrator = file_get_contents($root.'/System/TitanAI/Runtime/FiveTierOrchestrator.php');
$provider = file_get_contents($root.'/System/TitanAI/Runtime/TitanAIRuntimeServiceProvider.php');
$serviceProvider = file_get_contents($root.'/System/ChatbotServiceProvider.php');

$checks = [
    'alias resolution' => str_contains($registry, 'canonicalId'),
    'duplicate id protection' => str_contains($registry, 'Duplicate Titan AI worker id'),
    'worker diagnostics' => str_contains($registry, 'function diagnostics'),
    'permission enforcement' => str_contains($orchestrator, 'assertAllowed'),
    'worker definitions in context' => str_contains($orchestrator, 'worker_definitions'),
    'permission guard binding' => str_contains($provider, 'WorkerPermissionGuard::class'),
    'diagnostics route' => str_contains($serviceProvider, 'runtime/diagnostics'),
];

$failed = array_keys(array_filter($checks, static fn(bool $ok): bool => ! $ok));
if ($failed !== []) {
    fwrite(STDERR, 'FAILED: '.implode(', ', $failed).PHP_EOL);
    exit(1);
}

echo 'runtime wiring contract: '.count($checks).' checks passed'.PHP_EOL;
