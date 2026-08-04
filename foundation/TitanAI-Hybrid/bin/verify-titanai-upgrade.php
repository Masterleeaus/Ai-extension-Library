#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$checks = 0;

$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (! $condition) {
        $failures[] = $message;
    }
};

$read = static function (string $relative) use ($root, $assert): string {
    $path = $root . '/' . $relative;
    $assert(is_file($path), "Missing required file: {$relative}");
    return is_file($path) ? (string) file_get_contents($path) : '';
};

$requiredFiles = [
    'app/Domains/TitanAI/TitanAIServiceProvider.php',
    'app/Domains/TitanAI/SkillsDiscovered.php',
    'app/Domains/TitanAI/ActionsDiscovered.php',
    'app/Domains/TitanAI/ConnectorsDiscovered.php',
    'app/Domains/TitanAI/ActionInvoked.php',
    'app/Domains/TitanAI/ActionCompleted.php',
    'app/Domains/TitanAI/ActionFailed.php',
    'app/Domains/TitanAI/ExtensionBooted.php',
    'app/Extensions/Chatbot/System/TitanAI/Runtime/Skills/UnifiedSkillAdapter.php',
    'app/Extensions/AIAgent/System/Actions/UnifiedActionAdapter.php',
    'app/Extensions/AIChatPro/System/Connectors/UnifiedConnectorAdapter.php',
    'tests/standalone/titanai-pass2-hardening.php',
    'docs/titanai-hybrid-architecture.md',
    'docs/titanai-component-authoring.md',
    'docs/titanai-troubleshooting.md',
    'docs/titanai-rollback.md',
];

foreach ($requiredFiles as $file) {
    $assert(is_file($root . '/' . $file), "Missing required file: {$file}");
}

$eventsManifest = $read('app/Domains/TitanAI/Events.php');
$assert(! str_contains($eventsManifest, 'class SkillsDiscovered'), 'Events.php must not define non-PSR-4 event classes.');

$foundationProvider = $read('app/Domains/TitanAI/TitanAIServiceProvider.php');
$assert(str_contains($foundationProvider, 'singleton(UnifiedRegistry::class'), 'Foundation provider must bind UnifiedRegistry as a singleton.');
$assert(str_contains($foundationProvider, 'singleton(UnifiedMemoryRepository::class'), 'Foundation provider must bind UnifiedMemoryRepository as a singleton.');
$assert(str_contains($foundationProvider, 'loadMigrationsFrom'), 'Foundation provider must load unified memory migrations.');

$memory = $read('app/Domains/TitanAI/Memory/Services/UnifiedMemoryRepository.php');
$assert(str_contains($memory, 'Illuminate\\Support\\Facades\\DB'), 'Unified memory must use the database repository.');
$assert(! str_contains($memory, 'Facades\\Cache'), 'Unified memory must not be cache-only.');
$assert(str_contains($memory, 'ttl_minutes'), 'Unified memory must enforce TTL metadata.');
$assert(str_contains($memory, '->upsert('), 'Unified memory must use atomic upsert semantics.');
$assert(str_contains($memory, 'UserMemoryUpdated'), 'Unified memory must emit user memory updates.');
$assert(str_contains($memory, 'WorkflowMemoryUpdated'), 'Unified memory must emit workflow memory updates.');
$assert(str_contains($memory, 'assertMaxLength'), 'Unified memory must validate storage-column lengths.');

$migration = $read('database/migrations/2026_08_03_000001_create_unified_memories_table.php');
$assert(str_contains($migration, 'unique('), 'Unified memory migration must prevent duplicate logical keys.');

$providers = [
    'chatbot' => 'app/Extensions/Chatbot/System/ChatbotServiceProvider.php',
    'aiagent' => 'app/Extensions/AIAgent/System/AIAgentServiceProvider.php',
    'aichatpro' => 'app/Extensions/AIChatPro/System/AIChatProServiceProvider.php',
];
foreach ($providers as $name => $file) {
    $source = $read($file);
    $assert(str_contains($source, 'TitanAIServiceProvider::class'), "{$name} provider must register the TitanAI foundation.");
    $assert(str_contains($source, 'UnifiedRegistry'), "{$name} provider must integrate with UnifiedRegistry.");
    $assert(str_contains($source, 'ExtensionBooted'), "{$name} provider must emit ExtensionBooted.");
    $assert(str_contains($source, "titanai.extensions.{$name}.enabled"), "{$name} provider must honour its hybrid integration enabled flag.");
}

