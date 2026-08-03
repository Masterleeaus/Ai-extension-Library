#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$checks = 0;

$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (! $condition) $failures[] = $message;
};
$read = static function (string $relative) use ($root, $assert): string {
    $path = $root . '/' . $relative;
    $assert(is_file($path), "Missing required Pass 3 file: {$relative}");
    return is_file($path) ? (string) file_get_contents($path) : '';
};
$run = static function (string $label, string $command) use (&$failures, &$checks, $root): void {
    $checks++;
    exec('cd ' . escapeshellarg($root) . ' && ' . $command . ' 2>&1', $output, $code);
    if ($code !== 0) $failures[] = $label . " (exit {$code}): " . implode("\n", $output);
};

$run('Pass 2 regression suite', escapeshellarg(PHP_BINARY) . ' bin/verify-titanai-pass2.php');
$run('Pass 3 orchestration runtime', escapeshellarg(PHP_BINARY) . ' tests/standalone/titanai-pass3-orchestration.php');

$provider = $read('app/Domains/TitanAI/TitanAIServiceProvider.php');
foreach ([
    'TitanAIDiagnostics::class',
    'TitanAIEventBus::class',
    'CrossExtensionOrchestrator::class',
    "'titanai.diagnostics'",
    "'titanai.events'",
    "'titanai.orchestrator'",
    'PurgeExpiredMemoriesCommand::class',
    "command('titanai:memory:purge')",
    'withoutOverlapping()',
] as $needle) {
    $assert(str_contains($provider, $needle), "TitanAIServiceProvider missing Pass 3 wiring: {$needle}");
}

$eventBus = $read('app/Domains/TitanAI/Events/TitanAIEventBus.php');
foreach ([
    'listenOnce(',
    'idempotency_cache_size',
    "recordEvent('duplicate'",
    "recordEvent('failed'",
    'native operation was isolated',
    'produced side effects',
] as $needle) {
    $assert(str_contains($eventBus, $needle), "TitanAIEventBus missing behaviour: {$needle}");
}
$assert(! str_contains($eventBus, 'unset($this->delivered[$idempotencyKey])'), 'Failed event idempotency keys must remain reserved after possible partial listener side effects.');

$diagnostics = $read('app/Domains/TitanAI/Diagnostics/TitanAIDiagnostics.php');
foreach (['idempotency_key_hash', "'health'", "'events'", "'memory'", "'orchestration'", '$exception::class'] as $needle) {
    $assert(str_contains($diagnostics, $needle), "TitanAIDiagnostics missing safe diagnostic field: {$needle}");
}
$assert(! str_contains($diagnostics, 'getMessage()'), 'TitanAIDiagnostics must not retain raw exception messages.');

$orchestrator = $read('app/Domains/TitanAI/Orchestration/CrossExtensionOrchestrator.php');
foreach (['executeAction(', 'send(', 'matchingSkills(', 'correlation_id', 'action_execution_failed', 'connector_send_failed'] as $needle) {
    $assert(str_contains($orchestrator, $needle), "CrossExtensionOrchestrator missing contract: {$needle}");
}
$assert(str_contains($orchestrator, 'TitanAI action execution failed.'), 'Action failures must return a stable non-sensitive message.');
$assert(str_contains($orchestrator, 'TitanAI connector delivery failed.'), 'Connector failures must return a stable non-sensitive message.');

$memoryRepository = $read('app/Domains/TitanAI/Memory/Services/UnifiedMemoryRepository.php');
foreach (['TitanAIEventBus', 'TitanAIDiagnostics', 'forgetAllForEntity(', 'idempotencyKey', "recordMemory('read'"] as $needle) {
    $assert(str_contains($memoryRepository, $needle), "UnifiedMemoryRepository missing Pass 3 behaviour: {$needle}");
}

$bridge = $read('app/Extensions/AIAgent/System/Memory/UnifiedMemoryBridge.php');
foreach (["'aiagent.memory.'", 'features.shared_memory', 'memory_bridge.enabled', 'memory_bridge.strict', 'forgetAllForEntity'] as $needle) {
    $assert(str_contains($bridge, $needle), "AI Agent shared-memory bridge missing behaviour: {$needle}");
}

$nativeMemory = $read('app/Extensions/AIAgent/System/Memory/MemoryRepository.php');
$assert(substr_count($nativeMemory, 'DB::transaction(') === 4, 'AI Agent memory create/update/delete/delete-all must be transactionally bridged.');
foreach (['unifiedMemory->remember', 'unifiedMemory->forget', 'unifiedMemory->forgetAll'] as $needle) {
    $assert(str_contains($nativeMemory, $needle), "AI Agent native memory repository missing mirror call: {$needle}");
}

