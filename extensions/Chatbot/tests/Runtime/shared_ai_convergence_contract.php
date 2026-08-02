<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$required = [
    'System/TitanAI/Contracts/TitanAIRuntimeContract.php',
    'System/TitanAI/Contracts/GeneralConversationEngineContract.php',
    'System/TitanAI/Contracts/AssistantDeploymentResolverContract.php',
    'System/TitanAI/Contracts/MemoryContextProviderContract.php',
    'System/TitanAI/Contracts/KnowledgeContextProviderContract.php',
    'System/TitanAI/Runtime/UnifiedTitanAIRuntime.php',
    'System/TitanAI/Runtime/LaravelGeneralConversationEngine.php',
    'System/TitanAI/Deployments/AssistantDeploymentResolver.php',
    'System/TitanAI/Context/MemoryContextProvider.php',
    'System/TitanAI/Context/KnowledgeContextProvider.php',
    'System/TitanAI/Capabilities/SharedCapabilityRegistry.php',
    'config/titan-ai.php',
];
foreach ($required as $file) {
    if (! is_file($root.'/'.$file)) {
        fwrite(STDERR, "Missing {$file}\n");
        exit(1);
    }
}
$runtime = file_get_contents($root.'/System/TitanAI/Runtime/UnifiedTitanAIRuntime.php');
foreach (['execution_mode', 'requires_legacy_fallback\' => false', 'GeneralConversationEngineContract', 'AssistantDeploymentResolverContract'] as $needle) {
    if (! str_contains($runtime, $needle)) {
        fwrite(STDERR, "Runtime contract missing {$needle}\n");
        exit(1);
    }
}
echo "Shared AI convergence contract passed.\n";
