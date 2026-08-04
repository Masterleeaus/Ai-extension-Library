<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$runtime = $root.'/System/TitanAI/Runtime';
$checks = [
    'delegation graph exists' => is_file($runtime.'/AssistantDelegationGraph.php'),
    'confidence fallback policy exists' => is_file($runtime.'/ConfidenceFallbackPolicy.php'),
    'assistant chain planner exists' => is_file($runtime.'/AssistantChainPlanner.php'),
    'orchestrator uses chain planner' => str_contains((string) file_get_contents($runtime.'/FiveTierOrchestrator.php'), 'AssistantChainPlanner $chains'),
    'orchestrator emits worker chain' => str_contains((string) file_get_contents($runtime.'/FiveTierOrchestrator.php'), "'worker_chain' => \$chain"),
    'provider binds delegation graph' => str_contains((string) file_get_contents($runtime.'/TitanAIRuntimeServiceProvider.php'), 'AssistantDelegationGraph::class'),
    'provider binds chain planner' => str_contains((string) file_get_contents($runtime.'/TitanAIRuntimeServiceProvider.php'), 'AssistantChainPlanner::class'),
    'diagnostics include delegations' => str_contains((string) file_get_contents($runtime.'/WorkerRouteRegistry.php'), "'delegations'"),
];

$failures = array_keys(array_filter($checks, static fn (bool $passed): bool => ! $passed));
foreach ($checks as $name => $passed) echo ($passed ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
if ($failures !== []) {
    fwrite(STDERR, 'Failed: '.implode(', ', $failures).PHP_EOL);
    exit(1);
}