$chatbot = $read($providers['chatbot']);
$assert(str_contains($chatbot, 'SkillsDiscovered'), 'Chatbot provider must emit SkillsDiscovered.');
$assert(str_contains($chatbot, 'FieldServiceSkillRegistry'), 'Chatbot provider must use the real bundled skill registry.');

$aiagent = $read($providers['aiagent']);
$assert(str_contains($aiagent, 'ActionsDiscovered'), 'AI Agent provider must emit ActionsDiscovered.');
$assert(str_contains($aiagent, 'UnifiedActionAdapter'), 'AI Agent provider must adapt native actions.');
$assert(str_contains($aiagent, 'onRegistered'), 'AI Agent provider must mirror late action registration.');

$actionRegistry = $read('app/Extensions/AIAgent/System/Engine/AIAgentActionRegistry.php');
$assert(str_contains($actionRegistry, 'registeredListeners'), 'AI Agent action registry must support registration listeners.');
$assert(str_contains($actionRegistry, 'already registered by'), 'AI Agent action registry must reject conflicting replacements.');

$aichatpro = $read($providers['aichatpro']);
$assert(str_contains($aichatpro, 'ConnectorsDiscovered'), 'AIChatPro provider must emit ConnectorsDiscovered.');
$assert(str_contains($aichatpro, 'onRegistered'), 'AIChatPro provider must mirror late connector registration.');

$connectorRegistry = $read('app/Extensions/AIChatPro/System/Connectors/ConnectorRegistry.php');
$assert(str_contains($connectorRegistry, 'must implement'), 'AIChatPro connector registry must validate provider contracts.');
$assert(str_contains($connectorRegistry, 'already registered by'), 'AIChatPro connector registry must reject conflicting replacements.');

$unifiedRegistry = $read('app/Domains/TitanAI/Registries/UnifiedRegistry.php');
$assert(substr_count($unifiedRegistry, 'return clone $this->') >= 4, 'UnifiedRegistry must return immutable collection snapshots.');

$skillAdapter = $read('app/Extensions/Chatbot/System/TitanAI/Runtime/Skills/UnifiedSkillAdapter.php');
$assert(str_contains($skillAdapter, "'execution_mode' => 'governed_context'"), 'Chatbot skills must declare governed-context execution.');
$assert(str_contains($skillAdapter, 'cannot be invoked directly'), 'Chatbot skill adapter must not expose internal instructions through direct execution.');

$config = $read('config/titanai.php');
foreach (['chatbot', 'aiagent', 'aichatpro', 'auto_register_to_unified_registry', 'emit_discovery_events', 'listen_to_events', 'emit_events'] as $needle) {
    $assert(str_contains($config, $needle), "TitanAI config missing {$needle}.");
}

$skillRegistryPath = $root . '/app/Extensions/Chatbot/System/TitanAI/Runtime/Skills/FieldServiceSkillRegistry.php';
if (is_file($skillRegistryPath)) {
    require_once $skillRegistryPath;
    $registryClass = 'App\\Extensions\\Chatbot\\System\\TitanAI\\Runtime\\Skills\\FieldServiceSkillRegistry';
    $skills = (new $registryClass())->all();
    $assert(count($skills) === 7, 'Expected exactly seven integrity-verified bundled Chatbot skills.');
}

$phpFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

foreach ($phpFiles as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    exec($command, $output, $code);
    if ($code !== 0) {
        $failures[] = 'PHP lint failed: ' . substr($file, strlen($root) + 1) . ' :: ' . implode(' ', $output);
    }
    $checks++;
    $output = [];
}

if ($failures !== []) {
    fwrite(STDERR, "TitanAI upgrade verification FAILED ({$checks} checks, " . count($failures) . " failures)\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "TitanAI upgrade verification PASSED ({$checks} checks, " . count($phpFiles) . " PHP files linted)\n";
