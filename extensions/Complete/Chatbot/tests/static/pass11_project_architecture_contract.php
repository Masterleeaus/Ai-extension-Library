<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$required = [
    'System/TitanAI/Architecture/AuthorityBoundaryRegistry.php',
    'System/TitanAI/Architecture/InteractionEngineContractCatalog.php',
    'System/TitanAI/Architecture/ProjectArchitectureAuditService.php',
    'System/Http/Controllers/Api/TitanAI/ProjectArchitectureDiagnosticsController.php',
    'config/titan_project_architecture.php',
];
foreach ($required as $file) {
    if (! is_file($root.'/'.$file)) throw new RuntimeException("Missing {$file}");
}
$provider = file_get_contents($root.'/System/ChatbotServiceProvider.php');
foreach (['runtime/project-architecture','titan_project_architecture'] as $needle) {
    if (! str_contains($provider, $needle)) throw new RuntimeException("Provider missing {$needle}");
}
$config = require $root.'/config/titan_project_architecture.php';
foreach (['workcore_v2','extension_sdk_v2','interaction_engine','chatbot_pwa'] as $source) {
    if (! isset($config['sources'][$source])) throw new RuntimeException("Source missing {$source}");
}
echo "Pass 11 project architecture contract passed.\n";
