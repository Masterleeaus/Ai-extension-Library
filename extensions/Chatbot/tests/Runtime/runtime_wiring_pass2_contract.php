<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$required = [
    'System/TitanAI/Runtime/DynamicWorkerSelector.php',
    'System/TitanAI/Runtime/AgentExecutionPathResolver.php',
    'System/TitanAI/Runtime/FiveTierOrchestrator.php',
];
foreach ($required as $relative) {
    if (! is_file($root.'/'.$relative)) {
        fwrite(STDERR, "Missing {$relative}\n"); exit(1);
    }
}
$orchestrator = file_get_contents($root.'/System/TitanAI/Runtime/FiveTierOrchestrator.php');
$checks = [
    'dynamic selector injection' => str_contains($orchestrator, 'DynamicWorkerSelector $selector'),
    'execution resolver injection' => str_contains($orchestrator, 'AgentExecutionPathResolver $executionPaths'),
    'worker fallback selection' => str_contains($orchestrator, 'resolveWorkers($decision)'),
    'execution path validation' => str_contains($orchestrator, '$this->executionPaths->resolve'),
    'execution path context' => str_contains($orchestrator, "'agent_execution_path' => ".'$executionPath'),
];
foreach ($checks as $name => $ok) {
    if (! $ok) { fwrite(STDERR, "FAIL: {$name}\n"); exit(1); }
}
echo "Runtime wiring pass 2 contract: ".count($checks)." checks passed\n";
