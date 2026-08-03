<?php

declare(strict_types=1);

/** @var array<class-string,string> $aliases */
$aliases = [
    TitanAI\Hybrid\ActionCompleted::class => 'App\\Domains\\TitanAI\\ActionCompleted',
    TitanAI\Hybrid\ActionFailed::class => 'App\\Domains\\TitanAI\\ActionFailed',
    TitanAI\Hybrid\ActionInvoked::class => 'App\\Domains\\TitanAI\\ActionInvoked',
    TitanAI\Hybrid\ActionsDiscovered::class => 'App\\Domains\\TitanAI\\ActionsDiscovered',
    TitanAI\Hybrid\ConnectorStateChanged::class => 'App\\Domains\\TitanAI\\ConnectorStateChanged',
    TitanAI\Hybrid\ConnectorsDiscovered::class => 'App\\Domains\\TitanAI\\ConnectorsDiscovered',
    TitanAI\Hybrid\Console\PurgeExpiredMemoriesCommand::class => 'App\\Domains\\TitanAI\\Console\\PurgeExpiredMemoriesCommand',
    TitanAI\Hybrid\Contracts\ActionDefinition::class => 'App\\Domains\\TitanAI\\Contracts\\ActionDefinition',
    TitanAI\Hybrid\Contracts\ConnectorDefinition::class => 'App\\Domains\\TitanAI\\Contracts\\ConnectorDefinition',
    TitanAI\Hybrid\Contracts\Registrable::class => 'App\\Domains\\TitanAI\\Contracts\\Registrable',
    TitanAI\Hybrid\Contracts\SkillDefinition::class => 'App\\Domains\\TitanAI\\Contracts\\SkillDefinition',
    TitanAI\Hybrid\Contracts\ToolDefinition::class => 'App\\Domains\\TitanAI\\Contracts\\ToolDefinition',
    TitanAI\Hybrid\Diagnostics\TitanAIDiagnostics::class => 'App\\Domains\\TitanAI\\Diagnostics\\TitanAIDiagnostics',
    TitanAI\Hybrid\Events\TitanAIEventBus::class => 'App\\Domains\\TitanAI\\Events\\TitanAIEventBus',
    TitanAI\Hybrid\ExtensionBooted::class => 'App\\Domains\\TitanAI\\ExtensionBooted',
    TitanAI\Hybrid\ExtensionShuttingDown::class => 'App\\Domains\\TitanAI\\ExtensionShuttingDown',
    TitanAI\Hybrid\Memory\Enums\MemoryScope::class => 'App\\Domains\\TitanAI\\Memory\\Enums\\MemoryScope',
    TitanAI\Hybrid\Memory\Services\UnifiedMemoryRepository::class => 'App\\Domains\\TitanAI\\Memory\\Services\\UnifiedMemoryRepository',
    TitanAI\Hybrid\Orchestration\CrossExtensionOrchestrator::class => 'App\\Domains\\TitanAI\\Orchestration\\CrossExtensionOrchestrator',
    TitanAI\Hybrid\Registries\UnifiedRegistry::class => 'App\\Domains\\TitanAI\\Registries\\UnifiedRegistry',
    TitanAI\Hybrid\SkillsDiscovered::class => 'App\\Domains\\TitanAI\\SkillsDiscovered',
    TitanAI\Hybrid\TitanAIServiceProvider::class => 'App\\Domains\\TitanAI\\TitanAIServiceProvider',
    TitanAI\Hybrid\UserMemoryUpdated::class => 'App\\Domains\\TitanAI\\UserMemoryUpdated',
    TitanAI\Hybrid\WorkflowMemoryUpdated::class => 'App\\Domains\\TitanAI\\WorkflowMemoryUpdated',
];

foreach ($aliases as $current => $legacy) {
    $legacyExists = class_exists($legacy, false)
        || interface_exists($legacy, false)
        || trait_exists($legacy, false)
        || (function_exists('enum_exists') && enum_exists($legacy, false));
    if (! $legacyExists) {
        class_alias($current, $legacy);
    }
}