$memoryContext = $read('app/Extensions/Chatbot/System/TitanAI/Context/MemoryContextProvider.php');
foreach (['UnifiedMemoryRepository', "allForEntity('user'", "'workflow'", 'context_entry_limit', 'chatbot_context_read', 'features.shared_memory'] as $needle) {
    $assert(str_contains($memoryContext, $needle), "Chatbot shared memory context missing behaviour: {$needle}");
}

$actionAdapter = $read('app/Extensions/AIAgent/System/Actions/UnifiedActionAdapter.php');
foreach (['TitanAIEventBus', 'correlation_id', ':invoked', ':completed', ':failed', 'lifecycle listener failed'] as $needle) {
    $assert(str_contains($actionAdapter, $needle), "UnifiedActionAdapter missing lifecycle isolation: {$needle}");
}

$actionRegistry = $read('app/Extensions/AIAgent/System/Engine/AIAgentActionRegistry.php');
$connectorRegistry = $read('app/Extensions/AIChatPro/System/Connectors/ConnectorRegistry.php');
foreach ([$actionRegistry, $connectorRegistry] as $index => $source) {
    $label = $index === 0 ? 'AI Agent action registry' : 'AIChatPro connector registry';
    $assert(str_contains($source, 'registeredListenerKeys'), "{$label} lacks named listener deduplication.");
    $assert(str_contains($source, '?string $listenerKey = null'), "{$label} lacks a stable listener key API.");
}

$providers = [
    'chatbot' => $read('app/Extensions/Chatbot/System/ChatbotServiceProvider.php'),
    'aiagent' => $read('app/Extensions/AIAgent/System/AIAgentServiceProvider.php'),
    'aichatpro' => $read('app/Extensions/AIChatPro/System/AIChatProServiceProvider.php'),
];
foreach ($providers as $name => $source) {
    $assert(str_contains($source, 'TitanAIEventBus'), "{$name} provider does not use the failure-isolated event bus.");
    $assert(str_contains($source, 'listenOnce('), "{$name} provider does not deduplicate event listeners.");
    $assert(str_contains($source, "extension:{$name}:booted"), "{$name} provider lacks an idempotent boot event key.");
}
$assert(str_contains($providers['aiagent'], "listenerKey: 'titanai.unified.actions'"), 'AI Agent provider lacks a stable native registry bridge listener key.');
$assert(str_contains($providers['aichatpro'], "listenerKey: 'titanai.unified.connectors'"), 'AIChatPro provider lacks a stable native registry bridge listener key.');

$controller = $read('app/Extensions/Chatbot/System/Http/Controllers/Api/TitanAI/TitanAIRuntimeDiagnosticsController.php');
foreach (['TitanAIDiagnostics', 'UnifiedRegistry', "'hybrid_foundation'"] as $needle) {
    $assert(str_contains($controller, $needle), "Runtime diagnostics endpoint missing Pass 3 surface: {$needle}");
}

$config = $read('config/titanai.php');
foreach ([
    'TITANAI_EVENTS_ENABLED',
    'TITANAI_EVENTS_STRICT',
    'TITANAI_EVENTS_IDEMPOTENCY_CACHE_SIZE',
    'TITANAI_AIAGENT_MEMORY_BRIDGE_ENABLED',
    'TITANAI_AIAGENT_MEMORY_BRIDGE_STRICT',
    'TITANAI_MEMORY_CONTEXT_ENTRY_LIMIT',
    'TITANAI_CROSS_EXTENSION_ACTIONS',
    'TITANAI_CROSS_EXTENSION_CONNECTORS',
    'TITANAI_SHARED_MEMORY',
] as $needle) {
    $assert(str_contains($config, $needle), "TitanAI config missing Pass 3 setting: {$needle}");
}

foreach ([
    'docs/titanai-pass3-operations.md',
    'docs/titanai-hybrid-architecture.md',
    'docs/titanai-troubleshooting.md',
    'docs/titanai-rollback.md',
] as $document) {
    $source = $read($document);
    $assert(trim($source) !== '', "Pass 3 documentation is empty: {$document}");
}

if ($failures !== []) {
    fwrite(STDERR, "TitanAI pass 3 verification FAILED (" . count($failures) . " failures across {$checks} checks)\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "TitanAI pass 3 verification PASSED ({$checks} checks)\n";
